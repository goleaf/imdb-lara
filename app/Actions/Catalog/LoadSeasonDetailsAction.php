<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\WatchState;
use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
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
     *     watchStatesByTitle: Collection<int, WatchState>,
     *     previousSeason: Season|null,
     *     nextSeason: Season|null,
     *     poster: MediaAsset|null,
     *     backdrop: MediaAsset|null,
     *     airedRangeLabel: string|null,
     *     episodeCount: int,
     *     currentSeasonRuntimeAverage: float|int|null
     * }
     */
    public function handle(Title $series, Season $season, ?User $user = null): array
    {
        $series->load([
            'seasons' => fn ($query) => $query
                ->select(['id', 'series_id', 'name', 'slug', 'season_number', 'summary', 'release_year'])
                ->withCount('episodes')
                ->orderBy('season_number'),
            'mediaAssets' => fn ($query) => $query
                ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position'])
                ->ordered(),
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count,episodes_count',
        ]);

        $season->load([
            'episodes' => fn ($query) => $query
                ->select([
                    'id',
                    'season_id',
                    'series_id',
                    'title_id',
                    'episode_number',
                    'season_number',
                    'absolute_number',
                    'production_code',
                    'aired_at',
                ])
                ->with([
                    'title' => fn ($titleQuery) => $titleQuery
                        ->select([
                            'id',
                            'name',
                            'slug',
                            'title_type',
                            'release_year',
                            'runtime_minutes',
                            'plot_outline',
                            'synopsis',
                            'is_published',
                        ])
                        ->with([
                            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                            'mediaAssets' => fn ($mediaQuery) => $mediaQuery
                                ->select([
                                    'id',
                                    'mediable_type',
                                    'mediable_id',
                                    'kind',
                                    'url',
                                    'alt_text',
                                    'caption',
                                    'position',
                                    'is_primary',
                                ])
                                ->whereIn('kind', [MediaKind::Still, MediaKind::Backdrop, MediaKind::Poster, MediaKind::Gallery])
                                ->ordered(),
                            'credits' => fn ($creditQuery) => $creditQuery
                                ->select([
                                    'id',
                                    'title_id',
                                    'person_id',
                                    'department',
                                    'job',
                                    'character_name',
                                    'billing_order',
                                ])
                                ->where('department', 'Cast')
                                ->with('person:id,name,slug')
                                ->orderBy('billing_order'),
                        ]),
                ])
                ->orderBy('episode_number'),
        ]);

        $seasonNavigation = $series->seasons->values();
        $episodeRows = $season->episodes
            ->filter(fn ($episodeMeta): bool => $episodeMeta->title instanceof Title)
            ->values();

        $currentSeasonIndex = $seasonNavigation->search(fn (Season $navigationSeason): bool => $navigationSeason->is($season));
        $previousSeason = $currentSeasonIndex !== false && $currentSeasonIndex > 0
            ? $seasonNavigation->get($currentSeasonIndex - 1)
            : null;
        $nextSeason = $currentSeasonIndex !== false && $currentSeasonIndex < ($seasonNavigation->count() - 1)
            ? $seasonNavigation->get($currentSeasonIndex + 1)
            : null;

        $topRatedEpisodes = $episodeRows
            ->filter(fn ($episodeMeta): bool => (int) ($episodeMeta->title->statistic?->rating_count ?? 0) > 0)
            ->sortByDesc(function ($episodeMeta): string {
                return sprintf(
                    '%05.2f-%08d',
                    (float) ($episodeMeta->title->statistic?->average_rating ?? 0),
                    (int) ($episodeMeta->title->statistic?->rating_count ?? 0),
                );
            })
            ->take(5)
            ->values();

        $watchStatesByTitle = collect();

        if ($user && $episodeRows->isNotEmpty()) {
            $watchStatesByTitle = $user->watchlistEntries()
                ->select(['list_items.title_id', 'list_items.watch_state'])
                ->whereIn('list_items.title_id', $episodeRows->pluck('title_id'))
                ->get()
                ->mapWithKeys(fn ($entry): array => [$entry->title_id => $entry->watch_state]);
        }

        $airedEpisodes = $episodeRows->filter(fn ($episodeMeta): bool => $episodeMeta->aired_at !== null);
        $airedRangeLabel = null;

        if ($airedEpisodes->isNotEmpty()) {
            $firstAired = $airedEpisodes->min('aired_at');
            $lastAired = $airedEpisodes->max('aired_at');
            $airedRangeLabel = $firstAired && $lastAired
                ? sprintf('%s to %s', $firstAired->format('M j, Y'), $lastAired->format('M j, Y'))
                : null;
        }

        $poster = MediaAsset::preferredFrom($series->mediaAssets, MediaKind::Poster, MediaKind::Backdrop);
        $backdrop = MediaAsset::preferredFrom($series->mediaAssets, MediaKind::Backdrop, MediaKind::Poster);
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'TV Shows', 'href' => route('public.series.index')],
            ['label' => $series->name, 'href' => route('public.titles.show', $series)],
            ['label' => $season->name],
        ];

        return [
            'series' => $series,
            'season' => $season,
            'seasonNavigation' => $seasonNavigation,
            'episodeRows' => $episodeRows,
            'topRatedEpisodes' => $topRatedEpisodes,
            'watchStatesByTitle' => $watchStatesByTitle,
            'previousSeason' => $previousSeason,
            'nextSeason' => $nextSeason,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'airedRangeLabel' => $airedRangeLabel,
            'episodeCount' => $episodeRows->count(),
            'currentSeasonRuntimeAverage' => $episodeRows->avg(fn ($episodeMeta) => $episodeMeta->title?->runtime_minutes),
            'seo' => new PageSeoData(
                title: $season->meta_title ?: ($season->name.' · '.$series->name),
                description: $season->meta_description ?: ($season->summary ?: 'Browse episode records for '.$season->name.' of '.$series->name.'.'),
                canonical: route('public.seasons.show', ['series' => $series, 'season' => $season]),
                openGraphType: 'video.tv_show',
                openGraphImage: ($backdrop ?? $poster)?->url,
                openGraphImageAlt: ($backdrop ?? $poster)?->alt_text ?: $season->name,
                breadcrumbs: $breadcrumbs,
            ),
        ];
    }
}
