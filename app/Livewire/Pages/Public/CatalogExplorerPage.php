<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class CatalogExplorerPage extends Component
{
    use RendersPageView;

    public string $section = 'titles';

    public function mount(?string $section = null): void
    {
        $this->section = in_array($section, ['titles', 'people', 'themes'], true)
            ? $section
            : 'titles';
    }

    public function render(): View
    {
        $sectionConfig = $this->sectionConfig();

        return $this->renderPageView('catalog.explorer', [
            'currentSection' => $this->section,
            'sectionConfig' => $sectionConfig,
            'sectionNav' => $this->sectionNavigation(),
            'seo' => new PageSeoData(
                title: $sectionConfig['title'],
                description: $sectionConfig['description'],
                canonical: $sectionConfig['href'],
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Catalog Explorer'],
                    ['label' => $sectionConfig['label']],
                ],
                preserveQueryString: true,
                allowedQueryParameters: $sectionConfig['allowedQueryParameters'],
            ),
        ]);
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     title: string,
     *     eyebrow: string,
     *     description: string,
     *     href: string,
     *     actions: list<array{label: string, href: string, variant: string, icon: string}>,
     *     badges: list<array{label: string, icon: string}>,
     *     allowedQueryParameters: list<string>
     * }
     */
    private function sectionConfig(): array
    {
        return match ($this->section) {
            'people' => [
                'key' => 'people',
                'label' => 'People',
                'title' => 'Catalog Explorer · People',
                'eyebrow' => 'Canonical aggregate',
                'description' => 'Profile-driven browsing for cast, crew, professions, rankings, and linked titles using the normalized person graph.',
                'href' => route('public.catalog.explorer', ['section' => 'people']),
                'actions' => [
                    ['label' => 'People Directory', 'href' => route('public.people.index'), 'variant' => 'outline', 'icon' => 'users'],
                    ['label' => 'Advanced Search', 'href' => route('public.search', ['tab' => 'people']), 'variant' => 'ghost', 'icon' => 'funnel'],
                ],
                'badges' => [
                    ['label' => 'Profiles', 'icon' => 'users'],
                    ['label' => 'Professions', 'icon' => 'briefcase'],
                    ['label' => 'Known-for titles', 'icon' => 'film'],
                ],
                'allowedQueryParameters' => ['q', 'profession', 'sort', 'people'],
            ],
            'themes' => [
                'key' => 'themes',
                'label' => 'Themes',
                'title' => 'Catalog Explorer · Themes',
                'eyebrow' => 'Taxonomy explorer',
                'description' => 'Interest categories, linked interests, and title clusters exposed through the normalized discovery taxonomy instead of raw join tables.',
                'href' => route('public.catalog.explorer', ['section' => 'themes']),
                'actions' => [
                    ['label' => 'Theme Directory', 'href' => route('public.interest-categories.index'), 'variant' => 'outline', 'icon' => 'squares-2x2'],
                    ['label' => 'Discovery Search', 'href' => route('public.discover'), 'variant' => 'ghost', 'icon' => 'sparkles'],
                ],
                'badges' => [
                    ['label' => 'Interest lanes', 'icon' => 'sparkles'],
                    ['label' => 'Subgenres', 'icon' => 'tag'],
                    ['label' => 'Linked titles', 'icon' => 'film'],
                ],
                'allowedQueryParameters' => ['q', 'sort', 'interest-categories'],
            ],
            default => [
                'key' => 'titles',
                'label' => 'Titles',
                'title' => 'Catalog Explorer · Titles',
                'eyebrow' => 'Aggregate root',
                'description' => 'A direct read on the public title graph with card-ready eager loads, hero media, ratings, countries, languages, and discovery links.',
                'href' => route('public.catalog.explorer'),
                'actions' => [
                    ['label' => 'Browse Titles', 'href' => route('public.titles.index'), 'variant' => 'outline', 'icon' => 'film'],
                    ['label' => 'Deep Discovery', 'href' => route('public.discover'), 'variant' => 'ghost', 'icon' => 'sparkles'],
                ],
                'badges' => [
                    ['label' => 'Genres', 'icon' => 'tag'],
                    ['label' => 'Media', 'icon' => 'photo'],
                    ['label' => 'Ratings', 'icon' => 'star'],
                ],
                'allowedQueryParameters' => ['catalog-titles', 'country'],
            ],
        };
    }

    /**
     * @return Collection<int, array{key: string, label: string, icon: string, href: string, copy: string}>
     */
    private function sectionNavigation(): Collection
    {
        return collect([
            [
                'key' => 'titles',
                'label' => 'Titles',
                'icon' => 'film',
                'href' => route('public.catalog.explorer'),
                'copy' => 'Card-ready title aggregates.',
            ],
            [
                'key' => 'people',
                'label' => 'People',
                'icon' => 'users',
                'href' => route('public.catalog.explorer', ['section' => 'people']),
                'copy' => 'Profiles, filmographies, and collaborators.',
            ],
            [
                'key' => 'themes',
                'label' => 'Themes',
                'icon' => 'squares-2x2',
                'href' => route('public.catalog.explorer', ['section' => 'themes']),
                'copy' => 'Interest categories and linked titles.',
            ],
        ]);
    }
}
