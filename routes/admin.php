<?php

use App\Http\Controllers\Admin\CatalogAdminController;
use App\Http\Controllers\Admin\MediaAssetAdminController;
use App\Http\Controllers\Admin\ModerationAdminController;
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
            Route::post('/titles', [CatalogAdminController::class, 'storeTitle'])->name('titles.store');
            Route::patch('/titles/{title}', [CatalogAdminController::class, 'updateTitle'])->name('titles.update');
            Route::delete('/titles/{title}', [CatalogAdminController::class, 'destroyTitle'])->name('titles.destroy');
            Route::post('/titles/{title}/seasons', [CatalogAdminController::class, 'storeSeason'])->name('titles.seasons.store');

            Route::livewire('/people', PeoplePage::class)->name('people.index');
            Route::livewire('/people/create', PeoplePage::class)->name('people.create');
            Route::livewire('/people/{person}/edit', PeoplePage::class)->name('people.edit');
            Route::post('/people', [CatalogAdminController::class, 'storePerson'])->name('people.store');
            Route::patch('/people/{person}', [CatalogAdminController::class, 'updatePerson'])->name('people.update');
            Route::delete('/people/{person}', [CatalogAdminController::class, 'destroyPerson'])->name('people.destroy');
            Route::post('/people/{person}/professions', [CatalogAdminController::class, 'storePersonProfession'])->name('people.professions.store');
            Route::patch('/professions/{profession}', [CatalogAdminController::class, 'updateProfession'])->name('professions.update');
            Route::delete('/professions/{profession}', [CatalogAdminController::class, 'destroyProfession'])->name('professions.destroy');

            Route::livewire('/credits/create', CreditsPage::class)->name('credits.create');
            Route::livewire('/credits/{credit}/edit', CreditsPage::class)->name('credits.edit');
            Route::post('/credits', [CatalogAdminController::class, 'storeCredit'])->name('credits.store');
            Route::patch('/credits/{credit}', [CatalogAdminController::class, 'updateCredit'])->name('credits.update');
            Route::delete('/credits/{credit}', [CatalogAdminController::class, 'destroyCredit'])->name('credits.destroy');

            Route::livewire('/genres', GenresPage::class)->name('genres.index');
            Route::livewire('/genres/create', GenresPage::class)->name('genres.create');
            Route::livewire('/genres/{genre}/edit', GenresPage::class)->name('genres.edit');
            Route::post('/genres', [CatalogAdminController::class, 'storeGenre'])->name('genres.store');
            Route::patch('/genres/{genre}', [CatalogAdminController::class, 'updateGenre'])->name('genres.update');
            Route::delete('/genres/{genre}', [CatalogAdminController::class, 'destroyGenre'])->name('genres.destroy');

            Route::livewire('/seasons/{season}/edit', SeasonsPage::class)->name('seasons.edit');
            Route::patch('/seasons/{season}', [CatalogAdminController::class, 'updateSeason'])->name('seasons.update');
            Route::delete('/seasons/{season}', [CatalogAdminController::class, 'destroySeason'])->name('seasons.destroy');
            Route::post('/seasons/{season}/episodes', [CatalogAdminController::class, 'storeEpisode'])->name('seasons.episodes.store');

            Route::livewire('/episodes/{episode}/edit', EpisodesPage::class)->name('episodes.edit');
            Route::patch('/episodes/{episode}', [CatalogAdminController::class, 'updateEpisode'])->name('episodes.update');
            Route::delete('/episodes/{episode}', [CatalogAdminController::class, 'destroyEpisode'])->name('episodes.destroy');
        });

        Route::middleware('can:manage-media')->group(function (): void {
            Route::livewire('/media-assets', MediaAssetsPage::class)->name('media-assets.index');
            Route::livewire('/media-assets/{mediaAsset}/edit', MediaAssetsPage::class)->name('media-assets.edit');
            Route::post('/titles/{title}/media-assets', [MediaAssetAdminController::class, 'storeTitleMediaAsset'])->name('titles.media-assets.store');
            Route::post('/people/{person}/media-assets', [MediaAssetAdminController::class, 'storePersonMediaAsset'])->name('people.media-assets.store');
            Route::patch('/media-assets/{mediaAsset}', [MediaAssetAdminController::class, 'update'])->name('media-assets.update');
            Route::delete('/media-assets/{mediaAsset}', [MediaAssetAdminController::class, 'destroy'])->name('media-assets.destroy');
        });

        Route::middleware('can:moderate-content')->group(function (): void {
            Route::livewire('/reviews', ReviewsPage::class)->name('reviews.index');
            Route::livewire('/reports', ReportsPage::class)->name('reports.index');
            Route::patch('/reviews/{review}', [ModerationAdminController::class, 'updateReview'])->name('reviews.update');
            Route::patch('/reports/{report}', [ModerationAdminController::class, 'updateReport'])->name('reports.update');
        });

        Route::middleware('can:review-contribution')->group(function (): void {
            Route::livewire('/contributions', ContributionsPage::class)->name('contributions.index');
            Route::patch('/contributions/{contribution}', [ModerationAdminController::class, 'updateContribution'])->name('contributions.update');
        });
    });
