<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminMediaAssetsIndexQueryAction;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\MediaAsset;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class MediaAssetsPage extends Component
{
    use RendersLegacyPage;
    use WithPagination;

    public ?MediaAsset $mediaAsset = null;

    public function mount(?MediaAsset $mediaAsset = null): void
    {
        $this->mediaAsset = $mediaAsset;
    }

    public function render(BuildAdminMediaAssetsIndexQueryAction $buildAdminMediaAssetsIndexQuery): View
    {
        if (request()->routeIs('admin.media-assets.index')) {
            return $this->renderLegacyPage('admin.media-assets.index', [
                'mediaAssets' => $buildAdminMediaAssetsIndexQuery
                    ->handle()
                    ->simplePaginate(20)
                    ->withQueryString(),
            ]);
        }

        abort_unless($this->mediaAsset instanceof MediaAsset, 404);

        return $this->renderLegacyPage('admin.media-assets.edit', [
            'mediaAsset' => $this->mediaAsset->load('mediable'),
        ]);
    }
}
