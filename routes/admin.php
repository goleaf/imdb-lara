<?php

use App\Http\Controllers\Admin\CreditController;
use App\Http\Controllers\Admin\EpisodeController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\PersonProfessionController;
use App\Http\Controllers\Admin\SeasonController;
use App\Http\Controllers\Admin\TitleController;
use App\Livewire\Pages\Admin\AkaAttributeCreatePage;
use App\Livewire\Pages\Admin\AkaAttributeEditPage;
use App\Livewire\Pages\Admin\AkaAttributesIndexPage;
use App\Livewire\Pages\Admin\AkaTypeCreatePage;
use App\Livewire\Pages\Admin\AkaTypeEditPage;
use App\Livewire\Pages\Admin\AkaTypesIndexPage;
use App\Livewire\Pages\Admin\AwardCategoriesIndexPage;
use App\Livewire\Pages\Admin\AwardCategoryCreatePage;
use App\Livewire\Pages\Admin\AwardCategoryEditPage;
use App\Livewire\Pages\Admin\ContributionsPage;
use App\Livewire\Pages\Admin\CreditsPage;
use App\Livewire\Pages\Admin\DashboardPage;
use App\Livewire\Pages\Admin\EpisodesPage;
use App\Livewire\Pages\Admin\GenreCreatePage;
use App\Livewire\Pages\Admin\GenreEditPage;
use App\Livewire\Pages\Admin\GenresIndexPage;
use App\Livewire\Pages\Admin\MediaAssetEditPage;
use App\Livewire\Pages\Admin\MediaAssetsIndexPage;
use App\Livewire\Pages\Admin\PeopleIndexPage;
use App\Livewire\Pages\Admin\PersonCreatePage;
use App\Livewire\Pages\Admin\PersonEditPage;
use App\Livewire\Pages\Admin\ReportsPage;
use App\Livewire\Pages\Admin\ReviewsPage;
use App\Livewire\Pages\Admin\SeasonsPage;
use App\Livewire\Pages\Admin\TitleCreatePage;
use App\Livewire\Pages\Admin\TitleEditPage;
use App\Livewire\Pages\Admin\TitlesIndexPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::livewire('/', DashboardPage::class)->name('dashboard');

        Route::middleware('can:manage-catalog')->group(function (): void {
            Route::post('/titles', [TitleController::class, 'store'])->name('titles.store');
            Route::match(['patch', 'put'], '/titles/{title}', [TitleController::class, 'update'])->name('titles.update');
            Route::delete('/titles/{title}', [TitleController::class, 'destroy'])->name('titles.destroy');
            Route::post('/titles/{title}/seasons', [TitleController::class, 'storeSeason'])->name('titles.seasons.store');
            Route::livewire('/titles', TitlesIndexPage::class)->name('titles.index');
            Route::livewire('/titles/create', TitleCreatePage::class)->name('titles.create');
            Route::livewire('/titles/{title}/edit', TitleEditPage::class)->name('titles.edit');

            Route::post('/people', [PersonController::class, 'store'])->name('people.store');
            Route::match(['patch', 'put'], '/people/{person}', [PersonController::class, 'update'])->name('people.update');
            Route::delete('/people/{person}', [PersonController::class, 'destroy'])->name('people.destroy');
            Route::post('/people/{person}/professions', [PersonController::class, 'storeProfession'])->name('people.professions.store');
            Route::livewire('/people', PeopleIndexPage::class)->name('people.index');
            Route::livewire('/people/create', PersonCreatePage::class)->name('people.create');
            Route::livewire('/people/{person}/edit', PersonEditPage::class)->name('people.edit');

            Route::match(['patch', 'put'], '/professions/{profession}', [PersonProfessionController::class, 'update'])->name('professions.update');
            Route::delete('/professions/{profession}', [PersonProfessionController::class, 'destroy'])->name('professions.destroy');

            Route::post('/credits', [CreditController::class, 'store'])->name('credits.store');
            Route::match(['patch', 'put'], '/credits/{credit}', [CreditController::class, 'update'])->name('credits.update');
            Route::delete('/credits/{credit}', [CreditController::class, 'destroy'])->name('credits.destroy');
            Route::livewire('/credits/create', CreditsPage::class)->name('credits.create');
            Route::livewire('/credits/{credit}/edit', CreditsPage::class)->name('credits.edit');

            Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
            Route::match(['patch', 'put'], '/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
            Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');
            Route::livewire('/genres', GenresIndexPage::class)->name('genres.index');
            Route::livewire('/genres/create', GenreCreatePage::class)->name('genres.create');
            Route::livewire('/genres/{genre}/edit', GenreEditPage::class)->name('genres.edit');

            Route::livewire('/aka-attributes', AkaAttributesIndexPage::class)->name('aka-attributes.index');
            Route::livewire('/aka-attributes/create', AkaAttributeCreatePage::class)->name('aka-attributes.create');
            Route::livewire('/aka-attributes/{akaAttribute}/edit', AkaAttributeEditPage::class)->name('aka-attributes.edit');

            Route::livewire('/aka-types', AkaTypesIndexPage::class)->name('aka-types.index');
            Route::livewire('/aka-types/create', AkaTypeCreatePage::class)->name('aka-types.create');
            Route::livewire('/aka-types/{akaType}/edit', AkaTypeEditPage::class)->name('aka-types.edit');

            Route::livewire('/award-categories', AwardCategoriesIndexPage::class)->name('award-categories.index');
            Route::livewire('/award-categories/create', AwardCategoryCreatePage::class)->name('award-categories.create');
            Route::livewire('/award-categories/{awardCategory}/edit', AwardCategoryEditPage::class)->name('award-categories.edit');

            Route::match(['patch', 'put'], '/seasons/{season}', [SeasonController::class, 'update'])->name('seasons.update');
            Route::delete('/seasons/{season}', [SeasonController::class, 'destroy'])->name('seasons.destroy');
            Route::post('/seasons/{season}/episodes', [SeasonController::class, 'storeEpisode'])->name('seasons.episodes.store');
            Route::livewire('/seasons/{season}/edit', SeasonsPage::class)->name('seasons.edit');

            Route::match(['patch', 'put'], '/episodes/{episode}', [EpisodeController::class, 'update'])->name('episodes.update');
            Route::delete('/episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');
            Route::livewire('/episodes/{episode}/edit', EpisodesPage::class)->name('episodes.edit');
        });

        Route::middleware('can:manage-media')->group(function (): void {
            Route::post('/titles/{title}/media-assets', [TitleController::class, 'storeMediaAsset'])->name('titles.media-assets.store');
            Route::post('/people/{person}/media-assets', [PersonController::class, 'storeMediaAsset'])->name('people.media-assets.store');
            Route::match(['patch', 'put'], '/media-assets/{mediaAsset}', [MediaAssetController::class, 'update'])->name('media-assets.update');
            Route::delete('/media-assets/{mediaAsset}', [MediaAssetController::class, 'destroy'])->name('media-assets.destroy');
            Route::livewire('/media-assets', MediaAssetsIndexPage::class)->name('media-assets.index');
            Route::livewire('/media-assets/{mediaAsset}/edit', MediaAssetEditPage::class)->name('media-assets.edit');
        });

        Route::middleware('can:moderate-content')->group(function (): void {
            Route::match(['patch', 'put'], '/reviews/{review}', [ModerationController::class, 'updateReview'])->name('reviews.update');
            Route::match(['patch', 'put'], '/reports/{report}', [ModerationController::class, 'updateReport'])->name('reports.update');
            Route::livewire('/reviews', ReviewsPage::class)->name('reviews.index');
            Route::livewire('/reports', ReportsPage::class)->name('reports.index');
        });

        Route::middleware('can:review-contribution')->group(function (): void {
            Route::match(['patch', 'put'], '/contributions/{contribution}', [ModerationController::class, 'updateContribution'])->name('contributions.update');
            Route::livewire('/contributions', ContributionsPage::class)->name('contributions.index');
        });
    });
