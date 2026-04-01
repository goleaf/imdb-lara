<?php

namespace App\Actions\Admin;

use App\Models\Season;

class DeleteSeasonAction
{
    public function __construct(private DeleteEpisodeAction $deleteEpisode) {}

    public function handle(Season $season): void
    {
        $season->episodes()
            ->with('title')
            ->get()
            ->each(fn ($episode) => $this->deleteEpisode->handle($episode));

        $season->delete();
    }
}
