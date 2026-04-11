<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminMediaAssetsIndexQueryAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\MediaAsset;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MediaAssetsPage extends Component
{
    use RendersPageView;

    public ?MediaAsset $mediaAsset = null;

    public function mount(?MediaAsset $mediaAsset = null): void
    {
        $this->mediaAsset = $mediaAsset;
    }

    protected function renderMediaAssetsIndexPage(BuildAdminMediaAssetsIndexQueryAction $buildAdminMediaAssetsIndexQuery): View
    {
        return $this->renderPageView('admin.media-assets.index', [
            'mediaAssets' => $buildAdminMediaAssetsIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }

    protected function renderMediaAssetEditPage(): View
    {
        abort_unless($this->mediaAsset instanceof MediaAsset, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.media-assets.edit', [
                'mediaAsset' => $this->mediaAsset,
            ]);
        }

        return $this->renderPageView('admin.media-assets.edit', [
            'mediaAsset' => $this->mediaAsset->load('mediable'),
        ]);
    }
}
