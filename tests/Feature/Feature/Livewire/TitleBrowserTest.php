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
            ->assertSee($includedTitle->name);

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
}
