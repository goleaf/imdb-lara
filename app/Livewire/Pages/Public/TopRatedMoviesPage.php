<?php

namespace App\Livewire\Pages\Public;

class TopRatedMoviesPage extends BrowseTitlesPage
{
    protected function browsePageKey(): string
    {
        return 'top-rated-movies';
    }
}
