<?php

namespace App\Actions\Catalog;

use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class LoadEpisodeDetailsAction
{
    /**
     * @return array{
     *     series: Title,
     *     season: Season,
     *     episode: Title,
     *     still: MediaAsset|null,
     *     seasonNavigation: Collection<int, Season>,
     *     seasonEpisodes: Collection<int, Episode>,
     *     previousEpisode: Episode|null,
     *     nextEpisode: Episode|null,
     *     guestCast: Collection<int, Credit>,
     *     keyCrew: Collection<int, array{role: string, credits: Collection<int, Credit>}>,
     *     reviews: EloquentCollection<int, Review>,
     *     detailItems: Collection<int, array{label: string, value: string}>
     * }
     */
    public function handle(Title $series, Season $season, Title $episode): array
    {
        $series->load([
            'seasons' => fn ($query) => $query
                ->select(['id', 'series_id', 'name', 'slug', 'season_number', 'release_year'])
                ->withCount('episodes')
                ->orderBy('season_number'),
            'mediaAssets' => fn ($query) => $query
                ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position'])
                ->orderBy('position'),
        ]);

        $season->load([
            'episodes' => fn ($query) => $query
                ->select(['id', 'season_id', 'series_id', 'title_id', 'episode_number', 'season_number', 'aired_at'])
                ->with([
                    'title:id,name,slug,title_type,runtime_minutes,plot_outline',
                    'title.statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                ])
                ->orderBy('episode_number'),
        ]);

        $episode->load([
            'episodeMeta:id,title_id,series_id,season_id,season_number,episode_number,absolute_number,production_code,aired_at',
            'episodeMeta.season:id,series_id,name,slug,season_number',
            'episodeMeta.series:id,name,slug,title_type,release_year',
            'genres:id,name,slug',
            'credits' => fn ($query) => $query
                ->select([
                    'id',
                    'title_id',
                    'person_id',
                    'department',
                    'job',
                    'character_name',
                    'billing_order',
                    'credited_as',
                ])
                ->with('person:id,name,slug')
                ->orderBy('department')
                ->orderBy('billing_order'),
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            'mediaAssets' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'caption',
                    'position',
                    'published_at',
                ])
                ->orderBy('position')
                ->orderByDesc('published_at'),
            'reviews' => fn ($query) => $query
                ->select([
                    'id',
                    'user_id',
                    'title_id',
                    'headline',
                    'body',
                    'contains_spoilers',
                    'published_at',
                    'status',
                ])
                ->where('status', ReviewStatus::Published)
                ->withCount([
                    'votes as helpful_votes_count' => fn ($voteQuery) => $voteQuery->where('is_helpful', true),
                ])
                ->with('author:id,name,username')
                ->latest('published_at')
                ->limit(5),
        ]);

        $seasonNavigation = $series->seasons->values();
        $seasonEpisodes = $season->episodes
            ->filter(fn ($episodeMeta): bool => $episodeMeta->title instanceof Title)
            ->values();

        $currentEpisodeIndex = $seasonEpisodes->search(fn ($episodeMeta): bool => $episodeMeta->title_id === $episode->id);
        $previousEpisode = $currentEpisodeIndex !== false && $currentEpisodeIndex > 0
            ? $seasonEpisodes->get($currentEpisodeIndex - 1)
            : null;
        $nextEpisode = $currentEpisodeIndex !== false && $currentEpisodeIndex < ($seasonEpisodes->count() - 1)
            ? $seasonEpisodes->get($currentEpisodeIndex + 1)
            : null;

        $episodeCredits = $episode->credits
            ->concat(
                $episode->episodeMeta
                    ? $series->credits()
                        ->select([
                            'id',
                            'title_id',
                            'person_id',
                            'department',
                            'job',
                            'character_name',
                            'billing_order',
                            'credited_as',
                            'episode_id',
                        ])
                        ->where('episode_id', $episode->episodeMeta->id)
                        ->with('person:id,name,slug')
                        ->orderBy('department')
                        ->orderBy('billing_order')
                        ->get()
                    : collect()
            )
            ->unique('id')
            ->values();

        $guestCast = $episodeCredits
            ->where('department', 'Cast')
            ->values();

        $crewPriority = [
            'Director' => 1,
            'Writer' => 2,
            'Producer' => 3,
            'Composer' => 4,
            'Editor' => 5,
        ];

        $keyCrew = $episodeCredits
            ->where('department', '!=', 'Cast')
            ->groupBy(fn (Credit $credit): string => filled($credit->job) ? $credit->job : $credit->department)
            ->map(fn (Collection $credits, string $role): array => [
                'role' => $role,
                'credits' => $credits->take(3)->values(),
            ])
            ->sortBy(fn (array $group): int => $crewPriority[$group['role']] ?? 99)
            ->values();

        $detailItems = collect([
            ['label' => 'Air date', 'value' => $episode->episodeMeta?->aired_at?->format('M j, Y')],
            ['label' => 'Runtime', 'value' => $episode->runtime_minutes ? sprintf('%d min', $episode->runtime_minutes) : null],
            ['label' => 'Production code', 'value' => $episode->episodeMeta?->production_code],
            ['label' => 'Absolute number', 'value' => $episode->episodeMeta?->absolute_number ? (string) $episode->episodeMeta->absolute_number : null],
            ['label' => 'Season / episode', 'value' => $episode->episodeMeta ? sprintf('S%02dE%02d', $episode->episodeMeta->season_number, $episode->episodeMeta->episode_number) : null],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();

        return [
            'series' => $series,
            'season' => $season,
            'episode' => $episode,
            'still' => $episode->mediaAssets->firstWhere('kind', MediaKind::Still)
                ?? $episode->mediaAssets->firstWhere('kind', MediaKind::Backdrop)
                ?? $series->mediaAssets->firstWhere('kind', MediaKind::Backdrop)
                ?? $episode->mediaAssets->first(),
            'seasonNavigation' => $seasonNavigation,
            'seasonEpisodes' => $seasonEpisodes,
            'previousEpisode' => $previousEpisode,
            'nextEpisode' => $nextEpisode,
            'guestCast' => $guestCast,
            'keyCrew' => $keyCrew,
            'reviews' => $episode->reviews,
            'detailItems' => $detailItems,
        ];
    }
}
