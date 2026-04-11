<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\GetFeaturedInterestCategoriesAction;
use App\Actions\Catalog\LoadPublicTitleBrowserPageAction;
use App\Models\Genre;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Livewire\Livewire;
use Mockery;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class BrowseTitlesPageLocalRenderTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_titles_index_renders_local_title_cards_with_rating_and_votes(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->makeTitle(
            attributes: [
                'id' => 1,
                'imdb_id' => 'tt0133093',
                'name' => 'The Matrix',
                'original_name' => 'The Matrix',
                'title_type' => 'movie',
                'release_year' => 1999,
            ],
            genres: [$this->makeGenre(1, 'Science Fiction')],
            statistic: $this->makeStatistic(1, 8.7, 2100000),
        );

        $loadPublicTitleBrowserPage = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $loadPublicTitleBrowserPage
            ->shouldReceive('handleSafely')
            ->once()
            ->with($this->expectedTitlesFilters(), 12, 'titles')
            ->andReturn($this->paginatedBrowserData(
                new EloquentCollection([$title]),
                route('public.titles.index'),
                'titles',
            ));

        $getFeaturedInterestCategories = Mockery::mock(GetFeaturedInterestCategoriesAction::class);
        $getFeaturedInterestCategories
            ->shouldReceive('handle')
            ->once()
            ->with(4, null)
            ->andReturn(new EloquentCollection);

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $loadPublicTitleBrowserPage);
        $this->app->instance(GetFeaturedInterestCategoriesAction::class, $getFeaturedInterestCategories);

        $this->get(route('public.titles.index'))
            ->assertOk()
            ->assertSee('Browse Titles')
            ->assertSee('The Matrix')
            ->assertSee('Science Fiction')
            ->assertSee('8.7')
            ->assertSee('2,100,000 votes');
    }

    public function test_trending_chart_respects_the_selected_country_context(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->makeTitle(
            attributes: [
                'id' => 2,
                'imdb_id' => 'tt0095016',
                'name' => 'Die Hard',
                'original_name' => 'Die Hard',
                'title_type' => 'movie',
                'release_year' => 1988,
                'origin_country' => 'LT',
            ],
            statistic: $this->makeStatistic(2, 8.2, 750000),
        );

        $loadPublicTitleBrowserPage = Mockery::mock(LoadPublicTitleBrowserPageAction::class);
        $loadPublicTitleBrowserPage
            ->shouldReceive('handleCollectionSafely')
            ->once()
            ->with($this->expectedTrendingFilters('LT'), 12)
            ->andReturn($this->collectionBrowserData(new EloquentCollection([$title])));

        $getFeaturedInterestCategories = Mockery::mock(GetFeaturedInterestCategoriesAction::class);
        $getFeaturedInterestCategories->shouldNotReceive('handle');

        $this->app->instance(LoadPublicTitleBrowserPageAction::class, $loadPublicTitleBrowserPage);
        $this->app->instance(GetFeaturedInterestCategoriesAction::class, $getFeaturedInterestCategories);

        $this->get(route('public.trending', ['country' => 'LT']))
            ->assertOk()
            ->assertSee('Local Charts')
            ->assertSee('Lithuania')
            ->assertSee('Lithuania local chart');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<Genre>  $genres
     */
    private function makeTitle(array $attributes, array $genres = [], ?TitleStatistic $statistic = null): Title
    {
        $title = new Title;
        $title->forceFill([
            'id' => 1,
            'imdb_id' => 'tt0000001',
            'name' => 'Untitled',
            'original_name' => 'Untitled',
            'title_type' => 'movie',
            'release_year' => 2000,
            'is_published' => true,
            ...$attributes,
        ]);
        $title->exists = true;
        $title->setRelation('genres', new EloquentCollection($genres));
        $title->setRelation('statistic', $statistic);

        return $title;
    }

    private function makeGenre(int $id, string $name): Genre
    {
        $genre = new Genre;
        $genre->forceFill([
            'id' => $id,
            'name' => $name,
            'slug' => str($name)->slug()->append('-g'.$id)->toString(),
        ]);
        $genre->exists = true;

        return $genre;
    }

    private function makeStatistic(int $titleId, float $averageRating, int $ratingCount): TitleStatistic
    {
        $statistic = new TitleStatistic;
        $statistic->forceFill([
            'title_id' => $titleId,
            'average_rating' => $averageRating,
            'rating_count' => $ratingCount,
        ]);
        $statistic->exists = true;

        return $statistic;
    }

    /**
     * @param  EloquentCollection<int, Title>  $titles
     * @return array{
     *     titles: Paginator,
     *     usingStaleCache: bool,
     *     isUnavailable: bool
     * }
     */
    private function paginatedBrowserData(EloquentCollection $titles, string $path, string $pageName): array
    {
        return [
            'titles' => new Paginator(
                items: $titles,
                perPage: 12,
                currentPage: 1,
                options: [
                    'path' => $path,
                    'pageName' => $pageName,
                ],
            ),
            'usingStaleCache' => false,
            'isUnavailable' => false,
        ];
    }

    /**
     * @param  EloquentCollection<int, Title>  $titles
     * @return array{
     *     titles: EloquentCollection<int, Title>,
     *     usingStaleCache: bool,
     *     isUnavailable: bool
     * }
     */
    private function collectionBrowserData(EloquentCollection $titles): array
    {
        return [
            'titles' => $titles,
            'usingStaleCache' => false,
            'isUnavailable' => false,
        ];
    }

    /**
     * @return array{
     *     types: list<string>,
     *     genre: null,
     *     theme: null,
     *     year: null,
     *     country: null,
     *     sort: string,
     *     excludeEpisodes: bool
     * }
     */
    private function expectedTitlesFilters(): array
    {
        return [
            'types' => [],
            'genre' => null,
            'theme' => null,
            'year' => null,
            'country' => null,
            'sort' => 'name',
            'excludeEpisodes' => true,
        ];
    }

    /**
     * @return array{
     *     types: list<string>,
     *     genre: null,
     *     theme: null,
     *     year: null,
     *     country: string,
     *     sort: string,
     *     excludeEpisodes: bool
     * }
     */
    private function expectedTrendingFilters(string $countryCode): array
    {
        return [
            'types' => [],
            'genre' => null,
            'theme' => null,
            'year' => null,
            'country' => $countryCode,
            'sort' => 'trending',
            'excludeEpisodes' => true,
        ];
    }
}
