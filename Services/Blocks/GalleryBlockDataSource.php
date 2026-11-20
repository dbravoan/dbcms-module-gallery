<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Services\Blocks;

use DbCms\Core\Blocks\Contracts\BlockDataSourceInterface;
use DbCms\Core\Contracts\Services\PublicContentDiscoveryInterface;
use DbCms\Core\Models\Market;
use DbCms\Core\Services\Content\PublicTranslation;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Database\Eloquent\Builder;

/** Only published, localized owner data crosses the reusable block boundary. */
final class GalleryBlockDataSource implements BlockDataSourceInterface
{
    public function fields(): array
    {
        return [
            'id' => ['type' => 'integer', 'label' => 'ID'],
            'title' => ['type' => 'text', 'label' => 'Título'],
            'summary' => ['type' => 'text', 'label' => 'Resumen'],
            'image_url' => ['type' => 'image', 'label' => 'Imagen'],
            'url' => ['type' => 'url', 'label' => 'Enlace'],
        ];
    }

    public function filters(): array
    {
        return [
            'id' => ['type' => 'integer', 'label' => 'ID', 'rules' => ['nullable', 'integer', 'min:1']],
            'order' => ['type' => 'select', 'label' => 'Orden', 'options' => ['newest' => 'Recientes', 'oldest' => 'Antiguos'], 'rules' => ['nullable', 'in:newest,oldest']],
        ];
    }

    public function items(Market $market, string $locale, array $filters, int $page, int $limit): array
    {
        $discovery = app(PublicContentDiscoveryInterface::class);
        /** @var Builder<Gallery> $query */
        $query = $discovery->query('gallery', $market, $locale);
        $query->without(['publishedSections', 'publishedSections.translations']);
        PublicTranslation::constrainResolvedField($query, $market, $locale, 'slug', 'like', '_%');
        if (isset($filters['id'])) {
            $query->whereKey((int) $filters['id']);
        }
        $query->with(['items' => static fn ($items) => $items->limit(1)]);
        $direction = ($filters['order'] ?? 'newest') === 'oldest' ? 'asc' : 'desc';
        $limit = max(1, min(50, $limit));
        $page = max(1, min(10000, $page));
        $records = $query->orderBy('created_at', $direction)->orderBy('id', $direction)
            ->offset(($page - 1) * $limit)->limit($limit + 1)->get();
        $items = [];
        foreach ($records->take($limit) as $record) {
            $translation = PublicTranslation::resolve($record, $locale, $market);
            $item = [
                'id' => $record->id,
                'title' => (string) $translation?->getAttribute('title'),
                'summary' => strip_tags((string) $translation?->getAttribute('description')),
                'image_url' => $record->items->first()?->image_url,
                'url' => $discovery->url('gallery', $market, $locale, (string) $translation?->getAttribute('slug')),
            ];
            $items[] = $item;
        }

        return ['items' => $items, 'has_more' => $records->count() > $limit];
    }
}
