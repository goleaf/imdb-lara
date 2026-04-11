<?php

namespace App\Livewire\Pages\Public;

class TopRatedSeriesPage extends BrowseTitlesPage
{
    protected function browsePageKey(): string
    {
        return 'top-rated-series';
    }
}
