<?php

namespace App\Actions\Layout;

use Illuminate\Support\Facades\Route;

class BuildTopNavigationAction
{
    /**
     * @return list<array{
     *     label: string,
     *     items: list<array{
     *         href: string,
     *         label: string,
     *         icon: string,
     *         active: bool
     *     }>
     * }>
     */
    public function forPublic(): array
    {
        return array_values(array_filter([
            $this->section('Start', [
                $this->item('public.home', 'Home', 'home'),
                $this->item('public.discover', 'Discovery', 'sparkles'),
                $this->item('public.catalog.explorer', 'Explorer', 'rectangle-stack'),
            ]),
            $this->section('Browse', [
                $this->item('public.titles.index', 'Titles', 'film', [
                    'public.titles.*',
                    'public.genres.*',
                    'public.years.*',
                ]),
                $this->item('public.movies.index', 'Movies', 'film', [
                    'public.movies.*',
                    'public.rankings.movies',
                ]),
                $this->item('public.series.index', 'TV Shows', 'tv', [
                    'public.series.*',
                    'public.seasons.*',
                    'public.episodes.*',
                    'public.rankings.series',
                ]),
                $this->item('public.people.index', 'People', 'users', [
                    'public.people.*',
                ]),
                $this->item('public.interest-categories.index', 'Themes', 'squares-2x2', [
                    'public.interest-categories.*',
                ]),
            ]),
            $this->section('Tools', [
                $this->item('public.awards.index', 'Awards', 'trophy', [
                    'public.awards.*',
                ]),
                $this->item('public.trending', 'Charts', 'bolt', [
                    'public.trending',
                    'public.rankings.*',
                ]),
                $this->item('public.trailers.latest', 'Trailers', 'play', [
                    'public.trailers.*',
                ]),
                $this->item('public.search', 'Search', 'funnel'),
            ]),
        ]));
    }

    /**
     * @return list<array{
     *     label: string,
     *     items: list<array{
     *         href: string,
     *         label: string,
     *         icon: string,
     *         active: bool
     *     }>
     * }>
     */
    public function forAccount(): array
    {
        return array_values(array_filter([
            $this->section('Account', [
                $this->item('account.dashboard', 'Dashboard', 'home'),
                $this->item('account.watchlist', 'Watchlist', 'bookmark'),
                $this->item('account.lists.index', 'Lists', 'queue-list', [
                    'account.lists.*',
                ]),
                $this->item('account.settings', 'Settings', 'cog-6-tooth'),
            ]),
            $this->section('Browse', [
                $this->item('public.discover', 'Discover', 'sparkles'),
                $this->item('public.titles.index', 'Titles', 'film', [
                    'public.titles.*',
                    'public.genres.*',
                    'public.years.*',
                ]),
                $this->item('public.people.index', 'People', 'users', [
                    'public.people.*',
                ]),
                $this->item('public.search', 'Search', 'magnifying-glass'),
            ]),
        ]));
    }

    /**
     * @param  array{
     *     canViewAdminTitles: bool,
     *     canViewAdminPeople: bool,
     *     canViewAdminGenres: bool,
     *     canViewAdminMediaAssets: bool,
     *     canViewAdminContributions: bool,
     *     canViewAdminReviews: bool,
     *     canViewAdminReports: bool
     * }  $permissions
     * @return list<array{
     *     label: string,
     *     items: list<array{
     *         href: string,
     *         label: string,
     *         icon: string,
     *         active: bool
     *     }>
     * }>
     */
    public function forAdmin(array $permissions): array
    {
        return array_values(array_filter([
            $this->section('Overview', [
                $this->item('admin.dashboard', 'Dashboard', 'chart-bar-square'),
            ]),
            $this->section('Catalog', [
                $permissions['canViewAdminTitles']
                    ? $this->item('admin.titles.index', 'Titles', 'film', ['admin.titles.*'])
                    : null,
                $permissions['canViewAdminPeople']
                    ? $this->item('admin.people.index', 'People', 'users', [
                        'admin.people.*',
                        'admin.professions.*',
                    ])
                    : null,
                $permissions['canViewAdminGenres']
                    ? $this->item('admin.genres.index', 'Genres', 'tag', ['admin.genres.*'])
                    : null,
                $permissions['canViewAdminMediaAssets']
                    ? $this->item('admin.media-assets.index', 'Media', 'photo', ['admin.media-assets.*'])
                    : null,
            ]),
            $this->section('Operations', [
                $permissions['canViewAdminContributions']
                    ? $this->item('admin.contributions.index', 'Contributions', 'clipboard-document-check', ['admin.contributions.*'])
                    : null,
                $permissions['canViewAdminReviews']
                    ? $this->item('admin.reviews.index', 'Reviews', 'chat-bubble-left-right', ['admin.reviews.*'])
                    : null,
                $permissions['canViewAdminReports']
                    ? $this->item('admin.reports.index', 'Reports', 'flag', ['admin.reports.*'])
                    : null,
                $this->item('public.home', 'Public Site', 'arrow-up-right'),
            ]),
        ]));
    }

    /**
     * @param  list<array{
     *     href: string,
     *     label: string,
     *     icon: string,
     *     active: bool
     * }|null>  $items
     * @return array{
     *     label: string,
     *     items: list<array{
     *         href: string,
     *         label: string,
     *         icon: string,
     *         active: bool
     *     }>
     * }|null
     */
    private function section(string $label, array $items): ?array
    {
        $resolvedItems = array_values(array_filter($items));

        if ($resolvedItems === []) {
            return null;
        }

        return [
            'label' => $label,
            'items' => $resolvedItems,
        ];
    }

    /**
     * @param  list<string>  $activePatterns
     * @return array{
     *     href: string,
     *     label: string,
     *     icon: string,
     *     active: bool
     * }|null
     */
    private function item(string $routeName, string $label, string $icon, array $activePatterns = []): ?array
    {
        if (! Route::has($routeName)) {
            return null;
        }

        $resolvedActivePatterns = $activePatterns !== [] ? $activePatterns : [$routeName];

        return [
            'href' => route($routeName),
            'label' => $label,
            'icon' => $icon,
            'active' => collect($resolvedActivePatterns)->contains(
                fn (string $pattern): bool => request()->routeIs($pattern)
            ),
        ];
    }
}
