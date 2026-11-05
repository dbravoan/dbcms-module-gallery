<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Http\Controllers\Admin;

use DbCms\Core\Http\Controllers\Controller;
use DbCms\Modules\Gallery\Contracts\GalleryEditorialServiceInterface;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Http\RedirectResponse;

final class GalleryDeleteController extends Controller
{
    public function __invoke(Gallery $gallery, GalleryEditorialServiceInterface $service): RedirectResponse
    {
        $service->delete($gallery);

        return redirect()->route('admin.galleries.index')->with('success', 'Álbum eliminado.');
    }
}
