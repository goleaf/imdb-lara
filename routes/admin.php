<?php

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
            Route::livewire('/titles', TitlesIndexPage::class)->name('titles.index');
            Route::livewire('/titles/create', TitleCreatePage::class)->name('titles.create');
            Route::livewire('/titles/{title}/edit', TitleEditPage::class)->name('titles.edit');

            Route::livewire('/people', PeopleIndexPage::class)->name('people.index');
            Route::livewire('/people/create', PersonCreatePage::class)->name('people.create');
            Route::livewire('/people/{person}/edit', PersonEditPage::class)->name('people.edit');

            Route::livewire('/credits/create', CreditsPage::class)->name('credits.create');
            Route::livewire('/credits/{credit}/edit', CreditsPage::class)->name('credits.edit');

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

            Route::livewire('/seasons/{season}/edit', SeasonsPage::class)->name('seasons.edit');
            Route::livewire('/episodes/{episode}/edit', EpisodesPage::class)->name('episodes.edit');
        });

        Route::middleware('can:manage-media')->group(function (): void {
            Route::livewire('/media-assets', MediaAssetsIndexPage::class)->name('media-assets.index');
            Route::livewire('/media-assets/{mediaAsset}/edit', MediaAssetEditPage::class)->name('media-assets.edit');
        });

        Route::middleware('can:moderate-content')->group(function (): void {
            Route::livewire('/reviews', ReviewsPage::class)->name('reviews.index');
            Route::livewire('/reports', ReportsPage::class)->name('reports.index');
        });

        Route::middleware('can:review-contribution')->group(function (): void {
            Route::livewire('/contributions', ContributionsPage::class)->name('contributions.index');
        });
    });
