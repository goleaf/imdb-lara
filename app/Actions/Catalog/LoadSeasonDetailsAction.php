<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadSeasonDetailsAction
{
    /**
     * @return array{
     *     series: Title,
     *     season: Season,
     *     seasonNavigation: Collection<int, Season>,
     *     episodeRows: Collection<int, Episode>,
     *     topRatedEpisodes: Collection<int, Episode>,
     *     previousSeason: Season|null,
     *     nextSeason: Season|null,
     *     poster: mixed,
     *     backdrop: mixed,
     *     airedRangeLabel: string|null,
     *     episodeCount: int,
     *     currentSeasonRuntimeAverage: float|int|null,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $series, Season $season): array
    {
        $series->load([
            'seasons:movie_id,season,episode_count',
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
            'statistic:movie_id,aggregate_rating,vote_count',
        ]);

        $season->load([
            'episodes' => fn ($query) => $query
                ->select(['episode_movie_id', 'movie_id', 'season', 'episode_number', 'release_year', 'release_month', 'release_day'])
                ->with([
                    'title' => fn ($titleQuery) => $titleQuery
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
                        ])
                        ->with([
                            'statistic:movie_id,aggregate_rating,vote_count',
                            'titleImages:id,movie_id,position,url,width,height,type',
                            'primaryImageRecord:movie_id,url,width,height,type',
                            'plotRecord:movie_id,plot',
                        ]),
                ])
                ->orderBy('episode_number'),
        ]);

        $seasonNavigation = $series->seasons->values();
        $episodeRows = $season->episodes
            ->filter(fn (Episode $episodeMeta): bool => $episodeMeta->title instanceof Title)
            ->values();
        $currentSeasonIndex = $seasonNavigation->search(fn (Season $navigationSeason): bool => $navigationSeason->season_number === $season->season_number);
        $previousSeason = $currentSeasonIndex !== false && $currentSeasonIndex > 0
            ? $seasonNavigation->get($currentSeasonIndex - 1)
            : null;
        $nextSeason = $currentSeasonIndex !== false && $currentSeasonIndex < ($seasonNavigation->count() - 1)
            ? $seasonNavigation->get($currentSeasonIndex + 1)
            : null;
        $topRatedEpisodes = $episodeRows
            ->sortByDesc(fn (Episode $episodeMeta): string => sprintf(
                '%05.2f-%09d',
                (float) ($episodeMeta->title?->statistic?->average_rating ?? 0),
                (int) ($episodeMeta->title?->statistic?->rating_count ?? 0),
            ))
            ->take(5)
            ->values();
        $airedEpisodes = $episodeRows->filter(fn (Episode $episodeMeta): bool => $episodeMeta->aired_at !== null);
        $airedRangeLabel = null;

        if ($airedEpisodes->isNotEmpty()) {
            $airedRangeLabel = sprintf(
                '%s to %s',
                $airedEpisodes->min('aired_at')?->format('M j, Y'),
                $airedEpisodes->max('aired_at')?->format('M j, Y'),
            );
        }

        $poster = $series->preferredPoster();
        $backdrop = $series->preferredBackdrop();

        return [
            'series' => $series,
            'season' => $season,
            'seasonNavigation' => $seasonNavigation,
            'episodeRows' => $episodeRows,
            'topRatedEpisodes' => $topRatedEpisodes,
            'previousSeason' => $previousSeason,
            'nextSeason' => $nextSeason,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'airedRangeLabel' => $airedRangeLabel,
            'episodeCount' => $episodeRows->count(),
            'currentSeasonRuntimeAverage' => $episodeRows->avg(fn (Episode $episodeMeta) => $episodeMeta->title?->runtime_minutes),
            'seo' => new PageSeoData(
                title: $season->meta_title ?: ($season->name.' · '.$series->name),
                description: $season->meta_description ?: ($season->summary ?: 'Browse episode records for '.$season->name.' of '.$series->name.'.'),
                canonical: route('public.seasons.show', ['series' => $series, 'season' => $season]),
                openGraphType: 'video.tv_show',
                openGraphImage: ($backdrop ?? $poster)?->url,
                openGraphImageAlt: ($backdrop ?? $poster)?->alt_text ?: $season->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'TV Shows', 'href' => route('public.series.index')],
                    ['label' => $series->name, 'href' => route('public.titles.show', $series)],
                    ['label' => $season->name],
                ],
            ),
        ];
    }
}
