<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function (ViewInstance $view): void {
            $user = auth()->user();

            $view->with([
                'canViewAdminTitles' => $user?->can('viewAny', Title::class) ?? false,
                'canViewAdminReviews' => $user?->can('viewAny', Review::class) ?? false,
                'canViewAdminReports' => $user?->can('viewAny', Report::class) ?? false,
            ]);
        });
    }
}
