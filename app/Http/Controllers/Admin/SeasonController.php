<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteSeasonAction;
use App\Actions\Admin\SaveEpisodeAction;
use App\Actions\Admin\SaveSeasonAction;
use App\Http\Controllers\Admin\Concerns\BlocksCatalogOnlyAdminMutations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEpisodeRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Models\Episode;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;

class SeasonController extends Controller
{
    use BlocksCatalogOnlyAdminMutations;

    public function update(
        UpdateSeasonRequest $request,
        Season $season,
        SaveSeasonAction $saveSeason,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $season = $saveSeason->handle($season, $season->series, $request->validated());

        return redirect()->route('admin.seasons.edit', $season)->with('status', 'Season updated.');
    }

    public function destroy(Season $season, DeleteSeasonAction $deleteSeason): RedirectResponse
    {
        $this->abortIfCatalogOnly();
        $this->authorize('delete', $season);

        $series = $season->series;
        $deleteSeason->handle($season);

        return redirect()->route('admin.titles.edit', $series)->with('status', 'Season deleted.');
    }

    public function storeEpisode(
        StoreEpisodeRequest $request,
        Season $season,
        SaveEpisodeAction $saveEpisode,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $saveEpisode->handle(new Episode, $season->loadMissing('series'), $request->validated()['episode']);

        return redirect()->route('admin.seasons.edit', $season)->with('status', 'Episode added.');
    }
}
