<?php

declare(strict_types=1);

use DbCms\Core\Http\Middleware\CheckPermission;
use DbCms\Modules\Gallery\Http\Controllers\Admin\GalleriesGetController;
use DbCms\Modules\Gallery\Http\Controllers\Admin\GalleryCreateGetController;
use DbCms\Modules\Gallery\Http\Controllers\Admin\GalleryDeleteController;
use DbCms\Modules\Gallery\Http\Controllers\Admin\GalleryEditGetController;
use DbCms\Modules\Gallery\Http\Controllers\Admin\GalleryPostController;
use DbCms\Modules\Gallery\Http\Controllers\Admin\GalleryPutController;
use Illuminate\Support\Facades\Route;

Route::middleware([CheckPermission::class.':manage:galleries'])->prefix('galleries')->name('galleries.')->group(function (): void {
    Route::get('/', GalleriesGetController::class)->name('index');
    Route::get('/create', GalleryCreateGetController::class)->name('create');
    Route::post('/', GalleryPostController::class)->name('store');
    Route::get('/{gallery}/edit', GalleryEditGetController::class)->name('edit');
    Route::put('/{gallery}', GalleryPutController::class)->name('update');
    Route::delete('/{gallery}', GalleryDeleteController::class)->name('destroy');
});
