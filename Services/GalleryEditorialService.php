<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Services;

use DbCms\Core\Capabilities\ActorContext;
use DbCms\Core\Capabilities\CapabilityAuthorizer;
use DbCms\Core\Capabilities\ExecutionContext;
use DbCms\Core\DTOs\Content\EntityMutationData;
use DbCms\Core\Models\Language;
use DbCms\Core\Models\Market;
use DbCms\Core\Models\User;
use DbCms\Core\Services\Capabilities\EntityMutationService;
use DbCms\Core\Services\Capabilities\EntityReadService;
use DbCms\Core\Services\Content\EditorialDraftService;
use DbCms\Modules\Gallery\Contracts\GalleryEditorialServiceInterface;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Support\Facades\Auth;

/** Entry adapter; all mutation rules and transactions belong to the common writer. */
final class GalleryEditorialService implements GalleryEditorialServiceInterface
{
    public function __construct(private readonly EntityMutationService $entities, private readonly EntityReadService $reader, private readonly CapabilityAuthorizer $authorizer) {}

    public function create(array $input): Gallery
    {
        $actor = $this->authorize();
        $payload = $this->entities->create('gallery', EntityMutationData::fromForm($input)->arguments, $actor);
        $gallery = $this->reader->resolveEntity('gallery', $payload['id']);
        assert($gallery instanceof Gallery);

        return $gallery->load(['translations', 'items', 'markets']);
    }

    public function update(Gallery $gallery, array $input): array
    {
        $this->authorize();
        $args = EntityMutationData::fromForm($input)->arguments;
        if ($gallery->is_active && ! empty($args['attributes']['is_active'])) {
            unset($args['attributes']['is_active']);
        }

        return $this->entities->update('gallery', $gallery->getKey(), $args);
    }

    public function delete(Gallery $gallery): void
    {
        $this->authorize();
        $this->entities->delete('gallery', $gallery->getKey());
    }

    public function editData(Gallery $gallery): array
    {
        $this->authorize();
        $gallery->load(['translations', 'items', 'markets']);

        return ['gallery' => app(EditorialDraftService::class)->forEditor($gallery), 'markets' => Market::with('languages')->where('is_active', true)->get(), 'languages' => Language::where('is_active', true)->get()];
    }

    private function authorize(): User
    {
        $actor = app(ExecutionContext::class)->actor;
        $user = $actor !== null ? $this->authorizer->user($actor) : Auth::user();
        $actor ??= new ActorContext($user instanceof User ? (int) $user->getKey() : null);
        abort_unless($user instanceof User && $this->authorizer->allowed($actor, 'write', 'manage:galleries'), 403);

        return $user;
    }
}
