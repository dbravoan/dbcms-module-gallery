<?php

declare(strict_types=1);

use DbCms\Core\Content\Pages\Models\Page;
use DbCms\Core\Content\Pages\Models\PageSection;
use DbCms\Core\Content\Pages\Models\PageTranslation;
use DbCms\Core\Content\Pages\Models\SectionTranslation;
use DbCms\Core\Content\Pages\Observers\PageSearchObserver;
use DbCms\Modules\Gallery\Models\Gallery;
use DbCms\Modules\Gallery\Models\GalleryItem;
use DbCms\Modules\Gallery\Models\GalleryTranslation;
use DbCms\Modules\Gallery\Observers\GallerySearchObserver;
use Illuminate\Database\Eloquent\Model;
use Tests\Support\GalleryRoutingComposition;

uses(GalleryRoutingComposition::class);

test('page observer resolves the section translation parent with strict missing attributes', function (): void {
    $page = Page::withoutEvents(fn (): Page => Page::create(['is_published' => false, 'template' => 'default']));
    $section = PageSection::withoutEvents(fn (): PageSection => $page->sections()->create(['type' => 'rich_text', 'is_active' => true, 'status' => 'draft', 'order' => 0]));
    $method = new ReflectionMethod(PageSearchObserver::class, 'entityId');
    $previous = Model::preventsAccessingMissingAttributes();
    Model::preventAccessingMissingAttributes();
    try {
        foreach ([
            new Page,
            new PageTranslation(['page_id' => $page->getKey()]),
            new PageSection(['page_id' => $page->getKey()]),
            new SectionTranslation(['page_section_id' => $section->getKey()]),
        ] as $model) {
            if ($model instanceof Page) {
                $model->setAttribute('id', $page->getKey());
            }
            $model->exists = true;
            expect($method->invoke(new PageSearchObserver, $model))->toBe($page->getKey());
        }
        $partial = new SectionTranslation;
        $partial->exists = true;
        expect($method->invoke(new PageSearchObserver, $partial))->toBeNull();
    } finally {
        Model::preventAccessingMissingAttributes($previous);
    }
});

test('gallery observer resolves only its own real keys with strict missing attributes', function (): void {
    $method = new ReflectionMethod(GallerySearchObserver::class, 'entityId');
    $previous = Model::preventsAccessingMissingAttributes();
    Model::preventAccessingMissingAttributes();
    try {
        $gallery = new Gallery;
        $gallery->setAttribute('id', 17);
        foreach ([$gallery, new GalleryTranslation(['gallery_id' => 17]), new GalleryItem(['gallery_id' => 17])] as $model) {
            $model->exists = true;
            expect($method->invoke(new GallerySearchObserver, $model))->toBe(17);
        }
        $partial = new GalleryItem;
        $partial->exists = true;
        expect($method->invoke(new GallerySearchObserver, $partial))->toBeNull();
    } finally {
        Model::preventAccessingMissingAttributes($previous);
    }
});
