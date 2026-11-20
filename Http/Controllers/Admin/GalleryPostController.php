<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Http\Controllers\Admin;

use DbCms\Core\Http\Controllers\Controller;
use DbCms\Modules\Gallery\Contracts\GalleryEditorialServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class GalleryPostController extends Controller
{
    public function __invoke(Request $request, GalleryEditorialServiceInterface $service): RedirectResponse
    {
        $gallery = $service->create($request->all());

        return redirect()->route('admin.galleries.index')->with('success', "Álbum {$gallery->title} creado exitosamente.");
    }
}
