<?php

namespace App\Actions\Layout;

use Illuminate\Support\Facades\Route;

class BuildFooterAction
{
    /**
     * @return array{
     *     sections: list<array{
     *         heading: string,
     *         description: string,
     *         links: list<array{href: string, label: string, icon: string}>
     *     }>,
     *     legalLinks: list<array{href: string, label: string, icon: string}>,
     *     legalCopy: string
     * }
     */
    public function handle(): array
    {
        $browseLinks = array_values(array_filter([
            $this->link('public.home', 'Home', 'home'),
            $this->link('public.discover', 'Discovery', 'sparkles'),
            $this->link('public.titles.index', 'Browse Titles', 'film'),
            $this->link('public.movies.index', 'Movies', 'film'),
            $this->link('public.series.index', 'TV Shows', 'tv'),
            $this->link('public.people.index', 'People', 'users'),
            $this->link('public.interest-categories.index', 'Themes', 'squares-2x2'),
        ]));

        $signalLinks = array_values(array_filter([
            $this->link('public.trending', 'Trending', 'fire'),
            $this->link('public.trailers.latest', 'Trailers', 'play'),
            $this->link('public.awards.index', 'Awards', 'trophy'),
            $this->link('public.rankings.movies', 'Top Movies', 'star'),
            $this->link('public.rankings.series', 'Top Series', 'tv'),
        ]));

        $deepCatalogLinks = array_values(array_filter([
            $this->link('public.discover', 'Browse by Genre', 'tag'),
            $this->link('public.interest-categories.index', 'Browse by Theme', 'squares-2x2'),
            $this->link('public.titles.index', 'Browse by Year', 'calendar-days'),
            $this->link('public.search', 'Advanced Search', 'magnifying-glass'),
        ]));

        $legalLinks = array_values(array_filter([
            $this->link('public.changes', 'Changes', 'clock'),
        ]));

        return [
            'sections' => [
                [
                    'heading' => 'Browse',
                    'description' => 'Core entry points for the main public catalog lanes.',
                    'links' => $browseLinks,
                ],
                [
                    'heading' => 'Signals',
                    'description' => 'Follow momentum, releases, trailers, and standout recognition.',
                    'links' => $signalLinks,
                ],
                [
                    'heading' => 'Deep paths',
                    'description' => 'Use genre, theme, year, and search routes for narrower exploration.',
                    'links' => $deepCatalogLinks,
                ],
            ],
            'legalLinks' => $legalLinks,
            'legalCopy' => 'Built for title discovery, trailer browsing, awards tracking, and IMDb-style public catalog exploration.',
        ];
    }

    /**
     * @return array{href: string, label: string, icon: string}|null
     */
    private function link(string $routeName, string $label, string $icon): ?array
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return [
            'href' => route($routeName),
            'label' => $label,
            'icon' => $icon,
        ];
    }
}
