<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteMediaAssetAction;
use App\Actions\Admin\SaveMediaAssetAction;
use App\Http\Controllers\Admin\Concerns\BlocksCatalogOnlyAdminMutations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMediaAssetRequest;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;

class MediaAssetController extends Controller
{
    use BlocksCatalogOnlyAdminMutations;

    public function update(
        UpdateMediaAssetRequest $request,
        MediaAsset $mediaAsset,
        SaveMediaAssetAction $saveMediaAsset,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $mediable = $mediaAsset->mediable ?? $mediaAsset->mediable()->firstOrFail();
        $mediaAsset = $saveMediaAsset->handle($mediaAsset, $mediable, $request->validated());

        return redirect()->route('admin.media-assets.edit', $mediaAsset)->with('status', 'Media asset updated.');
    }

    public function destroy(MediaAsset $mediaAsset, DeleteMediaAssetAction $deleteMediaAsset): RedirectResponse
    {
        $this->abortIfCatalogOnly();
        $this->authorize('delete', $mediaAsset);

        $redirectUrl = $mediaAsset->adminAttachedEditUrl() ?? route('admin.media-assets.index');
        $deleteMediaAsset->handle($mediaAsset);

        return redirect()->to($redirectUrl)->with('status', 'Media asset deleted.');
    }
}
