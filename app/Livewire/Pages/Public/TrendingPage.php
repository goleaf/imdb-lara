<?php

namespace App\Livewire\Pages\Public;

class TrendingPage extends BrowseTitlesPage
{
    protected function browsePageKey(): string
    {
        return 'trending';
    }
}
