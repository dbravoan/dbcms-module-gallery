<?php

declare(strict_types=1);

namespace DbCms\Modules\Gallery\Observers;

use DbCms\Core\Search\SearchEntityObserver;
use DbCms\Modules\Gallery\Models\Gallery;
use DbCms\Modules\Gallery\Models\GalleryItem;
use DbCms\Modules\Gallery\Models\GalleryTranslation;
use Illuminate\Database\Eloquent\Model;

final class GallerySearchObserver extends SearchEntityObserver
{
    protected function type(): string
    {
        return 'gallery';
    }

    protected function entityId(Model $model): int|string|null
    {
        $attributes = $model->getAttributes();
        $id = match (true) {
            $model instanceof Gallery => $attributes[$model->getKeyName()] ?? null,
            $model instanceof GalleryTranslation, $model instanceof GalleryItem => $attributes['gallery_id'] ?? null,
            default => null,
        };

        return is_int($id) || is_string($id) ? $id : null;
    }
}
