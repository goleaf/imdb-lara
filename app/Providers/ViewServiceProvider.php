<?php

namespace App\Providers;

use App\Models\Contribution;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(['layouts.public', 'layouts.partials.public-navbar', 'home'], function (ViewInstance $view): void {
            $view->with([
                'hasPublicMoviesRoute' => Route::has('public.movies.index'),
                'hasPublicSeriesRoute' => Route::has('public.series.index'),
                'hasPublicListsRoute' => Route::has('public.lists.index'),
                'hasPublicAwardsRoute' => Route::has('public.awards.index'),
                'hasPublicTrendingRoute' => Route::has('public.trending'),
                'hasPublicLatestTrailersRoute' => Route::has('public.trailers.latest'),
                'hasPublicLatestReviewsRoute' => Route::has('public.reviews.latest'),
            ]);
        });

        View::composer(['layouts.admin', 'layouts.partials.admin-navbar'], function (ViewInstance $view): void {
            $user = auth()->user();

            $view->with([
                'canViewAdminTitles' => $user?->can('viewAny', Title::class) ?? false,
                'canViewAdminPeople' => $user?->can('viewAny', Person::class) ?? false,
                'canViewAdminGenres' => $user?->can('viewAny', Genre::class) ?? false,
                'canViewAdminMediaAssets' => $user?->can('viewAny', MediaAsset::class) ?? false,
                'canViewAdminContributions' => $user?->can('viewAny', Contribution::class) ?? false,
                'canViewAdminReviews' => $user?->can('viewAny', Review::class) ?? false,
                'canViewAdminReports' => $user?->can('viewAny', Report::class) ?? false,
            ]);
        });
    }
}
