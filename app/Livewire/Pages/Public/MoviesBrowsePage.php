<?php

namespace App\Livewire\Pages\Public;

class MoviesBrowsePage extends BrowseTitlesPage
{
    protected function browsePageKey(): string
    {
        return 'movies';
    }
}
