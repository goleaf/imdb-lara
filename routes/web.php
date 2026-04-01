<?php

use App\Http\Controllers\Account\ListController as AccountListController;
use App\Http\Controllers\Account\WatchlistController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\TitleController as AdminTitleController;
use App\Http\Controllers\Auth\PageController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LatestReviewController;
use App\Http\Controllers\LatestTrailerController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TitleController;
use App\Http\Controllers\TopRatedMovieController;
use App\Http\Controllers\TopRatedSeriesController;
use App\Http\Controllers\TrendingController;
use App\Http\Controllers\UserListController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\YearController;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(
    "User-agent: *\nAllow: /\n\nSitemap: ".route('sitemap')."\n",
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8'],
))->name('robots');

Route::name('public.')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/discover', DiscoverController::class)->name('discover');
    Route::get('/movies', MovieController::class)->name('movies.index');
    Route::get('/tv-shows', SeriesController::class)->name('series.index');
    Route::get('/titles', [TitleController::class, 'index'])->name('titles.index');
    Route::get('/titles/{title:slug}', [TitleController::class, 'show'])->name('titles.show');
    Route::get('/people', [PersonController::class, 'index'])->name('people.index');
    Route::get('/people/{person:slug}', [PersonController::class, 'show'])->name('people.show');
    Route::get('/genres/{genre:slug}', GenreController::class)->name('genres.show');
    Route::get('/years/{year}', YearController::class)->whereNumber('year')->name('years.show');
    Route::get('/top-rated/movies', TopRatedMovieController::class)->name('rankings.movies');
    Route::get('/top-rated/series', TopRatedSeriesController::class)->name('rankings.series');
    Route::get('/trending', TrendingController::class)->name('trending');
    Route::get('/trailers/latest', LatestTrailerController::class)->name('trailers.latest');
    Route::get('/reviews/latest', LatestReviewController::class)->name('reviews.latest');
    Route::get('/search', SearchController::class)->name('search');
    Route::get('/u/{user:username}', UserProfileController::class)->name('users.show');

    Route::withoutScopedBindings()->group(function (): void {
        Route::get('/series/{series:slug}/seasons/{season:slug}', SeasonController::class)->name('seasons.show');
        Route::get('/series/{series:slug}/seasons/{season:slug}/episodes/{episode:slug}', EpisodeController::class)->name('episodes.show');
    });

    Route::scopeBindings()->group(function (): void {
        Route::get('/u/{user:username}/lists/{list:slug}', [UserListController::class, 'show'])->name('lists.show');
    });
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [PageController::class, 'login'])->name('login');
    Route::get('/register', [PageController::class, 'register'])->name('register');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::prefix('account')->name('account.')->group(function (): void {
        Route::get('/watchlist', WatchlistController::class)->name('watchlist');
        Route::get('/lists', AccountListController::class)->name('lists.index');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'admin'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/titles', [AdminTitleController::class, 'index'])
        ->can('viewAny', Title::class)
        ->name('titles.index');

    Route::middleware('moderate')->group(function (): void {
        Route::get('/reviews', [AdminReviewController::class, 'index'])
            ->can('viewAny', Review::class)
            ->name('reviews.index');
        Route::get('/reports', [AdminReportController::class, 'index'])
            ->can('viewAny', Report::class)
            ->name('reports.index');
    });
});
