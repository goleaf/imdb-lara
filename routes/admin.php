<?php

use App\Livewire\Pages\Admin\ContributionsPage;
use App\Livewire\Pages\Admin\CreditsPage;
use App\Livewire\Pages\Admin\DashboardPage;
use App\Livewire\Pages\Admin\EpisodesPage;
use App\Livewire\Pages\Admin\GenresPage;
use App\Livewire\Pages\Admin\MediaAssetsPage;
use App\Livewire\Pages\Admin\PeoplePage;
use App\Livewire\Pages\Admin\ReportsPage;
use App\Livewire\Pages\Admin\ReviewsPage;
use App\Livewire\Pages\Admin\SeasonsPage;
use App\Livewire\Pages\Admin\TitlesPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::livewire('/', DashboardPage::class)->name('dashboard');

        Route::middleware('can:manage-catalog')->group(function (): void {
            Route::livewire('/titles', TitlesPage::class)->name('titles.index');
            Route::livewire('/titles/create', TitlesPage::class)->name('titles.create');
            Route::livewire('/titles/{title}/edit', TitlesPage::class)->name('titles.edit');

            Route::livewire('/people', PeoplePage::class)->name('people.index');
            Route::livewire('/people/create', PeoplePage::class)->name('people.create');
            Route::livewire('/people/{person}/edit', PeoplePage::class)->name('people.edit');

            Route::livewire('/credits/create', CreditsPage::class)->name('credits.create');
            Route::livewire('/credits/{credit}/edit', CreditsPage::class)->name('credits.edit');

            Route::livewire('/genres', GenresPage::class)->name('genres.index');
            Route::livewire('/genres/create', GenresPage::class)->name('genres.create');
            Route::livewire('/genres/{genre}/edit', GenresPage::class)->name('genres.edit');

            Route::livewire('/seasons/{season}/edit', SeasonsPage::class)->name('seasons.edit');

            Route::livewire('/episodes/{episode}/edit', EpisodesPage::class)->name('episodes.edit');
        });

        Route::middleware('can:manage-media')->group(function (): void {
            Route::livewire('/media-assets', MediaAssetsPage::class)->name('media-assets.index');
            Route::livewire('/media-assets/{mediaAsset}/edit', MediaAssetsPage::class)->name('media-assets.edit');
        });

        Route::middleware('can:moderate-content')->group(function (): void {
            Route::livewire('/reviews', ReviewsPage::class)->name('reviews.index');
            Route::livewire('/reports', ReportsPage::class)->name('reports.index');
        });

        Route::middleware('can:review-contribution')->group(function (): void {
            Route::livewire('/contributions', ContributionsPage::class)->name('contributions.index');
        });
    });
