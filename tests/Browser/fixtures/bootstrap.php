<?php

declare(strict_types=1);

use DbCms\Core\Models\Language;
use DbCms\Core\Models\Market;
use DbCms\Core\Models\User;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$root = getenv('DBCMS_BROWSER_APP') ?: dirname(__DIR__, 3).'/.build/app';
if (getenv('DBCMS_BROWSER_FIXTURE') !== '1'
    || ! is_file($root.'/.dbcms-browser-disposable')
    || trim((string) file_get_contents($root.'/.dbcms-browser-disposable')) !== 'dbcms-module-browser'
    || preg_match('/^dbcms_browser_[a-z0-9_]+$/D', (string) getenv('DB_DATABASE')) !== 1) {
    throw new RuntimeException('Requires a marked disposable application and a dedicated browser database.');
}
putenv('CMS_MODULES=gallery');
$_ENV['CMS_MODULES'] = $_SERVER['CMS_MODULES'] = 'gallery';
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (DB::connection()->getDatabaseName() !== getenv('DB_DATABASE') || DB::transactionLevel() !== 0) {
    throw new RuntimeException('The browser fixture must use its committed disposable database.');
}
$processing = array_map(static fn (string $owner): array => [
    'owner' => $owner, 'purpose' => 'Disposable browser acceptance fixture', 'legal_basis' => 'Requested test service',
    'data' => 'Synthetic test data', 'retention' => 'Deleted after acceptance', 'retention_days' => 1,
    'recipients' => 'Disposable local application', 'transfers' => 'External browser requests are intercepted',
], ['core', 'forms', 'executions']);
$companyFile = storage_path('browser-company.json');
file_put_contents($companyFile, json_encode([
    'company_name' => 'Browser fixture SL', 'tax_id' => 'TEST-BROWSER', 'activity' => 'Synthetic acceptance',
    'address' => 'Test street 1, Madrid', 'country' => 'ES', 'contact_email' => 'contact@example.test',
    'privacy_email' => 'privacy@example.test', 'hosting_provider' => 'Disposable Docker fixture', 'hosting_country' => 'ES',
    'processing' => $processing, 'reviewed' => true,
], JSON_THROW_ON_ERROR));
$exit = Artisan::call('cms:install', [
    '--db-host' => getenv('DB_HOST'), '--db-port' => getenv('DB_PORT') ?: '3306', '--db-database' => getenv('DB_DATABASE'),
    '--db-username' => getenv('DB_USERNAME'), '--db-password' => getenv('DB_PASSWORD'), '--without-demo' => true,
    '--admin-name' => 'Browser administrator', '--admin-email' => 'browser@example.test', '--admin-password' => 'browser-only-password',
    '--company-file' => $companyFile, '--site-name' => 'Module browser acceptance', '--brand' => 'dbcms', '--theme' => 'default-clean', '--force' => true,
]);
echo Artisan::output();
if ($exit !== 0) {
    throw new RuntimeException('The disposable browser installation failed.');
}
$user = User::where('email', 'browser@example.test')->sole();
$spanish = Language::firstOrCreate(['code' => 'es'], ['name' => 'Español', 'native_name' => 'Español', 'is_default' => true, 'is_active' => true]);
$english = Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English', 'is_default' => false, 'is_active' => true]);
$market = Market::where('is_default', true)->firstOrFail();
$market->update(['code' => 'es', 'default_locale' => 'es', 'is_active' => true]);
$market->languages()->sync([$spanish->id => ['is_default' => true], $english->id => ['is_default' => false]]);
$fixtures = ['modules' => ['gallery']];
$other = Market::firstOrCreate(['code' => 'uk'], ['name' => 'Other test market', 'currency' => 'EUR', 'default_locale' => 'es', 'is_active' => true]);
$other->languages()->sync([$spanish->id => ['is_default' => true], $english->id => ['is_default' => false]]);
file_put_contents(public_path('browser-fixture.svg'), '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="90"><rect width="160" height="90" fill="navy"/></svg>');
$public = Gallery::create(['is_active' => true, 'title' => 'Internal gallery fixture', 'slug' => 'internal-gallery']);
$public->translations()->createMany([
    ['locale' => 'es', 'title' => 'Browser global gallery', 'slug' => 'browser-global', 'description' => 'Inherited gallery description'],
    ['locale' => 'es', 'market_id' => $other->id, 'title' => 'Foreign gallery fixture', 'slug' => 'browser-foreign', 'description' => 'Other market only'],
    ['locale' => 'es', 'market_id' => $market->id, 'title' => 'Browser regional gallery', 'slug' => 'browser-regional', 'description' => ''],
    ['locale' => 'en', 'title' => 'Browser English gallery', 'slug' => 'browser-english', 'description' => 'English gallery description'],
]);
$public->items()->create(['image_url' => '/browser-fixture.svg', 'title' => 'Browser image', 'alt_text' => 'Synthetic blue square']);
$fixtures += ['publicGallery' => '/es/es/galeria/browser-regional', 'publicForeignSlug' => '/es/es/galeria/browser-foreign'];
foreach (['dbcms', 'termocms'] as $brand) {
    $gallery = Gallery::create(['is_active' => false, 'title' => 'Browser gallery '.$brand, 'slug' => 'browser-gallery-'.$brand]);
    $gallery->translations()->createMany([
        ['locale' => 'es', 'title' => 'Browser gallery '.$brand, 'slug' => 'browser-gallery-'.$brand],
        ['locale' => 'en', 'title' => 'Browser gallery English '.$brand, 'slug' => 'browser-gallery-en-'.$brand],
    ]);
    $gallery->items()->create(['image_url' => '/browser-fixture.svg', 'title' => 'Preserved image', 'alt_text' => 'Synthetic blue square']);
    $gallery->markets()->sync([$market->id]);
    $fixtures[$brand] = ['gallery' => $gallery->id];
}
file_put_contents(storage_path('browser-fixtures.json'), json_encode($fixtures, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo "Browser fixture initialized.\n";
