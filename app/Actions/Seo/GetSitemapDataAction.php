<?php

namespace App\Actions\Seo;

use App\Enums\TitleType;
use App\Models\Genre;
use App\Models\InterestCategory;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class GetSitemapDataAction
{
    private const TITLE_LIMIT = 100;

    private const PERSON_LIMIT = 100;

    private const INTEREST_CATEGORY_LIMIT = 100;

    private const SEASON_LIMIT = 100;

    private const EPISODE_LIMIT = 100;

    /**
     * @return array{
     *     staticRoutes: list<string>,
     *     genres: EloquentCollection<int, Genre>,
     *     interestCategories: EloquentCollection<int, InterestCategory>,
     *     years: Collection<int, int>,
     *     titles: EloquentCollection<int, Title>,
     *     titleArchiveUrls: Collection<int, string>,
     *     episodes: EloquentCollection<int, Title>,
     *     seasons: EloquentCollection<int, Season>,
     *     people: EloquentCollection<int, Person>
     * }
     */
    public function handle(): array
    {
        $titles = Title::query()
            ->select(['id', 'slug', 'name', 'release_year'])
            ->publishedCatalog()
            ->orderByDesc('release_year')
            ->orderBy('name')
            ->limit(self::TITLE_LIMIT)
            ->get();
        $staticRoutes = collect([
            'public.home',
            'public.discover',
            'public.titles.index',
            'public.people.index',
            'public.interest-categories.index',
            'public.awards.index',
            'public.trailers.latest',
            'public.movies.index',
            'public.series.index',
            'public.rankings.movies',
            'public.rankings.series',
            'public.trending',
        ])
            ->filter(fn (string $routeName): bool => Route::has($routeName))
            ->map(fn (string $routeName): string => route($routeName))
            ->values()
            ->all();

        return [
            'staticRoutes' => $staticRoutes,
            'genres' => Genre::query()
                ->select(['id', 'name'])
                ->whereHas('titles', fn ($query) => $query->publishedCatalog())
                ->orderBy('name')
                ->get(),
            'interestCategories' => Route::has('public.interest-categories.show')
                ? InterestCategory::query()
                    ->select(['interest_categories.id', 'interest_categories.name'])
                    ->whereHas('interests.movies')
                    ->orderBy('interest_categories.name')
                    ->limit(self::INTEREST_CATEGORY_LIMIT)
                    ->get()
                : new EloquentCollection,
            'years' => Title::query()
                ->select(['release_year'])
                ->publishedCatalog()
                ->whereNotNull('release_year')
                ->distinct()
                ->orderByDesc('release_year')
                ->pluck('release_year'),
            'titles' => $titles,
            'titleArchiveUrls' => $titles
                ->flatMap(function (Title $title): Collection {
                    return collect([
                        'public.titles.cast',
                        'public.titles.media',
                        'public.titles.box-office',
                        'public.titles.parents-guide',
                        'public.titles.trivia',
                        'public.titles.metadata',
                    ])
                        ->filter(fn (string $routeName): bool => Route::has($routeName))
                        ->map(fn (string $routeName): string => route($routeName, $title));
                })
                ->values(),
            'episodes' => Route::has('public.episodes.show')
                ? Title::query()
                    ->select(['id', 'slug', 'name', 'title_type'])
                    ->published()
                    ->where('title_type', TitleType::Episode->value)
                    ->with('episodeMeta.series:id,slug,name', 'episodeMeta')
                    ->orderBy('name')
                    ->limit(self::EPISODE_LIMIT)
                    ->get()
                : new EloquentCollection,
            'seasons' => Route::has('public.seasons.show')
                ? Season::query()
                    ->select(['id', 'series_id', 'slug', 'season_number', 'name'])
                    ->with('series:id,slug,name')
                    ->withCount('episodes')
                    ->orderBy('series_id')
                    ->orderBy('season_number')
                    ->limit(self::SEASON_LIMIT)
                    ->get()
                : new EloquentCollection,
            'people' => Person::query()
                ->select(['id', 'slug', 'name'])
                ->published()
                ->orderBy('name')
                ->limit(self::PERSON_LIMIT)
                ->get(),
        ];
    }
}
