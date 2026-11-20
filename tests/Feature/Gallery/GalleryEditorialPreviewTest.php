<?php

declare(strict_types=1);

use DbCms\Core\DTOs\Front\PublicContentContext;
use DbCms\Core\Models\Market;
use DbCms\Modules\Gallery\Models\Gallery;
use DbCms\Modules\Gallery\Models\GalleryTranslation;
use DbCms\Modules\Gallery\Services\GalleryEditorialPreview;
use DbCms\Modules\Gallery\Services\GalleryMutationHandler;
use Tests\Support\GalleryAuditComposition;

uses(GalleryAuditComposition::class);

test('gallery preview renders hydrated revision items and requested translation', function (): void {
    $market = new Market;
    $market->id = 1;
    $gallery = new Gallery;
    $gallery->id = 1;
    $translation = new GalleryTranslation;
    $translation->setRawAttributes(['locale' => 'es', 'market_id' => null, 'title' => 'Borrador', 'slug' => 'borrador']);
    $gallery->setRelation('translations', $translation->newCollection([$translation]));
    app(GalleryMutationHandler::class)->hydrateRevision($gallery, ['items' => [
        ['id' => 3, 'image_url' => '/pending.webp', 'title' => 'Pendiente'],
        ['id' => 4, 'image_url' => '/second.webp', 'title' => 'Segundo'],
    ]]);

    $handler = app(GalleryEditorialPreview::class);
    $preview = $handler->render($gallery, new PublicContentContext($market, 'es'));
    expect($preview->data['translation']->title)->toBe('Borrador')
        ->and($preview->data['items']->pluck('image_url')->all())->toBe(['/pending.webp', '/second.webp'])
        ->and($handler->render($gallery, new PublicContentContext($market, 'en'))->viewName)->toBeNull();
});
