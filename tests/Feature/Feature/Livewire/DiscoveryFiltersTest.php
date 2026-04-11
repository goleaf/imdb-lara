<?php

namespace Tests\Feature\Feature\Livewire;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Actions\Search\BuildDiscoveryQueryAction;
use App\Livewire\Search\DiscoveryFilters;
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

        Livewire::test(DiscoveryFilters::class)
            ->assertSeeHtml('data-slot="discover-filters-island"')
            ->assertSeeHtml('data-slot="discover-active-filters"')
            ->assertSeeHtml('data-slot="autocomplete"')
            ->assertSeeHtml('data-slot="combobox-input"')
            ->assertDontSeeHtml('<select');
    }

    public function test_discovery_filters_render_title_autocomplete_suggestions_for_matching_titles(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();

        $this->get(route('public.discover', ['q' => $this->searchTermFor($title)]))
            ->assertOk()
            ->assertSeeHtml('data-slot="autocomplete-item"')
            ->assertSee($title->name);
    }

    public function test_discovery_filters_return_the_matrix_for_exact_keyword_search(): void
    {
        Livewire::withoutLazyLoading();

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

        $this->get(route('public.discover', ['q' => 'the matrix']))
            ->assertOk()
            ->assertSee($matrix->name)
            ->assertDontSee('No titles match the current filters.');
    }

    public function test_discovery_filters_hydrate_the_matrix_from_the_query_string(): void
    {
        Livewire::withoutLazyLoading();

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

        $this->get(route('public.discover', ['q' => 'the matrix']))
            ->assertOk()
            ->assertSee('Keyword: the matrix')
            ->assertSee($matrix->name)
            ->assertDontSee('No titles match the current filters.');
    }

    public function test_discovery_filters_search_by_text_genre_and_rating_against_remote_titles(): void
    {
        Livewire::withoutLazyLoading();

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

        $this->get(route('public.discover', ['q' => $this->searchTermFor($title)]))
            ->assertOk()
            ->assertSee($title->name);

        if ($excludedTitle instanceof Title) {
            $this->get(route('public.discover', ['q' => $this->searchTermFor($title)]))
                ->assertOk()
                ->assertDontSee($excludedTitle->name);
        }

        $this->get(route('public.discover', ['genre' => $genre->slug]))
            ->assertOk()
            ->assertSee($genreResultTitle->name)
            ->assertDontSee('No titles match the current filters.');

        $this->get(route('public.discover', ['minimumRating' => (string) $minimumRating]))
            ->assertOk()
            ->assertSee($ratingResultTitle->name);
    }

    public function test_discovery_filters_support_awards_release_runtime_language_and_country_filters(): void
    {
        Livewire::withoutLazyLoading();

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

        $this->get(route('public.discover', [
            'awards' => 'winners',
            'yearFrom' => (string) $title->release_year,
            'yearTo' => (string) $title->release_year,
            'runtime' => $runtimeFilter,
            'language' => $language,
            'country' => $country,
        ]))
            ->assertOk()
            ->assertSee('Awards')
            ->assertSee('Release from')
            ->assertSee('Vote count')
            ->assertSee('Country')
            ->assertSee($title->name);
    }

    public function test_discovery_filters_make_active_filter_state_obvious(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle()->loadMissing('genres');
        $genre = $title->genres->firstOrFail();
        $search = $this->searchTermFor($title);

        $this->get(route('public.discover', [
            'q' => $search,
            'genre' => $genre->slug,
        ]))
            ->assertOk()
            ->assertSee('2 active')
            ->assertSee('Keyword: '.$search)
            ->assertSee($genre->name);
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
        $this->assertMatchesRegularExpression(
            '/dusk="nextPage\\.discover"[^>]*wire:island="discover-results-page"|wire:island="discover-results-page"[^>]*dusk="nextPage\\.discover"/',
            $nextResponse->getContent(),
        );

        $previousResponse = $this->get(route('public.discover', ['discover' => 2]).'&q=');

        $previousResponse->assertOk();
        $this->assertMatchesRegularExpression(
            '/dusk="previousPage\\.discover"[^>]*wire:island="discover-results-page"|wire:island="discover-results-page"[^>]*dusk="previousPage\\.discover"/',
            $previousResponse->getContent(),
        );
    }
}
