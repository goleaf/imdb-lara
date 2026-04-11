<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteMediaAssetAction;
use App\Actions\Admin\SaveMediaAssetAction;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Http\Requests\Admin\UpdateMediaAssetRequest;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class MediaAssetAdminController
{
    use AuthorizesRequests;

    public function storeTitleMediaAsset(
        StoreMediaAssetRequest $request,
        Title $title,
        SaveMediaAssetAction $saveMediaAsset,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $saveMediaAsset->handle(new MediaAsset, $title, $request->validated());

        return redirect()
            ->route('admin.titles.edit', $title)
            ->with('status', 'Media asset added.');
    }

    public function storePersonMediaAsset(
        StoreMediaAssetRequest $request,
        Person $person,
        SaveMediaAssetAction $saveMediaAsset,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $saveMediaAsset->handle(new MediaAsset, $person, $request->validated());

        return redirect()
            ->route('admin.people.edit', $person)
            ->with('status', 'Media asset added.');
    }

    public function update(
        UpdateMediaAssetRequest $request,
        MediaAsset $mediaAsset,
        SaveMediaAssetAction $saveMediaAsset,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $mediable = $mediaAsset->mediable;

        if (! $mediable instanceof Model) {
            abort(404);
        }

        $mediaAsset = $saveMediaAsset->handle($mediaAsset, $mediable, $request->validated());

        return redirect()
            ->route('admin.media-assets.edit', $mediaAsset)
            ->with('status', 'Media asset updated.');
    }

    public function destroy(MediaAsset $mediaAsset, DeleteMediaAssetAction $deleteMediaAsset): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();
        $this->authorize('delete', $mediaAsset);

        $mediable = $mediaAsset->mediable;
        $deleteMediaAsset->handle($mediaAsset);

        return match (true) {
            $mediable instanceof Title => redirect()
                ->route('admin.titles.edit', $mediable)
                ->with('status', 'Media asset deleted.'),
            $mediable instanceof Person => redirect()
                ->route('admin.people.edit', $mediable)
                ->with('status', 'Media asset deleted.'),
            default => redirect()
                ->route('admin.media-assets.index')
                ->with('status', 'Media asset deleted.'),
        };
    }

    private function ensureCatalogWritesEnabled(): void
    {
        abort_if((bool) config('screenbase.catalog_only', false), 501);
    }
}
