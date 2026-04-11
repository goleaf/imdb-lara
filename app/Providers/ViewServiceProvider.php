<?php

namespace App\Providers;

use App\Actions\Layout\BuildFooterAction;
use App\Actions\Layout\BuildTopNavigationAction;
use App\Models\Contribution;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(BuildTopNavigationAction $buildTopNavigation, BuildFooterAction $buildFooter): void
    {
        View::composer(['layouts.public', 'layouts.partials.public-navbar', 'home'], function (ViewInstance $view) use ($buildTopNavigation): void {
            $view->with([
                'publicNavigationSections' => $buildTopNavigation->forPublic(),
            ]);
        });

        View::composer(['layouts.account', 'layouts.partials.account-navbar'], function (ViewInstance $view) use ($buildTopNavigation): void {
            $view->with([
                'accountNavigationSections' => $buildTopNavigation->forAccount(),
            ]);
        });

        View::composer(['layouts.admin', 'layouts.partials.admin-navbar'], function (ViewInstance $view) use ($buildTopNavigation): void {
            $user = auth()->user();
            $permissions = [
                'canViewAdminTitles' => $user?->can('viewAny', Title::class) ?? false,
                'canViewAdminPeople' => $user?->can('viewAny', Person::class) ?? false,
                'canViewAdminGenres' => $user?->can('viewAny', Genre::class) ?? false,
                'canViewAdminMediaAssets' => $user?->can('viewAny', MediaAsset::class) ?? false,
                'canViewAdminContributions' => $user?->can('viewAny', Contribution::class) ?? false,
                'canViewAdminReviews' => $user?->can('viewAny', Review::class) ?? false,
                'canViewAdminReports' => $user?->can('viewAny', Report::class) ?? false,
            ];

            $view->with([
                'adminNavigationSections' => $buildTopNavigation->forAdmin($permissions),
            ]);
        });

        View::composer('components.ui.footer', function (ViewInstance $view) use ($buildFooter): void {
            $view->with([
                'footerData' => $buildFooter->handle(),
            ]);
        });
    }
}
