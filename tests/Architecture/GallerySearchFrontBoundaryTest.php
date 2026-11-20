<?php

declare(strict_types=1);

use DbCms\Core\Search\Http\Controllers\SearchFrontController;
use DbCms\Modules\Gallery\Http\Controllers\GalleryFrontController;
use Tests\Support\ArchitectureBoundaryInspector;
use Tests\Support\ArchitectureSources;

test('gallery and search transports delegate queries and domain presentation', function (): void {
    $guard = new ArchitectureBoundaryInspector;
    foreach ([GalleryFrontController::class, SearchFrontController::class] as $class) {
        expect($guard->dependencyViolations(ArchitectureSources::forClass($class), ['DbCms\\Modules\\Gallery\\Models\\Gallery', 'DbCms\\Modules\\Gallery\\Services\\GallerySeoSchemaService', 'DbCms\\Core\\Search\\SearchEngine', 'DbCms\\Core\\Search\\SearchIndexer', 'DbCms\\Core\\Search\\Services\\SearchAnalyticsService']), $class)->toBe([]);
    }
});
