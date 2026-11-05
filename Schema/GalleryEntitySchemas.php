<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Schema;

use DbCms\Core\Content\EntitySchema;
use DbCms\Core\Content\PublicContentDefinition;
use DbCms\Modules\Gallery\Models\Gallery;
use DbCms\Modules\Gallery\Services\GalleryEditorialPreview;
use DbCms\Modules\Gallery\Services\GalleryMutationHandler;

/**
 * Declares the Gallery entities a generic consumer may operate on.
 */
final class GalleryEntitySchemas
{
    /**
     * @return list<EntitySchema>
     */
    public static function all(): array
    {
        return [self::gallery()];
    }

    private static function gallery(): EntitySchema
    {
        return new EntitySchema(
            type: 'gallery',
            publicContent: new PublicContentDefinition('galleries', 'fa-solid fa-images', includeHub: true),
            label: 'Galería',
            model: Gallery::class,
            description: 'Álbum multimedia localizado con título, descripción y metadatos.',
            permission: 'manage:galleries',
            previewHandler: GalleryEditorialPreview::class,
            mutationHandler: GalleryMutationHandler::class,
            urlPrefix: 'gallery',
            createView: 'admin.galleries.create',
            adminFormView: 'admin.galleries._form',
            editView: 'admin.galleries.edit',
            fields: [
                'schema_type' => ['input' => 'translations[{locale}][schema_type]', 'label' => 'Tipo de schema'],
                EntitySchema::ROLE_TITLE => ['input' => 'translations[{locale}][title]', 'label' => 'Título', 'required' => true],
                EntitySchema::ROLE_SLUG => ['input' => 'translations[{locale}][slug]', 'label' => 'Slug'],
                EntitySchema::ROLE_BODY => ['input' => 'translations[{locale}][description]', 'label' => 'Descripción', 'multiline' => true],
                EntitySchema::ROLE_META_DESCRIPTION => ['input' => 'translations[{locale}][meta_description]', 'label' => 'Meta descripción', 'multiline' => true],
                EntitySchema::ROLE_META_TITLE => ['input' => 'translations[{locale}][meta_title]', 'label' => 'Meta título'],
            ],
        );
    }
}
