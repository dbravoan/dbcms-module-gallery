<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Services;

use DbCms\Core\Content\Contracts\PublicSemanticContributorInterface;
use DbCms\Core\Contracts\Services\PublicContentDiscoveryInterface;
use DbCms\Core\Models\Market;
use DbCms\Core\Services\Content\PublicTranslation;
use DbCms\Modules\Gallery\Models\Gallery;

final class GalleryPublicSemanticContributor implements PublicSemanticContributorInterface
{
    public function key(): string
    {
        return 'gallery';
    }

    public function ownerModule(): string
    {
        return 'gallery';
    }

    public function index(Market $market, string $locale): string
    {
        $discovery = app(PublicContentDiscoveryInterface::class);
        try {
            $galleries = $discovery->query('gallery', $market, $locale, ['translations', 'items'])
                ->get();

            if ($galleries->isEmpty()) {
                return '';
            }

            $output = "## Media Albums & Galleries\n";
            foreach ($galleries as $gallery) {
                /** @var Gallery $gallery */
                $trans = PublicTranslation::resolve($gallery, $locale, $market);
                if ($trans) {
                    $desc = ! empty($trans->getAttribute('description')) ? $trans->getAttribute('description') : 'Galería multimedia interactiva.';
                    $url = $discovery->url('gallery', $market, $locale, (string) $trans->getAttribute('slug'));
                    $output .= "- [{$trans->getAttribute('title')}]({$url}): {$desc} ({$gallery->items->count()} elementos)\n";
                }
            }

            return $output."\n";
        } catch (\Throwable) {
            return '';
        }
    }

    public function corpus(Market $market, string $locale): string
    {
        $output = '';
        foreach (app(PublicContentDiscoveryInterface::class)->query('gallery', $market, $locale, ['items'])->get() as $gallery) {
            /** @var Gallery $gallery */
            $translation = PublicTranslation::resolve($gallery, $locale, $market);
            if ($translation === null) {
                continue;
            }
            $output .= "## Gallery: {$translation->getAttribute('title')}\n";
            $output .= '**Source**: '.app(PublicContentDiscoveryInterface::class)->url('gallery', $market, $locale, (string) $translation->getAttribute('slug'))."\n\n";
            $output .= strip_tags((string) $translation->getAttribute('description'))."\n\n";
            foreach ($gallery->items as $item) {
                $output .= '- '.strip_tags((string) ($item->title ?: $item->alt_text)).': '.strip_tags((string) $item->caption)."\n";
            }
            $output .= "\n---\n\n";
        }

        return $output;
    }
}
