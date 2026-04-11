<?php

namespace Tests\Unit\Actions\Lists;

use App\Actions\Lists\GetAccountWatchlistFilterOptionsAction;
use App\Enums\WatchState;
use PHPUnit\Framework\TestCase;

class GetAccountWatchlistFilterOptionsActionTest extends TestCase
{
    public function test_state_options_include_icons_for_watchlist_filter_controls(): void
    {
        $action = new GetAccountWatchlistFilterOptionsAction;

        $this->assertSame([
            ['value' => 'all', 'label' => 'All titles', 'icon' => 'squares-2x2'],
            ['value' => 'watched', 'label' => 'Watched', 'icon' => 'check-circle'],
            ['value' => 'unwatched', 'label' => 'Unwatched', 'icon' => 'eye'],
            ['value' => WatchState::Planned->value, 'label' => 'Planned', 'icon' => WatchState::Planned->icon()],
            ['value' => WatchState::Watching->value, 'label' => 'Watching', 'icon' => WatchState::Watching->icon()],
            ['value' => WatchState::Completed->value, 'label' => 'Completed', 'icon' => WatchState::Completed->icon()],
            ['value' => WatchState::Paused->value, 'label' => 'Paused', 'icon' => WatchState::Paused->icon()],
            ['value' => WatchState::Dropped->value, 'label' => 'Dropped', 'icon' => WatchState::Dropped->icon()],
        ], $action->stateOptions());
    }

    public function test_sort_options_include_icons_for_watchlist_filter_controls(): void
    {
        $action = new GetAccountWatchlistFilterOptionsAction;

        $this->assertSame([
            ['value' => 'added', 'label' => 'Date added', 'icon' => 'calendar-days'],
            ['value' => 'year', 'label' => 'Release year', 'icon' => 'calendar-days'],
            ['value' => 'rating', 'label' => 'Rating', 'icon' => 'star'],
            ['value' => 'title', 'label' => 'Title', 'icon' => 'bars-arrow-down'],
        ], $action->sortOptions());
    }
}
