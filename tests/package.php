<?php

declare(strict_types=1);

return [
    'laravel' => [
        'version' => '13.11.0',
        'reference' => '06d016a364a37430eec9cbc52209adce3d7667ce',
    ],
    'kind' => 'module',
    'core' => '0.0.1',
    'fixtureVersion' => '0.0.1',
    'theme' => 'default-clean',
    'adminTheme' => 'dbcms',
    'require' => [],
    'sourceDirs' => [
        '.',
    ],
    'testPaths' => [
        'Regression/GalleryEditorRevisionTest.php',
        'Architecture/GallerySearchFrontBoundaryTest.php',
        'Feature/Gallery/GalleryMarketReadTest.php',
        'Feature/Search/SearchObserversStrictModelTest.php',
        'Feature/Search/PageGallerySourcesTest.php',
        'Feature/Cms/GalleryModuleLocalizedRoutingTest.php',
    ],
    'modules' => [
        'gallery',
    ],
    'blockPackages' => [],
    'privacyProcessing' => [],
    'preserveRows' => [],
    'profiles' => [
        'integration' => [
            'require' => [
                'dbravoan/dbcms-module-content-audit' => '0.0.1',
            ],
            'testPaths' => [
                'Feature/Gallery/GalleryMutationParityTest.php',
                'Feature/Gallery/GalleryEditorialPreviewTest.php',
            ],
        ],
    ],
];
