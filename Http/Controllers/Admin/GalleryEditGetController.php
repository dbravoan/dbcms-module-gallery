<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Http\Controllers\Admin;

use DbCms\Core\Http\Controllers\Controller;
use DbCms\Modules\Gallery\Contracts\GalleryEditorialServiceInterface;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Contracts\View\View;

final class GalleryEditGetController extends Controller
{
    public function __invoke(Gallery $gallery, GalleryEditorialServiceInterface $service): View
    {
        return view('admin.galleries.edit', $service->editData($gallery));
    }
}
