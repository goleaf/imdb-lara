<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteEpisodeAction;
use App\Actions\Admin\SaveEpisodeAction;
use App\Http\Controllers\Admin\Concerns\BlocksCatalogOnlyAdminMutations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEpisodeRequest;
use App\Models\Episode;
use Illuminate\Http\RedirectResponse;

class EpisodeController extends Controller
{
    use BlocksCatalogOnlyAdminMutations;

    public function update(
        UpdateEpisodeRequest $request,
        Episode $episode,
        SaveEpisodeAction $saveEpisode,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $episode = $saveEpisode->handle($episode, $episode->season, $request->validated());

        return redirect()->route('admin.episodes.edit', $episode)->with('status', 'Episode updated.');
    }

    public function destroy(Episode $episode, DeleteEpisodeAction $deleteEpisode): RedirectResponse
    {
        $this->abortIfCatalogOnly();
        $this->authorize('delete', $episode);

        $season = $episode->season;
        $deleteEpisode->handle($episode);

        return redirect()->route('admin.seasons.edit', $season)->with('status', 'Episode deleted.');
    }
}
