<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteTitleAction;
use App\Actions\Admin\SaveMediaAssetAction;
use App\Actions\Admin\SaveSeasonAction;
use App\Actions\Admin\StoreTitleAction;
use App\Actions\Admin\UpdateTitleAction;
use App\Http\Controllers\Admin\Concerns\BlocksCatalogOnlyAdminMutations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\StoreTitleRequest;
use App\Http\Requests\Admin\UpdateTitleRequest;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;

class TitleController extends Controller
{
    use BlocksCatalogOnlyAdminMutations;

    public function store(StoreTitleRequest $request, StoreTitleAction $storeTitle): RedirectResponse
    {
        $this->abortIfCatalogOnly();

        $title = $storeTitle->handle($request->validated());

        return redirect()->route('admin.titles.edit', $title)->with('status', 'Title created.');
    }

    public function update(
        UpdateTitleRequest $request,
        Title $title,
        UpdateTitleAction $updateTitle,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $title = $updateTitle->handle($title, $request->validated());

        return redirect()->route('admin.titles.edit', $title)->with('status', 'Title updated.');
    }

    public function destroy(Title $title, DeleteTitleAction $deleteTitle): RedirectResponse
    {
        $this->abortIfCatalogOnly();
        $this->authorize('delete', $title);

        $deleteTitle->handle($title);

        return redirect()->route('admin.titles.index')->with('status', 'Title deleted.');
    }

    public function storeSeason(
        StoreSeasonRequest $request,
        Title $title,
        SaveSeasonAction $saveSeason,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $saveSeason->handle(new Season, $title, $request->validated()['season']);

        return redirect()->route('admin.titles.edit', $title)->with('status', 'Season added.');
    }

    public function storeMediaAsset(
        StoreMediaAssetRequest $request,
        Title $title,
        SaveMediaAssetAction $saveMediaAsset,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $saveMediaAsset->handle(new MediaAsset, $title, $request->validated());

        return redirect()->route('admin.titles.edit', $title)->with('status', 'Media asset added.');
    }
}
