<?php

namespace App\Actions\Admin;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Builder;

class BuildAdminMediaAssetsIndexQueryAction
{
    public function handle(): Builder
    {
        return MediaAsset::query()
            ->select([
                'id',
                'mediable_type',
                'mediable_id',
                'kind',
                'url',
                'alt_text',
                'caption',
                'width',
                'height',
                'provider',
                'provider_key',
                'duration_seconds',
                'metadata',
                'is_primary',
                'position',
                'published_at',
                'created_at',
            ])
            ->with('mediable')
            ->latest('created_at');
    }
}
