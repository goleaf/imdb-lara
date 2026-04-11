<?php

use App\Livewire\Pages\Account\DashboardPage;
use App\Livewire\Pages\Account\ListsPage;
use App\Livewire\Pages\Account\SettingsPage;
use App\Livewire\Pages\Account\WatchlistPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])
    ->prefix('account')
    ->name('account.')
    ->group(function (): void {
        Route::livewire('/', DashboardPage::class)->name('dashboard');
        Route::livewire('/settings', SettingsPage::class)->name('settings');
        Route::livewire('/watchlist', WatchlistPage::class)->name('watchlist');
        Route::livewire('/lists', ListsPage::class)->name('lists.index');
        Route::livewire('/lists/{list:slug}', ListsPage::class)->name('lists.show');
    });
