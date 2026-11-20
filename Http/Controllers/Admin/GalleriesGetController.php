<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Http\Controllers\Admin;

use DbCms\Core\DTOs\Admin\DatatableQueryData;
use DbCms\Core\Http\Controllers\Controller;
use DbCms\Core\Models\Market;
use DbCms\Core\Services\Admin\DatatableQueryService;
use DbCms\Modules\Gallery\Models\Gallery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class GalleriesGetController extends Controller
{
    public function __invoke(Request $request, DatatableQueryService $tables): View
    {
        $query = Gallery::with(['translations', 'items', 'markets'])->withCount('items');

        $galleries = $tables->paginate(
            $query,
            DatatableQueryData::fromInput($request->query()),
            ['translations.title', 'slug'],
            [
                'is_active' => 'is_active',
                'market_id' => 'markets.id',
            ],
            'created_at',
            'desc'
        );

        $markets = Market::where('is_active', true)->get();

        return view('admin.galleries.index', [
            'galleries' => $galleries,
            'markets' => $markets,
        ]);
    }
}
