<?php

use App\Http\Controllers\Account\WatchlistController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\TitleController as AdminTitleController;
use App\Http\Controllers\Auth\PageController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TitleController;
use Illuminate\Support\Facades\Route;

Route::name('public.')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/discover', DiscoverController::class)->name('discover');
    Route::get('/titles', [TitleController::class, 'index'])->name('titles.index');
    Route::get('/titles/{title:slug}', [TitleController::class, 'show'])->name('titles.show');
    Route::get('/people', [PersonController::class, 'index'])->name('people.index');
    Route::get('/people/{person:slug}', [PersonController::class, 'show'])->name('people.show');
    Route::get('/search', SearchController::class)->name('search');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [PageController::class, 'login'])->name('login');
    Route::get('/register', [PageController::class, 'register'])->name('register');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::prefix('account')->name('account.')->group(function (): void {
        Route::get('/watchlist', WatchlistController::class)->name('watchlist');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'admin'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/titles', [AdminTitleController::class, 'index'])->name('titles.index');
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
});
