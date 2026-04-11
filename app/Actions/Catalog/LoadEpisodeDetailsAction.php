<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadEpisodeDetailsAction
{
    /**
     * @return array{
     *     series: Title,
     *     season: Season,
     *     episode: Title,
     *     episodeMeta: Episode|null,
     *     still: mixed,
     *     seasonNavigation: Collection<int, Season>,
     *     seasonEpisodes: Collection<int, Episode>,
     *     previousEpisode: Episode|null,
     *     nextEpisode: Episode|null,
     *     guestCast: Collection<int, Credit>,
     *     keyCrew: Collection<int, array{role: string, credits: Collection<int, Credit>}>,
     *     detailItems: Collection<int, array{label: string, value: string}>,
     *     ratingCount: int,
     *     previousEpisodeTitle: Title|null,
     *     nextEpisodeTitle: Title|null,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $series, Season $season, Title $episode): array
    {
        $series->load([
            'seasons:movie_id,season,episode_count',
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
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
                            'plotRecord:movie_id,plot',
                        ]),
                ])
                ->orderBy('episode_number'),
        ]);

        $episode->load([
            'episodeMeta:episode_movie_id,movie_id,season,episode_number,release_year,release_month,release_day',
            'genres:id,name',
            'credits' => fn ($query) => $query
                ->select(['name_basic_id', 'movie_id', 'category', 'episode_count', 'position'])
                ->with([
                    'person' => fn ($personQuery) => $personQuery
                        ->select([
                            'id',
                            'nconst',
                            'imdb_id',
                            'primaryname',
                            'displayName',
                            'alternativeNames',
                            'primaryProfessions',
                            'biography',
                            'birthLocation',
                            'deathLocation',
                            'primaryImage_url',
                            'primaryImage_width',
                            'primaryImage_height',
                        ])
                        ->with([
                            'personImages:name_basic_id,position,url,width,height,type',
                            'professionTerms:id,name',
                        ]),
                ])
                ->orderBy('position')
                ->limit(24),
            'statistic:movie_id,aggregate_rating,vote_count',
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
            'plotRecord:movie_id,plot',
        ]);

        $seasonNavigation = $series->seasons->values();
        $seasonEpisodes = $season->episodes
            ->filter(fn (Episode $episodeMeta): bool => $episodeMeta->title instanceof Title)
            ->values();
        $currentEpisodeIndex = $seasonEpisodes->search(fn (Episode $episodeMeta): bool => $episodeMeta->title_id === $episode->id);
        $previousEpisode = $currentEpisodeIndex !== false && $currentEpisodeIndex > 0
            ? $seasonEpisodes->get($currentEpisodeIndex - 1)
            : null;
        $nextEpisode = $currentEpisodeIndex !== false && $currentEpisodeIndex < ($seasonEpisodes->count() - 1)
            ? $seasonEpisodes->get($currentEpisodeIndex + 1)
            : null;
        $guestCast = $episode->credits
            ->filter(fn (Credit $credit): bool => $credit->department === 'Cast')
            ->values();
        $keyCrew = $episode->credits
            ->reject(fn (Credit $credit): bool => $credit->department === 'Cast')
            ->groupBy(fn (Credit $credit): string => $credit->job ?: $credit->department)
            ->map(fn (Collection $credits, string $role): array => [
                'role' => $role,
                'credits' => $credits->take(4)->values(),
            ])
            ->values();
        $detailItems = collect([
            ['label' => 'Air date', 'value' => $episode->episodeMeta?->aired_at?->format('M j, Y')],
            ['label' => 'Runtime', 'value' => $episode->runtime_minutes ? $episode->runtime_minutes.' min' : null],
            ['label' => 'Season / episode', 'value' => $episode->episodeMeta ? sprintf('S%02dE%02d', $episode->episodeMeta->season_number, $episode->episodeMeta->episode_number) : null],
            ['label' => 'Series', 'value' => $series->name],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();
        $still = $episode->preferredDisplayImage() ?? $series->preferredBackdrop() ?? $series->preferredPoster();

        return [
            'series' => $series,
            'season' => $season,
            'episode' => $episode,
            'episodeMeta' => $episode->episodeMeta,
            'still' => $still,
            'seasonNavigation' => $seasonNavigation,
            'seasonEpisodes' => $seasonEpisodes,
            'previousEpisode' => $previousEpisode,
            'nextEpisode' => $nextEpisode,
            'previousEpisodeTitle' => $previousEpisode?->title,
            'nextEpisodeTitle' => $nextEpisode?->title,
            'guestCast' => $guestCast,
            'keyCrew' => $keyCrew,
            'detailItems' => $detailItems,
            'ratingCount' => (int) ($episode->statistic?->rating_count ?? 0),
            'seo' => new PageSeoData(
                title: $episode->meta_title ?: $episode->name,
                description: $episode->meta_description ?: ($episode->plot_outline ?: 'Browse cast, metadata, and gallery items for '.$episode->name.'.'),
                canonical: route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episode]),
                openGraphType: 'video.tv_show',
                openGraphImage: $still?->url,
                openGraphImageAlt: $still?->alt_text ?: $episode->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'TV Shows', 'href' => route('public.series.index')],
                    ['label' => $series->name, 'href' => route('public.titles.show', $series)],
                    ['label' => $season->name, 'href' => route('public.seasons.show', ['series' => $series, 'season' => $season])],
                    ['label' => $episode->name],
                ],
            ),
        ];
    }
}
