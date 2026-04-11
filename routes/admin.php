<?php

use App\Actions\Admin\UpdateContributionStatusAction;
use App\Actions\Admin\UpdateReportStatusAction;
use App\Actions\Moderation\ModerateReviewAction;
use App\Enums\ReviewStatus;
use App\Http\Requests\Admin\UpdateContributionRequest;
use App\Http\Requests\Admin\UpdateReportRequest;
use App\Http\Requests\Admin\UpdateReviewModerationRequest;
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
use App\Models\Contribution;
use App\Models\Report;
use App\Models\Review;
use Illuminate\Support\Facades\Route;

$catalogWriteUnavailable = static function () {
    abort(501, 'Catalog write workflows are not enabled in the current catalog-only mode.');
};

Route::middleware(['auth', 'active', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () use ($catalogWriteUnavailable): void {
        Route::livewire('/', DashboardPage::class)->name('dashboard');

        Route::middleware('can:manage-catalog')->group(function () use ($catalogWriteUnavailable): void {
            Route::livewire('/titles', TitlesPage::class)->name('titles.index');
            Route::livewire('/titles/create', TitlesPage::class)->name('titles.create');
            Route::livewire('/titles/{title}/edit', TitlesPage::class)->name('titles.edit');
            Route::post('/titles', $catalogWriteUnavailable)->name('titles.store');
            Route::patch('/titles/{title}', $catalogWriteUnavailable)->name('titles.update');
            Route::delete('/titles/{title}', $catalogWriteUnavailable)->name('titles.destroy');
            Route::post('/titles/{title}/seasons', $catalogWriteUnavailable)->name('titles.seasons.store');

            Route::livewire('/people', PeoplePage::class)->name('people.index');
            Route::livewire('/people/create', PeoplePage::class)->name('people.create');
            Route::livewire('/people/{person}/edit', PeoplePage::class)->name('people.edit');
            Route::post('/people', $catalogWriteUnavailable)->name('people.store');
            Route::patch('/people/{person}', $catalogWriteUnavailable)->name('people.update');
            Route::delete('/people/{person}', $catalogWriteUnavailable)->name('people.destroy');
            Route::post('/people/{person}/professions', $catalogWriteUnavailable)->name('people.professions.store');
            Route::patch('/professions/{profession}', $catalogWriteUnavailable)->name('professions.update');
            Route::delete('/professions/{profession}', $catalogWriteUnavailable)->name('professions.destroy');

            Route::livewire('/credits/create', CreditsPage::class)->name('credits.create');
            Route::livewire('/credits/{credit}/edit', CreditsPage::class)->name('credits.edit');
            Route::post('/credits', $catalogWriteUnavailable)->name('credits.store');
            Route::patch('/credits/{credit}', $catalogWriteUnavailable)->name('credits.update');
            Route::delete('/credits/{credit}', $catalogWriteUnavailable)->name('credits.destroy');

            Route::livewire('/genres', GenresPage::class)->name('genres.index');
            Route::livewire('/genres/create', GenresPage::class)->name('genres.create');
            Route::livewire('/genres/{genre}/edit', GenresPage::class)->name('genres.edit');
            Route::post('/genres', $catalogWriteUnavailable)->name('genres.store');
            Route::patch('/genres/{genre}', $catalogWriteUnavailable)->name('genres.update');
            Route::delete('/genres/{genre}', $catalogWriteUnavailable)->name('genres.destroy');

            Route::livewire('/seasons/{season}/edit', SeasonsPage::class)->name('seasons.edit');
            Route::patch('/seasons/{season}', $catalogWriteUnavailable)->name('seasons.update');
            Route::delete('/seasons/{season}', $catalogWriteUnavailable)->name('seasons.destroy');
            Route::post('/seasons/{season}/episodes', $catalogWriteUnavailable)->name('seasons.episodes.store');

            Route::livewire('/episodes/{episode}/edit', EpisodesPage::class)->name('episodes.edit');
            Route::patch('/episodes/{episode}', $catalogWriteUnavailable)->name('episodes.update');
            Route::delete('/episodes/{episode}', $catalogWriteUnavailable)->name('episodes.destroy');
        });

        Route::middleware('can:manage-media')->group(function () use ($catalogWriteUnavailable): void {
            Route::livewire('/media-assets', MediaAssetsPage::class)->name('media-assets.index');
            Route::livewire('/media-assets/{mediaAsset}/edit', MediaAssetsPage::class)->name('media-assets.edit');
            Route::post('/titles/{title}/media-assets', $catalogWriteUnavailable)->name('titles.media-assets.store');
            Route::post('/people/{person}/media-assets', $catalogWriteUnavailable)->name('people.media-assets.store');
            Route::patch('/media-assets/{mediaAsset}', $catalogWriteUnavailable)->name('media-assets.update');
            Route::delete('/media-assets/{mediaAsset}', $catalogWriteUnavailable)->name('media-assets.destroy');
        });

        Route::middleware('can:moderate-content')->group(function (): void {
            Route::livewire('/reviews', ReviewsPage::class)->name('reviews.index');
            Route::patch('/reviews/{review}', function (
                UpdateReviewModerationRequest $request,
                Review $review,
                ModerateReviewAction $moderateReview,
            ) {
                $moderateReview->handle(
                    $request->user(),
                    $review,
                    ReviewStatus::from((string) $request->validated('status')),
                    $request->validated('moderation_notes'),
                );

                return to_route('admin.reviews.index')->with('status', 'Review moderation saved.');
            })->name('reviews.update');

            Route::livewire('/reports', ReportsPage::class)->name('reports.index');
            Route::patch('/reports/{report}', function (
                UpdateReportRequest $request,
                Report $report,
                UpdateReportStatusAction $updateReportStatus,
            ) {
                $updateReportStatus->handle(
                    $request->user(),
                    $report,
                    $request->validated(),
                );

                return to_route('admin.reports.index')->with('status', 'Report moderation saved.');
            })->name('reports.update');
        });

        Route::middleware('can:review-contribution')->group(function (): void {
            Route::livewire('/contributions', ContributionsPage::class)->name('contributions.index');
            Route::patch('/contributions/{contribution}', function (
                UpdateContributionRequest $request,
                Contribution $contribution,
                UpdateContributionStatusAction $updateContributionStatus,
            ) {
                $updateContributionStatus->handle(
                    $request->user(),
                    $contribution,
                    $request->validated(),
                );

                return to_route('admin.contributions.index')->with('status', 'Contribution review saved.');
            })->name('contributions.update');
        });
    });
