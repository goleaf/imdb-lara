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

        View::composer(['layouts.account', 'layouts.partials.account-navbar', 'layouts.partials.account-sidebar'], function (ViewInstance $view) use ($buildTopNavigation): void {
            $user = auth()->user();
            $accountNavigationSections = $buildTopNavigation->forAccount();

            $view->with([
                'accountNavigationSections' => $accountNavigationSections,
                'accountNavbarItems' => $this->flattenNavigationItems($accountNavigationSections, 1),
                'portalUser' => $user,
            ]);
        });

        View::composer(['layouts.admin', 'layouts.partials.admin-navbar', 'layouts.partials.admin-sidebar'], function (ViewInstance $view) use ($buildTopNavigation): void {
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
            $adminNavigationSections = $buildTopNavigation->forAdmin($permissions);

            $view->with([
                'adminNavigationSections' => $adminNavigationSections,
                'adminNavbarItems' => $this->flattenNavigationItems($adminNavigationSections),
                'portalUser' => $user,
            ]);
        });

        View::composer('components.ui.footer', function (ViewInstance $view) use ($buildFooter): void {
            $view->with([
                'footerData' => $buildFooter->handle(),
            ]);
        });
    }

    /**
     * @param  list<array{label: string, items: list<array{href: string, label: string, icon: string, active: bool}>}>  $sections
     * @return list<array{href: string, label: string, icon: string, active: bool}>
     */
    private function flattenNavigationItems(array $sections, int $skipSections = 0): array
    {
        $navigationItems = collect($sections)
            ->slice($skipSections)
            ->pluck('items')
            ->flatten(1)
            ->values();

        if ($skipSections > 0 && $navigationItems->isEmpty()) {
            $navigationItems = collect($sections)
                ->pluck('items')
                ->flatten(1)
                ->values();
        }

        return $navigationItems->all();
    }
}
