<?php

namespace App\Actions\Admin;

use App\Models\Episode;

class DeleteEpisodeAction
{
    public function handle(Episode $episode): void
    {
        $episode->credits()->delete();
        $episode->delete();
        $episode->title?->delete();
    }
}
