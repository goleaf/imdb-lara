<?php

namespace Tests\Feature\Feature\Livewire;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Actions\Search\BuildDiscoveryQueryAction;
use App\Actions\Search\BuildDiscoveryViewDataAction;
use App\Models\Title;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class DiscoveryFiltersTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_discovery_filters_render_combobox_controls_instead_of_native_selects(): void
    {
        Livewire::withoutLazyLoading();

        $this->get(route('public.discover'))
            ->assertOk()
            ->assertSee('Filter Panel')
            ->assertSee('Loading')
            ->assertSeeHtml('wire:intersect.once="__lazyLoadIsland"')
            ->assertDontSeeHtml('<select');
    }

    public function test_discovery_filters_render_title_autocomplete_suggestions_for_matching_titles(): void
    {
        $title = $this->sampleTitle();
        $viewData = $this->discoveryViewData([
            'search' => $this->searchTermFor($title),
        ]);

        $this->assertTrue(
            $viewData['searchSuggestions']->contains(
                fn (Title $suggestion): bool => $suggestion->is($title),
            ),
        );
    }

    public function test_discovery_filters_return_the_matrix_for_exact_keyword_search(): void
    {
        $matrix = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->where(function ($query): void {
                $query
                    ->where('movies.primarytitle', 'The Matrix')
                    ->orWhere('movies.originaltitle', 'The Matrix');
            })
            ->orderBy('movies.startyear')
            ->first();

        if (! $matrix instanceof Title) {
            $this->markTestSkipped('The remote catalog does not expose The Matrix in the current dataset.');
        }

        $results = app(BuildDiscoveryQueryAction::class)
            ->handle([
                'search' => 'the matrix',
                'sort' => 'popular',
            ])
            ->limit(12)
            ->get();

        $this->assertTrue($results->contains(fn (Title $title): bool => $title->is($matrix)));
    }

    public function test_discovery_filters_hydrate_the_matrix_from_the_query_string(): void
    {
        $matrix = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->where(function ($query): void {
                $query
                    ->where('movies.primarytitle', 'The Matrix')
                    ->orWhere('movies.originaltitle', 'The Matrix');
            })
            ->orderBy('movies.startyear')
            ->first();

        if (! $matrix instanceof Title) {
            $this->markTestSkipped('The remote catalog does not expose The Matrix in the current dataset.');
        }

        $viewData = $this->discoveryViewData([
            'search' => 'the matrix',
        ]);

        $this->assertSame(1, $viewData['activeFilterCount']);
        $this->assertSame('Keyword: the matrix', $viewData['activeFilters']->first()['label'] ?? null);
        $this->assertTrue(
            $viewData['titles']->getCollection()->contains(
                fn (Title $title): bool => $title->is($matrix),
            ),
        );
    }

    public function test_discovery_filters_search_by_text_genre_and_rating_against_remote_titles(): void
    {
        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('genres')
            ->whereHas('statistic', fn ($query) => $query->where('aggregate_rating', '>=', 1))
            ->with([
                'genres:id,name',
                'statistic:movie_id,aggregate_rating,vote_count',
            ])
            ->orderBy('movies.id')
            ->firstOrFail();

        $genre = $title->genres->firstOrFail();
        $minimumRating = max(1, (int) floor($title->displayAverageRating() ?? 1));
        $genreResultTitle = app(BuildPublicTitleIndexQueryAction::class)
            ->handle([
                'genre' => $genre->slug,
                'sort' => 'popular',
            ])
            ->limit(12)
            ->firstOrFail();
        $ratingResultTitle = app(BuildPublicTitleIndexQueryAction::class)
            ->handle([
                'minimumRating' => $minimumRating,
                'sort' => 'popular',
            ])
            ->limit(12)
            ->firstOrFail();
        $excludedTitle = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKeyNot($title->id)
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        $searchViewData = $this->discoveryViewData([
            'search' => $this->searchTermFor($title),
        ]);

        $this->assertTrue(
            $searchViewData['titles']->getCollection()->contains(
                fn (Title $result): bool => $result->is($title),
            ),
        );

        if ($excludedTitle instanceof Title) {
            $this->assertFalse(
                $searchViewData['titles']->getCollection()->contains(
                    fn (Title $result): bool => $result->is($excludedTitle),
                ),
            );
        }

        $genreViewData = $this->discoveryViewData([
            'genre' => $genre->slug,
        ]);

        $this->assertTrue(
            $genreViewData['titles']->getCollection()->contains(
                fn (Title $result): bool => $result->is($genreResultTitle),
            ),
        );

        $ratingViewData = $this->discoveryViewData([
            'minimumRating' => (string) $minimumRating,
        ]);

        $this->assertTrue(
            $ratingViewData['titles']->getCollection()->contains(
                fn (Title $result): bool => $result->is($ratingResultTitle),
            ),
        );
    }

    public function test_discovery_filters_support_awards_release_runtime_language_and_country_filters(): void
    {
        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('awardNominations', fn ($query) => $query->where('is_winner', true))
            ->whereHas('languages')
            ->whereHas('countries')
            ->whereNotNull('movies.startyear')
            ->whereNotNull('movies.runtimeminutes')
            ->with([
                'countries:code,name',
                'languages:code,name',
            ])
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose an award-winning title with language, country, and runtime metadata.');
        }

        $language = $title->languages->first()?->code;
        $country = $title->countries->first()?->code;

        if (! is_string($language) || $language === '' || ! is_string($country) || $country === '') {
            $this->markTestSkipped('The selected remote award-winning title is missing language or country metadata.');
        }

        $runtimeFilter = match (true) {
            $title->runtime_minutes < 30 => 'under-30',
            $title->runtime_minutes <= 60 => '30-60',
            $title->runtime_minutes <= 90 => '60-90',
            $title->runtime_minutes <= 120 => '90-120',
            default => '120-plus',
        };

        $viewData = $this->discoveryViewData([
            'awards' => 'winners',
            'yearFrom' => (string) $title->release_year,
            'yearTo' => (string) $title->release_year,
            'runtime' => $runtimeFilter,
            'language' => $language,
            'country' => $country,
        ]);

        $this->assertSame(6, $viewData['activeFilterCount']);
        $this->assertTrue(
            $viewData['titles']->getCollection()->contains(
                fn (Title $result): bool => $result->is($title),
            ),
        );
    }

    public function test_discovery_filters_support_theme_filters_against_remote_titles(): void
    {
        $interestCategory = $this->sampleInterestCategory();
        $themeResultTitle = app(BuildDiscoveryQueryAction::class)
            ->handle([
                'search' => '',
                'genre' => null,
                'theme' => $interestCategory->slug,
                'type' => null,
                'sort' => 'popular',
                'minimumRating' => null,
                'yearFrom' => null,
                'yearTo' => null,
                'votesMin' => null,
                'language' => null,
                'country' => null,
                'runtime' => null,
                'awards' => null,
            ])
            ->limit(12)
            ->first();

        if (! $themeResultTitle instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a visible title for the sampled interest category.');
        }

        $viewData = $this->discoveryViewData([
            'theme' => $interestCategory->slug,
        ]);

        $this->assertSame(1, $viewData['activeFilterCount']);
        $this->assertTrue(
            $viewData['activeFilters']->contains(
                fn (array $filter): bool => $filter['label'] === $interestCategory->name,
            ),
        );
        $this->assertTrue(
            $viewData['titles']->getCollection()->contains(
                fn (Title $result): bool => $result->is($themeResultTitle),
            ),
        );
    }

    public function test_discovery_filters_make_active_filter_state_obvious(): void
    {
        $title = $this->sampleTitle()->loadMissing('genres');
        $genre = $title->genres->firstOrFail();
        $search = $this->searchTermFor($title);
        $viewData = $this->discoveryViewData([
            'search' => $search,
            'genre' => $genre->slug,
        ]);

        $this->assertSame(2, $viewData['activeFilterCount']);
        $this->assertTrue(
            $viewData['activeFilters']->contains(
                fn (array $filter): bool => $filter['label'] === 'Keyword: '.$search,
            ),
        );
        $this->assertTrue(
            $viewData['activeFilters']->contains(
                fn (array $filter): bool => $filter['label'] === $genre->name,
            ),
        );
    }

    public function test_discovery_pagination_buttons_are_scoped_to_the_results_island(): void
    {
        Livewire::withoutLazyLoading();

        $paginator = app(BuildDiscoveryQueryAction::class)
            ->handle([
                'search' => '',
                'genre' => null,
                'type' => null,
                'sort' => 'popular',
                'minimumRating' => null,
                'yearFrom' => null,
                'yearTo' => null,
                'votesMin' => null,
                'language' => null,
                'country' => null,
                'runtime' => null,
                'awards' => null,
            ])
            ->simplePaginate(12, pageName: 'discover');

        if (! $paginator->hasMorePages()) {
            $this->markTestSkipped('The remote catalog does not currently expose enough discovery titles to verify paginator controls.');
        }

        $nextResponse = $this->get(route('public.discover').'?q=');

        $nextResponse->assertOk();
        $nextResponse->assertSee('FRAGMENT:type=island|name=discover-results-page', false);

        $previousResponse = $this->get(route('public.discover', ['discover' => 2]).'&q=');

        $previousResponse->assertOk();
        $previousResponse->assertSee('FRAGMENT:type=island|name=discover-results-page', false);
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return array<string, mixed>
     */
    private function discoveryViewData(array $filters = []): array
    {
        return app(BuildDiscoveryViewDataAction::class)->handle([
            'search' => '',
            'genre' => null,
            'theme' => null,
            'type' => null,
            'sort' => 'popular',
            'minimumRating' => null,
            'yearFrom' => null,
            'yearTo' => null,
            'votesMin' => null,
            'language' => null,
            'country' => null,
            'runtime' => null,
            'awards' => null,
            ...$filters,
        ]);
    }
}
