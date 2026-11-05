<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Blocks;

use DbCms\Core\Blocks\AbstractBlockType;
use DbCms\Core\Blocks\Contracts\ContextualBlockReferencesInterface;
use DbCms\Core\Content\Contracts\PublicWidgetSemanticContributorInterface;
use DbCms\Core\Contracts\Services\PublicContentDiscoveryInterface;
use DbCms\Core\Models\Market;
use DbCms\Core\Sections\SectionLayout;
use DbCms\Core\Services\Cms\FrontViewResolver;
use DbCms\Core\Services\Content\PublicTranslation;
use DbCms\Modules\Gallery\Models\Gallery;
use DbCms\Modules\Gallery\Models\GalleryItem;
use Illuminate\Database\Eloquent\Builder;

class GalleryAlbumBlock extends AbstractBlockType implements ContextualBlockReferencesInterface, PublicWidgetSemanticContributorInterface
{
    public function getType(): string
    {
        return 'gallery';
    }

    public function getName(): string
    {
        return 'Galería de Imágenes / Álbumes';
    }

    public function getIcon(): string
    {
        return 'fa-solid fa-images';
    }

    public function getCategory(): string
    {
        return 'dynamic';
    }

    public function getDescription(): string
    {
        return 'Muestra un álbum específico de imágenes o un catálogo de galerías fotográficas.';
    }

    public function getRequiredModule(): ?string
    {
        return 'gallery';
    }

    /**
     * The block manages its own grid: it is added as a section, never nested
     * inside a column (a grid inside a column renders as a grid inside a grid).
     */
    public function isFullWidth(): bool
    {
        return true;
    }

    /** @return list<string> */
    public function getTranslationPaths(): array
    {
        return ['title'];
    }

    public function getEditorData(): array
    {
        $galleries = Gallery::where('is_active', true)->with(['translations', 'items'])->get();

        return [
            'gallery_albums' => $galleries->map(fn (Gallery $gallery): array => ['id' => $gallery->id, 'title' => $gallery->title, 'slug' => $gallery->slug, 'cover_url' => $gallery->items->first()?->image_url, 'image_count' => $gallery->items->count()])->all(),
            'gallery_images' => $galleries->flatMap(fn (Gallery $gallery) => $gallery->items->map(fn (GalleryItem $item): array => ['id' => $item->id, 'url' => $item->image_url, 'title' => $item->title, 'gallery_id' => $gallery->id]))->values()->all(),
        ];
    }

    public function getContextualEditorData(Market $market, string $locale): array
    {
        /** @var Builder<Gallery> $query */
        $query = app(PublicContentDiscoveryInterface::class)->query('gallery', $market, $locale);

        return ['gallery_albums' => array_values($query->orderBy('id')->get()->map(static function (Gallery $gallery) use ($market, $locale): array {
            $translation = PublicTranslation::resolve($gallery, $locale, $market);

            return ['id' => $gallery->id, 'title' => (string) $translation?->getAttribute('title'), 'slug' => (string) $translation?->getAttribute('slug')];
        })->all())];
    }

    public function getEditorSchema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Título', 'description' => 'Título opcional sobre la galería.'],
            'album_slug' => ['type' => 'select', 'label' => 'Galería', 'options' => ['' => 'Todas'], 'options_source' => 'gallery_albums', 'option_label' => 'title', 'option_value' => 'slug', 'rules' => ['nullable', 'string', 'max:255'], 'description' => 'Álbum que se muestra. Déjalo vacío para incluir todos.'],
            'grid_columns' => ['type' => 'select', 'label' => 'Columnas', 'options' => ['2', '3', '4'], 'default' => '3', 'description' => 'Columnas de la rejilla en pantallas grandes.'],
            'limit' => ['type' => 'integer', 'label' => 'Límite', 'default' => 6, 'rules' => ['nullable', 'integer', 'between:1,50'], 'description' => 'Número máximo de imágenes.'],
        ];
    }

    public function getDefaultPayload(): array
    {
        return [
            'title' => 'Álbumes y Galerías',
            'album_slug' => '',
            'grid_columns' => '3',
            'limit' => 6,
        ];
    }

    public function keys(): array
    {
        return [$this->getType()];
    }

    public function ownerModule(): string
    {
        return 'gallery';
    }

    public function text(array $payload, Market $market, string $locale): string
    {
        $text = '';
        foreach ($this->items($payload, $market, $locale) as $item) {
            $text .= '- ['.strip_tags($item['title']).']('.$item['url'].")\n";
        }

        return $text;
    }

    /** @param array<string,mixed> $payload
     * @return array<int,array<string,string>>
     */
    private function items(array $payload, Market $market, string $locale): array
    {
        $discovery = app(PublicContentDiscoveryInterface::class);
        /** @var Builder<Gallery> $query */
        $query = $discovery->query('gallery', $market, $locale, ['items']);
        $slug = (string) ($payload['album_slug'] ?? '');
        $limit = min(50, max(1, (int) ($payload['limit'] ?? 6)));
        if ($slug !== '') {
            $gallery = $query->where(fn ($selection) => $selection->whereKey(is_numeric($slug) ? (int) $slug : 0)->orWhereHas('translations', fn ($translation) => $translation->where('locale', $locale)->where('slug', $slug)))->first();

            return $gallery?->items->take($limit)->map(static fn (GalleryItem $item): array => [
                'title' => (string) $item->title,
                'summary' => (string) ($item->caption ?? $item->alt_text),
                'image' => (string) $item->image_url,
                'url' => (string) $item->image_url,
            ])->values()->all() ?? [];
        }

        return $query->orderBy('id')->limit($limit)->get()->map(function (Gallery $gallery) use ($discovery, $market, $locale): array {
            $translation = $gallery->translations->firstWhere('locale', $locale);

            return [
                'title' => (string) $translation?->title,
                'summary' => (string) $translation?->description,
                'image' => (string) $gallery->items->first()?->image_url,
                'url' => $discovery->url('gallery', $market, $locale, (string) $translation?->slug),
                'cta_label' => 'Ver álbum',
            ];
        })->all();
    }

    public function render(array $payload, ?Market $market = null, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $market ??= app()->bound('current.market') ? app('current.market') : app(PublicContentDiscoveryInterface::class)->markets()->firstWhere('is_default', true);
        if (! $market instanceof Market) {
            return '';
        }
        $view = app(FrontViewResolver::class)->resolve('gallery.blocks.albums', 'gallery::blocks.albums');

        return view($view, [
            'items' => $this->items($payload, $market, $locale),
            'title' => (string) ($payload['title'] ?? ''),
            'columns' => SectionLayout::gridColumns($payload, '3'),
        ])->render();
    }
}
