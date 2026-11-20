<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Services;

use DbCms\Core\Content\Contracts\EditorialPreviewHandlerInterface;
use DbCms\Core\DTOs\Front\FrontPageData;
use DbCms\Core\DTOs\Front\PublicContentContext;
use DbCms\Core\Services\Cms\FrontViewResolver;
use DbCms\Core\Services\Content\PublicTranslation;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Database\Eloquent\Model;

final class GalleryEditorialPreview implements EditorialPreviewHandlerInterface
{
    public function render(Model $entity, PublicContentContext $context): FrontPageData
    {
        if (! $entity instanceof Gallery) {
            return FrontPageData::missing();
        }
        $translation = PublicTranslation::resolve($entity, $context->locale, $context->market);
        if ($translation === null) {
            return FrontPageData::missing();
        }

        return FrontPageData::view(app(FrontViewResolver::class)->resolve('gallery.show', 'gallery::show'), [
            'gallery' => $entity,
            'translation' => $translation,
            'items' => $entity->getRelation('items'),
            'breadcrumbs' => [],
            'hreflangs' => [],
            'alternateUrls' => [],
            'currentMarket' => $context->market,
            'currentLocale' => $context->locale,
        ]);
    }
}
