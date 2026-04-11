<?php

namespace Tests\Feature\Feature\Livewire;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Enums\TitleType;
use App\Livewire\Catalog\TitleBrowser;
use App\Models\Title;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleBrowserTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_browser_filters_title_collections_for_public_catalog_pages(): void
    {
        Livewire::withoutLazyLoading();

        $seedTitle = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->forType(TitleType::Movie)
            ->whereHas('genres')
            ->whereNotNull('movies.startyear')
            ->with('genres:id,name')
            ->orderBy('movies.id')
            ->firstOrFail();

        $genre = $seedTitle->genres->firstOrFail();
        $filters = [
            'types' => [TitleType::Movie->value],
            'genre' => $genre->slug,
            'year' => $seedTitle->release_year,
            'sort' => 'rating',
            'excludeEpisodes' => true,
        ];

        $results = app(BuildPublicTitleIndexQueryAction::class)
            ->handle($filters)
            ->limit(12)
            ->get();

        if ($results->isEmpty()) {
            $this->markTestSkipped('The remote catalog did not return any movie results for the sampled title-browser filters.');
        }

        $includedTitle = $results->first();
        $excludedTitle = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereNotIn('movies.id', $results->pluck('id')->all())
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        $component = Livewire::test(TitleBrowser::class, [
            'types' => [TitleType::Movie->value],
            'genre' => $genre->slug,
            'year' => $seedTitle->release_year,
            'sort' => 'rating',
            'pageName' => 'movies',
        ])
            ->assertSeeHtml('data-slot="title-browser-island"')
            ->assertSee($includedTitle->name)
            ->assertDontSeeHtml('title-browser-skeleton-1')
            ->assertSeeHtml(route('public.genres.show', $genre));

        if ($excludedTitle instanceof Title) {
            $component->assertDontSee($excludedTitle->name);
        }
    }

    public function test_title_browser_renders_empty_state_for_unmatched_collections(): void
    {
        Livewire::withoutLazyLoading();

        Livewire::test(TitleBrowser::class, [
            'types' => [TitleType::Documentary->value],
            'year' => 9999,
            'emptyHeading' => 'No documentaries found.',
            'emptyText' => 'Try another route into the catalog.',
        ])
            ->assertSee('No documentaries found.')
            ->assertSee('Try another route into the catalog.');
    }

    public function test_title_browser_supports_theme_filtered_public_catalog_pages(): void
    {
        Livewire::withoutLazyLoading();

        $interestCategory = $this->sampleInterestCategory();
        $results = app(BuildPublicTitleIndexQueryAction::class)
            ->handle([
                'theme' => $interestCategory->slug,
                'sort' => 'popular',
                'excludeEpisodes' => true,
            ])
            ->limit(12)
            ->get();

        if ($results->isEmpty()) {
            $this->markTestSkipped('The remote catalog did not return any public titles for the sampled theme filter.');
        }

        Livewire::test(TitleBrowser::class, [
            'theme' => $interestCategory->slug,
            'pageName' => 'titles',
        ])
            ->assertSeeHtml('data-slot="title-browser-island"')
            ->assertSee($results->first()->name);
    }

    public function test_title_browser_can_render_trending_collections_without_pagination(): void
    {
        Livewire::withoutLazyLoading();

        $results = app(BuildPublicTitleIndexQueryAction::class)
            ->handle([
                'sort' => 'trending',
                'excludeEpisodes' => true,
            ])
            ->limit(12)
            ->get();

        if ($results->isEmpty()) {
            $this->markTestSkipped('The remote catalog did not return any trending titles for the sampled title browser.');
        }

        Livewire::test(TitleBrowser::class, [
            'sort' => 'trending',
            'showAll' => true,
            'pageName' => 'trending',
            'displayMode' => 'chart',
        ])
            ->assertSeeHtml('data-slot="title-browser-island"')
            ->assertSee($results->first()->name)
            ->assertDontSee('catalog order')
            ->assertDontSeeHtml('title-browser-skeleton-1')
            ->assertDontSeeHtml('aria-label="Pagination Navigation"');
    }
}
