<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Services;

use DbCms\Core\Contracts\Services\LocalizationServiceInterface;
use DbCms\Core\Contracts\Services\PublicContentDiscoveryInterface;
use DbCms\Core\DTOs\Front\FrontPageData;
use DbCms\Core\Services\Cms\FrontViewResolver;
use DbCms\Core\Services\Content\PublicTranslation;
use DbCms\Core\Services\Seo\PublicContentModifiedService;
use DbCms\Core\Services\Seo\SeoMetaService;
use DbCms\Modules\Gallery\Contracts\GalleryFrontPageServiceInterface;
use DbCms\Modules\Gallery\DTOs\GalleryFrontInput;
use DbCms\Modules\Gallery\Models\Gallery;
use DbCms\Modules\Gallery\Models\GalleryTranslation;

final class GalleryFrontPageService implements GalleryFrontPageServiceInterface
{
    public function __construct(
        private readonly FrontViewResolver $views,
        private readonly LocalizationServiceInterface $localization,
        private readonly PublicContentDiscoveryInterface $discovery,
    ) {}

    public function index(GalleryFrontInput $input): FrontPageData
    {
        $market = $input->market;
        $locale = $input->locale;
        $localization = $this->localization;

        $galleries = $this->discovery->query('gallery', $market, $locale, ['items'])
            ->latest()
            ->paginate(12, ['*'], 'page', max(1, $input->page))
            ->withPath($input->absoluteUrl);

        $galPrefix = $localization->getModulePrefix('gallery', $locale);
        $alternateUrls = $localization->buildAlternateUrlsMapForPath(explode('/', trim((string) parse_url($input->absoluteUrl, PHP_URL_PATH), '/')));

        $breadcrumbs = [
            ['title' => $locale === 'es' ? 'Inicio' : ($locale === 'ca' ? 'Inici' : 'Home'), 'url' => $localization->url('/', $market)],
            ['title' => $locale === 'es' ? 'Galería' : ($locale === 'ca' ? 'Galeria' : 'Gallery'), 'url' => $localization->url("/{$galPrefix}", $market)],
        ];

        $viewName = $this->views->resolve('gallery.index', 'gallery::index');

        return FrontPageData::view($viewName, [
            'galleries' => $galleries,
            'galPrefix' => $galPrefix,
            'breadcrumbs' => $breadcrumbs,
            'alternateUrls' => $alternateUrls,
            'currentMarket' => $market,
        ]);
    }

    public function show(GalleryFrontInput $input): FrontPageData
    {
        $market = $input->market;
        $locale = $input->locale;
        $localization = $this->localization;
        $slug = $input->slug;

        $query = $this->discovery->query('gallery', $market, $locale, ['items']);
        PublicTranslation::constrainResolvedField($query, $market, $locale, 'slug', '=', (string) $slug);
        $gallery = $query->first();
        if (! $gallery instanceof Gallery) {
            return FrontPageData::missing();
        }

        $translation = PublicTranslation::resolve($gallery, $locale, $market);
        if (! $translation instanceof GalleryTranslation) {
            return FrontPageData::missing();
        }

        $galPrefix = $localization->getModulePrefix('gallery', $locale);
        $hreflangs = [];
        $alternateUrls = [];
        foreach ($this->discovery->markets() as $alternateMarket) {
            foreach ($alternateMarket->languages as $language) {
                $alternateLocale = (string) $language->code;
                if (! $this->discovery->query('gallery', $alternateMarket, $alternateLocale)->whereKey($gallery->id)->exists()) {
                    continue;
                }
                $alternate = PublicTranslation::resolve($gallery, $alternateLocale, $alternateMarket);
                if ($alternate === null) {
                    continue;
                }
                $url = $this->discovery->url('gallery', $alternateMarket, $alternateLocale, (string) $alternate->getAttribute('slug'));
                $hreflangs[strtolower($alternateLocale).'-'.strtoupper($alternateMarket->code)] = $url;
                $alternateUrls[$alternateMarket->code][$alternateLocale] = $url;
            }
        }
        if ($hreflangs !== []) {
            $hreflangs['x-default'] = $hreflangs['es-ES'] ?? reset($hreflangs);
        }

        $breadcrumbs = [
            ['title' => $locale === 'es' ? 'Inicio' : ($locale === 'ca' ? 'Inici' : 'Home'), 'url' => $localization->url('/', $market, $locale)],
            ['title' => $locale === 'es' ? 'Galería' : ($locale === 'ca' ? 'Galeria' : 'Gallery'), 'url' => $localization->url("/{$galPrefix}", $market, $locale)],
            ['title' => $translation->title, 'url' => $this->discovery->url('gallery', $market, $locale, $translation->slug)],
        ];

        $viewName = $this->views->resolve('gallery.show', 'gallery::show');
        $seoMeta = app(SeoMetaService::class)->forWebPage(
            title: (string) ($translation->meta_title ?: $translation->title),
            description: (string) ($translation->meta_description ?: $translation->description),
            canonical: $input->absoluteUrl,
            locale: $locale,
            entitySchema: app(GallerySeoSchemaService::class)->entity($gallery, $translation),
            modifiedAt: app(PublicContentModifiedService::class)->forEntity($gallery),
            breadcrumbs: array_map(static fn (array $crumb): array => ['name' => $crumb['title'], 'url' => $crumb['url']], $breadcrumbs),
            alternates: array_map(static fn (string $tag, string $href): array => ['hreflang' => $tag, 'href' => $href], array_keys($hreflangs), array_values($hreflangs)),
        );

        return FrontPageData::view($viewName, [
            'seoMeta' => $seoMeta,
            'gallery' => $gallery,
            'translation' => $translation,
            'items' => $gallery->items,
            'breadcrumbs' => $breadcrumbs,
            'hreflangs' => $hreflangs,
            'alternateUrls' => $alternateUrls,
            'currentMarket' => $market,
        ]);
    }
}
