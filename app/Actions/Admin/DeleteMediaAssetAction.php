<?php

namespace App\Actions\Admin;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;

class DeleteMediaAssetAction
{
    public function handle(MediaAsset $mediaAsset): void
    {
        $disk = $mediaAsset->storageDisk();
        $path = $mediaAsset->storagePath();

        $mediaAsset->delete();

        if ($disk !== null && $path !== null) {
            Storage::disk($disk)->delete($path);
        }
    }
}
