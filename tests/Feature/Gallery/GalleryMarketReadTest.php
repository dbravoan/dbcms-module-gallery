<?php

declare(strict_types=1);

use DbCms\Core\Contracts\Services\PublicContentDiscoveryInterface;
use DbCms\Core\Models\Market;
use DbCms\Modules\Gallery\Contracts\GalleryFrontPageServiceInterface;
use DbCms\Modules\Gallery\DTOs\GalleryFrontInput;
use DbCms\Modules\Gallery\Models\Gallery;
use DbCms\Modules\Gallery\Search\GallerySearchSource;
use DbCms\Modules\Gallery\Services\GalleryPublicSemanticContributor;
use Tests\Support\CmsFixtures;
use Tests\Support\GallerySearchComposition;

uses(GallerySearchComposition::class);

beforeEach(function (): void {
    CmsFixtures::localization(['es', 'en', 'fr'], ['eu', 'us']);
    $this->market = Market::where('code', 'eu')->sole();
    $this->otherMarket = Market::where('code', 'us')->sole();
    $this->gallery = Gallery::create(['is_active' => true, 'title' => 'Internal gallery', 'slug' => 'internal-gallery']);
    $this->gallery->translations()->create(['market_id' => $this->otherMarket->id, 'locale' => 'es', 'title' => 'Ajeno secreto', 'slug' => 'ajeno-secreto', 'description' => 'Descripcion ajena']);
    $this->gallery->translations()->create(['locale' => 'es', 'title' => 'Titulo global', 'slug' => 'global', 'description' => 'Descripcion global']);
    $this->gallery->translations()->create(['market_id' => $this->market->id, 'locale' => 'es', 'title' => 'Titulo regional', 'slug' => 'regional', 'description' => '']);
    $this->gallery->translations()->create(['market_id' => $this->otherMarket->id, 'locale' => 'en', 'title' => 'Other language market', 'slug' => 'other-language']);
    $this->gallery->translations()->create(['locale' => 'fr', 'title' => 'Titre francais', 'slug' => 'francais']);
});

test('gallery detail uses the resolved market slug and inherits only its global language fields', function (): void {
    $service = app(GalleryFrontPageServiceInterface::class);
    $show = $service->show(new GalleryFrontInput($this->market, 'es', url('/eu/es/galeria/regional'), 'regional'));
    expect($show->viewName)->not->toBeNull()
        ->and($show->data['translation']->title)->toBe('Titulo regional')
        ->and($show->data['translation']->description)->toBe('Descripcion global')
        ->and($show->data['breadcrumbs'][2]['url'])->toEndWith('/eu/es/galeria/regional');
    foreach (['global', 'ajeno-secreto'] as $slug) {
        expect($service->show(new GalleryFrontInput($this->market, 'es', url('/eu/es/galeria/'.$slug), $slug))->viewName)->toBeNull();
    }
    expect($show->data['hreflangs']['es-EU'])->toEndWith('/eu/es/galeria/regional')
        ->and($show->data['hreflangs']['es-US'])->toEndWith('/us/es/galeria/ajeno-secreto')
        ->and($show->data['hreflangs'])->not->toHaveKey('en-EU')
        ->and($show->data['alternateUrls']['eu'])->not->toHaveKey('en');
});

test('blank regional slugs inherit global slugs and another market or language never substitutes them', function (): void {
    $this->gallery->translations()->where('market_id', $this->market->id)->update(['slug' => '']);
    $service = app(GalleryFrontPageServiceInterface::class);
    $show = $service->show(new GalleryFrontInput($this->market, 'es', url('/eu/es/galeria/global'), 'global'));
    expect($show->viewName)->not->toBeNull()->and($show->data['translation']->title)->toBe('Titulo regional');
    expect($service->show(new GalleryFrontInput($this->market, 'en', url('/eu/en/gallery/other-language'), 'other-language'))->viewName)->toBeNull();
    expect($service->show(new GalleryFrontInput($this->market, 'en', url('/eu/en/gallery/francais'), 'francais'))->viewName)->toBeNull();
});

test('gallery discovery and semantic readers share the same market translation and canonical URL', function (): void {
    $discovery = app(PublicContentDiscoveryInterface::class);
    expect($discovery->entityUrl('gallery', $this->gallery->fresh('translations'), $this->market, 'es'))->toEndWith('/eu/es/galeria/regional');
    expect($discovery->query('gallery', $this->market, 'en')->whereKey($this->gallery->id)->exists())->toBeFalse();
    $reader = app(GalleryPublicSemanticContributor::class);
    foreach ([$reader->index($this->market, 'es'), $reader->corpus($this->market, 'es')] as $text) {
        expect($text)->toContain('Titulo regional')->toContain('Descripcion global')->toContain('/eu/es/galeria/regional')
            ->not->toContain('Ajeno secreto')->not->toContain('Descripcion ajena')->not->toContain('ajeno-secreto');
    }
    expect($reader->index($this->market, 'en'))->toBe('')->and($reader->corpus($this->market, 'en'))->toBe('');
});

test('gallery search emits one resolved document for each public market and language', function (): void {
    $documents = collect(app(GallerySearchSource::class)->documentsFor($this->gallery->id));
    $regional = $documents->where('market', 'eu')->where('locale', 'es');
    expect($regional)->toHaveCount(1)->and($regional->sole()->title)->toBe('Titulo regional')
        ->and($regional->sole()->url)->toEndWith('/eu/es/galeria/regional')
        ->and($regional->sole()->body)->toContain('Descripcion global');
    expect($documents->where('market', 'eu')->where('locale', 'en'))->toHaveCount(0);
    expect($documents->where('market', 'us')->where('locale', 'es'))->toHaveCount(1);
});

test('gallery reads support global or regional rows alone and hide rows belonging only to another market', function (string $variant, ?string $slug, ?string $title): void {
    if ($variant !== 'global') {
        $this->gallery->translations()->where('locale', 'es')->whereNull('market_id')->delete();
    }
    if ($variant !== 'regional') {
        $this->gallery->translations()->where('locale', 'es')->where('market_id', $this->market->id)->delete();
    }
    $visible = app(PublicContentDiscoveryInterface::class)->query('gallery', $this->market, 'es')->whereKey($this->gallery->id)->exists();
    $documents = collect(app(GallerySearchSource::class)->documentsFor($this->gallery->id))->where('market', 'eu')->where('locale', 'es');
    $show = app(GalleryFrontPageServiceInterface::class)->show(new GalleryFrontInput($this->market, 'es', url('/eu/es/galeria/'.($slug ?? 'ajeno-secreto')), $slug ?? 'ajeno-secreto'));
    if ($slug === null) {
        expect($visible)->toBeFalse()->and($documents)->toHaveCount(0)->and($show->viewName)->toBeNull();
        expect(app(GalleryPublicSemanticContributor::class)->corpus($this->market, 'es'))->toBe('');

        return;
    }
    expect($visible)->toBeTrue()->and($show->viewName)->not->toBeNull()
        ->and($show->data['translation']->title)->toBe($title)
        ->and($documents)->toHaveCount(1)->and($documents->sole()->url)->toEndWith('/eu/es/galeria/'.$slug);
})->with([
    'global only' => ['global', 'global', 'Titulo global'],
    'regional only' => ['regional', 'regional', 'Titulo regional'],
    'foreign market only' => ['foreign', null, null],
]);
