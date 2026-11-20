<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class);

it('opens a gallery without a pending revision under strict attribute access', function (): void {
    $previous = Model::preventsAccessingMissingAttributes();
    Model::preventAccessingMissingAttributes();
    $gallery = new Gallery;
    $gallery->exists = true;
    $gallery->setRawAttributes(['id' => 1, 'is_active' => true]);
    $source = file_get_contents(InstalledVersions::getInstallPath('dbravoan/dbcms-module-gallery').'/Resources/views/admin/galleries/_form.blade.php');
    preg_match('/@if\([^\n]*pending_revision_id[^\n]*\).*?@endif/s', $source, $match);
    try {
        expect(trim(Blade::render($match[0], ['gallery' => $gallery])))->toBe('');
    } finally {
        Model::preventAccessingMissingAttributes($previous);
    }
});
