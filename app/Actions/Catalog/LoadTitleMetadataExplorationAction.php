<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\CatalogMediaAsset;
use App\Models\Genre;
use App\Models\Interest;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LoadTitleMetadataExplorationAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: CatalogMediaAsset|null,
     *     backdrop: CatalogMediaAsset|null,
     *     keywordCount: int,
     *     connectionCount: int,
     *     keywordGroups: Collection<int, array{
     *         label: string,
     *         copy: string,
     *         keywords: Collection<int, array{name: string, href: string, relevance: int, relevanceLabel: string}>
     *     }>,
     *     connectionGroups: Collection<int, array{
     *         label: string,
     *         copy: string,
     *         count: int,
     *         items: Collection<int, array{
     *             title: Title,
     *             badgeLabel: string,
     *             weight: int|null,
     *             typeLabel: string,
     *             yearLabel: string|null,
     *             ratingLabel: string|null,
     *             note: string|null
     *         }>
     *     }>,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'genres:id,name',
            'statistic:movie_id,aggregate_rating,vote_count',
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
            'interests:imdb_id,name,description,is_subgenre',
            'interests.interestCategoryInterests:interest_category_id,interest_imdb_id,position',
            'interests.interestCategoryInterests.interestCategory:id,name',
            'interests.interestSimilarInterests:interest_imdb_id,similar_interest_imdb_id,position',
            'interests.interestSimilarInterests.similar:imdb_id,name,description,is_subgenre',
        ]);

        $poster = $title->preferredPoster();
        $backdrop = $title->preferredBackdrop();
        $keywordItems = $this->buildKeywordItems($title);
        $keywordGroups = $this->buildKeywordGroups($keywordItems);
        $connectionGroups = $this->buildConnectionGroups($title);

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'keywordCount' => $keywordItems->count(),
            'connectionCount' => (int) $connectionGroups->sum('count'),
            'keywordGroups' => $keywordGroups,
            'connectionGroups' => $connectionGroups,
            'seo' => new PageSeoData(
                title: $title->name.' Keywords & Connections',
                description: 'Explore discovery keywords and title-to-title connections for '.$title->name.'.',
                canonical: route('public.titles.metadata', $title),
                openGraphType: 'article',
                openGraphImage: $backdrop?->url ?? $poster?->url,
                openGraphImageAlt: $backdrop?->alt_text ?: $poster?->alt_text ?: $title->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                    ['label' => 'Keywords & Connections'],
                ],
                paginationPageName: null,
            ),
        ];
    }

    /**
     * @return Collection<int, array{name: string, href: string, relevance: int, relevanceLabel: string}>
     */
    private function buildKeywordItems(Title $title): Collection
    {
        $interestKeywords = $title->interests
            ->map(fn (Interest $interest): ?string => $interest->name)
            ->filter();
        $fallbackKeywords = $interestKeywords->isEmpty()
            ? $title->genres->map(fn (Genre $genre): string => $genre->name)
            : collect();

        return $interestKeywords
            ->merge($fallbackKeywords)
            ->map(fn (string $keyword): string => Str::of($keyword)->trim()->headline()->toString())
            ->unique(fn (string $keyword): string => Str::of($keyword)->lower()->toString())
            ->take(12)
            ->values()
            ->map(function (string $keyword, int $index): array {
                $relevance = $index < 3 ? 3 : ($index < 7 ? 2 : 1);

                return [
                    'name' => $keyword,
                    'href' => route('public.search', ['q' => $keyword]),
                    'relevance' => $relevance,
                    'relevanceLabel' => match ($relevance) {
                        3 => 'High signal',
                        2 => 'Medium signal',
                        default => 'Niche signal',
                    },
                ];
            });
    }

    /**
     * @param  Collection<int, array{name: string, href: string, relevance: int, relevanceLabel: string}>  $keywordItems
     * @return Collection<int, array{
     *     label: string,
     *     copy: string,
     *     keywords: Collection<int, array{name: string, href: string, relevance: int, relevanceLabel: string}>
     * }>
     */
    private function buildKeywordGroups(Collection $keywordItems): Collection
    {
        return collect([
            [
                'label' => 'Primary Cues',
                'copy' => 'The strongest discovery hooks attached directly to this title.',
                'keywords' => $keywordItems->where('relevance', 3)->values(),
            ],
            [
                'label' => 'Story Vectors',
                'copy' => 'Secondary thematic lanes that broaden search and recommendation coverage.',
                'keywords' => $keywordItems->where('relevance', 2)->values(),
            ],
            [
                'label' => 'Niche Threads',
                'copy' => 'Smaller cues for deep-dive browsing and adjacent catalog exploration.',
                'keywords' => $keywordItems->where('relevance', 1)->values(),
            ],
        ])->filter(fn (array $group): bool => $group['keywords']->isNotEmpty())->values();
    }

    /**
     * @return Collection<int, array{
     *     label: string,
     *     copy: string,
     *     count: int,
     *     items: Collection<int, array{
     *         title: Title,
     *         badgeLabel: string,
     *         weight: int|null,
     *         typeLabel: string,
     *         yearLabel: string|null,
     *         ratingLabel: string|null,
     *         note: string|null
     *     }>
     * }>
     */
    private function buildConnectionGroups(Title $title): Collection
    {
        $usedTitleIds = collect([$title->getKey()]);
        $groups = collect();

        $sharedInterestGroup = $this->buildSharedInterestGroup($title, $usedTitleIds->all());

        if (is_array($sharedInterestGroup)) {
            $groups->push($sharedInterestGroup);
            $usedTitleIds = $usedTitleIds->merge($sharedInterestGroup['items']->pluck('title.id'))->unique();
        }

        $adjacentThemesGroup = $this->buildAdjacentThemesGroup($title, $usedTitleIds->all());

        if (is_array($adjacentThemesGroup)) {
            $groups->push($adjacentThemesGroup);
            $usedTitleIds = $usedTitleIds->merge($adjacentThemesGroup['items']->pluck('title.id'))->unique();
        }

        $genreNeighborsGroup = $this->buildGenreNeighborsGroup($title, $usedTitleIds->all());

        if (is_array($genreNeighborsGroup)) {
            $groups->push($genreNeighborsGroup);
        }

        return $groups->values();
    }

    /**
     * @param  list<int>  $excludedTitleIds
     * @return array{
     *     label: string,
     *     copy: string,
     *     count: int,
     *     items: Collection<int, array{
     *         title: Title,
     *         badgeLabel: string,
     *         weight: int|null,
     *         typeLabel: string,
     *         yearLabel: string|null,
     *         ratingLabel: string|null,
     *         note: string|null
     *     }>
     * }|null
     */
    private function buildSharedInterestGroup(Title $title, array $excludedTitleIds): ?array
    {
        $interestNames = $title->interests
            ->mapWithKeys(fn (Interest $interest): array => $interest->name
                ? [$interest->getKey() => $interest->name]
                : [])
            ->all();

        if ($interestNames === []) {
            return null;
        }

        $titles = $this->baseConnectionTitleQuery($title)
            ->whereNotIn('movies.id', $excludedTitleIds)
            ->whereHas(
                'interests',
                fn (Builder $query) => $query->whereIn('interests.imdb_id', array_keys($interestNames)),
            )
            ->with([
                'interests' => fn ($query) => $query
                    ->select(['interests.imdb_id', 'interests.name'])
                    ->whereIn('interests.imdb_id', array_keys($interestNames)),
            ])
            ->limit(6)
            ->get();

        $items = $titles
            ->map(function (Title $relatedTitle) use ($interestNames): ?array {
                $matchedInterests = $relatedTitle->interests
                    ->map(fn (Interest $interest): ?string => $interestNames[$interest->getKey()] ?? null)
                    ->filter()
                    ->values();

                if ($matchedInterests->isEmpty()) {
                    return null;
                }

                return $this->makeConnectionItem(
                    $relatedTitle,
                    $matchedInterests->first() ?? 'Shared Interest',
                    min(10, $matchedInterests->count() * 3),
                    'Shares '.$matchedInterests->take(3)->implode(', ').'.',
                );
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Shared Interests',
            'copy' => 'Titles linked through the same imported interest tags and subgenre signals.',
            'count' => $items->count(),
            'items' => $items,
        ];
    }

    /**
     * @param  list<int>  $excludedTitleIds
     * @return array{
     *     label: string,
     *     copy: string,
     *     count: int,
     *     items: Collection<int, array{
     *         title: Title,
     *         badgeLabel: string,
     *         weight: int|null,
     *         typeLabel: string,
     *         yearLabel: string|null,
     *         ratingLabel: string|null,
     *         note: string|null
     *     }>
     * }|null
     */
    private function buildAdjacentThemesGroup(Title $title, array $excludedTitleIds): ?array
    {
        $similarInterestNames = $title->interests
            ->flatMap(fn (Interest $interest): Collection => $interest->interestSimilarInterests
                ->mapWithKeys(function ($similarInterest): array {
                    $similarName = $similarInterest->similar?->name;

                    return $similarName ? [$similarInterest->similar_interest_imdb_id => $similarName] : [];
                }) ?? collect())
            ->all();

        if ($similarInterestNames === []) {
            return null;
        }

        $titles = $this->baseConnectionTitleQuery($title)
            ->whereNotIn('movies.id', $excludedTitleIds)
            ->whereHas(
                'interests',
                fn (Builder $query) => $query->whereIn('interests.imdb_id', array_keys($similarInterestNames)),
            )
            ->with([
                'interests' => fn ($query) => $query
                    ->select(['interests.imdb_id', 'interests.name'])
                    ->whereIn('interests.imdb_id', array_keys($similarInterestNames)),
            ])
            ->limit(6)
            ->get();

        $items = $titles
            ->map(function (Title $relatedTitle) use ($similarInterestNames): ?array {
                $matchedThemes = $relatedTitle->interests
                    ->map(fn (Interest $interest): ?string => $similarInterestNames[$interest->getKey()] ?? null)
                    ->filter()
                    ->values();

                if ($matchedThemes->isEmpty()) {
                    return null;
                }

                return $this->makeConnectionItem(
                    $relatedTitle,
                    $matchedThemes->first() ?? 'Adjacent Theme',
                    min(10, $matchedThemes->count() * 2),
                    'Bridges through adjacent themes like '.$matchedThemes->take(3)->implode(', ').'.',
                );
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Adjacent Themes',
            'copy' => 'Nearby titles surfaced from related interests in the imported metadata graph.',
            'count' => $items->count(),
            'items' => $items,
        ];
    }

    /**
     * @param  list<int>  $excludedTitleIds
     * @return array{
     *     label: string,
     *     copy: string,
     *     count: int,
     *     items: Collection<int, array{
     *         title: Title,
     *         badgeLabel: string,
     *         weight: int|null,
     *         typeLabel: string,
     *         yearLabel: string|null,
     *         ratingLabel: string|null,
     *         note: string|null
     *     }>
     * }|null
     */
    private function buildGenreNeighborsGroup(Title $title, array $excludedTitleIds): ?array
    {
        $genreNames = $title->genres
            ->mapWithKeys(fn (Genre $genre): array => [$genre->getKey() => $genre->name])
            ->all();

        if ($genreNames === []) {
            return null;
        }

        $titles = $this->baseConnectionTitleQuery($title)
            ->whereNotIn('movies.id', $excludedTitleIds)
            ->whereHas('genres', fn (Builder $query) => $query->whereIn('genres.id', array_keys($genreNames)))
            ->with([
                'genres:id,name',
            ])
            ->limit(6)
            ->get();

        $items = $titles
            ->map(function (Title $relatedTitle) use ($genreNames): ?array {
                $matchedGenres = $relatedTitle->genres
                    ->filter(fn (Genre $genre): bool => array_key_exists($genre->getKey(), $genreNames))
                    ->map(fn (Genre $genre): string => $genre->name)
                    ->values();

                if ($matchedGenres->isEmpty()) {
                    return null;
                }

                return $this->makeConnectionItem(
                    $relatedTitle,
                    $matchedGenres->first() ?? 'Shared Genre',
                    min(10, $matchedGenres->count() * 2),
                    'Shares genres such as '.$matchedGenres->take(3)->implode(', ').'.',
                );
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'label' => 'Genre Neighbors',
            'copy' => 'Additional catalog neighbors connected through the same core genres.',
            'count' => $items->count(),
            'items' => $items,
        ];
    }

    private function baseConnectionTitleQuery(Title $title): Builder
    {
        return Title::query()
            ->select([
                'movies.id',
                'movies.tconst',
                'movies.imdb_id',
                'movies.primarytitle',
                'movies.originaltitle',
                'movies.titletype',
                'movies.isadult',
                'movies.startyear',
                'movies.endyear',
                'movies.runtimeminutes',
                'movies.title_type_id',
                'movies.runtimeSeconds',
            ])
            ->addSelect([
                'popularity_rank' => TitleStatistic::query()
                    ->select('vote_count')
                    ->whereColumn('movie_ratings.movie_id', 'movies.id')
                    ->limit(1),
            ])
            ->publishedCatalog()
            ->whereKeyNot($title->getKey())
            ->with([
                'statistic:movie_id,aggregate_rating,vote_count',
                'titleImages:id,movie_id,position,url,width,height,type',
                'primaryImageRecord:movie_id,url,width,height,type',
            ])
            ->orderByDesc('popularity_rank')
            ->orderByDesc('movies.startyear')
            ->orderBy('movies.primarytitle');
    }

    /**
     * @return array{
     *     title: Title,
     *     badgeLabel: string,
     *     weight: int|null,
     *     typeLabel: string,
     *     yearLabel: string|null,
     *     ratingLabel: string|null,
     *     note: string|null
     * }
     */
    private function makeConnectionItem(Title $title, string $badgeLabel, ?int $weight, ?string $note): array
    {
        $ratingLabel = $title->displayAverageRating();

        return [
            'title' => $title,
            'badgeLabel' => $badgeLabel,
            'weight' => $weight,
            'typeLabel' => $title->typeLabel(),
            'yearLabel' => $title->release_year ? (string) $title->release_year : null,
            'ratingLabel' => $ratingLabel !== null ? number_format($ratingLabel, 1) : null,
            'note' => $note,
        ];
    }
}
