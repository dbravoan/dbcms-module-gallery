<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Contracts;

use DbCms\Core\DTOs\Front\FrontPageData;
use DbCms\Modules\Gallery\DTOs\GalleryFrontInput;

interface GalleryFrontPageServiceInterface
{
    public function index(GalleryFrontInput $input): FrontPageData;

    public function show(GalleryFrontInput $input): FrontPageData;
}
