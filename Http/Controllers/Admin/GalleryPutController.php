<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Http\Controllers\Admin;

use DbCms\Core\Http\Controllers\Controller;
use DbCms\Modules\Gallery\Contracts\GalleryEditorialServiceInterface;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class GalleryPutController extends Controller
{
    public function __invoke(Request $request, Gallery $gallery, GalleryEditorialServiceInterface $service): RedirectResponse
    {
        $service->update($gallery, $request->all());

        return redirect()->route('admin.galleries.index')->with('success', 'Álbum actualizado correctamente.');
    }
}
