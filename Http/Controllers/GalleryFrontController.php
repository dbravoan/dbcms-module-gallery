<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Http\Controllers;

use DbCms\Core\Contracts\Services\LocalizationServiceInterface;
use DbCms\Core\Http\Controllers\Controller;
use DbCms\Modules\Gallery\Contracts\GalleryFrontPageServiceInterface;
use DbCms\Modules\Gallery\DTOs\GalleryFrontInput;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Compatibility transport for the established gallery routes. */
final class GalleryFrontController extends Controller
{
    public function __construct(private readonly GalleryFrontPageServiceInterface $pages, private readonly LocalizationServiceInterface $localization) {}

    public function index(Request $request): View
    {
        $data = $this->pages->index(GalleryFrontInput::fromRequest($request, $this->localization));
        abort_if($data->viewName === null, 404);

        return view($data->viewName, $data->data);
    }

    public function show(Request $request): View
    {
        $data = $this->pages->show(GalleryFrontInput::fromRequest($request, $this->localization));
        abort_if($data->viewName === null, 404);

        return view($data->viewName, $data->data);
    }
}
