<?php

namespace App\Actions\Admin;

use App\Models\Title;

class DeleteTitleAction
{
    public function __construct(
        private DeleteEpisodeAction $deleteEpisode,
        private DeleteSeasonAction $deleteSeason,
    ) {}

    public function handle(Title $title): void
    {
        $title->credits()->delete();
        $title->mediaAssets()->delete();

        if ($title->episodeMeta()->exists()) {
            $this->deleteEpisode->handle($title->episodeMeta()->with('title')->firstOrFail());

            return;
        }

        $title->seasons()
            ->with('episodes.title')
            ->get()
            ->each(fn ($season) => $this->deleteSeason->handle($season));

        $title->delete();
    }
}
