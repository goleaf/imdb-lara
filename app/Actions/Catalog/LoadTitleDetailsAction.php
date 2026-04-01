<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\ReviewStatus;
use App\Enums\TitleType;
use App\Models\AwardNomination;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Review;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleRelationship;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class LoadTitleDetailsAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: MediaAsset|null,
     *     backdrop: MediaAsset|null,
     *     galleryAssets: Collection<int, MediaAsset>,
     *     castPreview: Collection<int, Credit>,
     *     crewPreview: Collection<int, array{role: string, department: string|null, credits: Collection<int, Credit>}>,
     *     reviews: EloquentCollection<int, Review>,
     *     detailItems: Collection<int, array{label: string, value: string}>,
     *     technicalSpecItems: Collection<int, array{label: string, value: string}>,
     *     ratingsBreakdown: Collection<int, array{score: int, count: int, percentage: int}>,
     *     relatedTitles: Collection<int, array{title: Title, relationship: TitleRelationship, label: string}>,
     *     awardHighlights: Collection<int, AwardNomination>,
     *     countries: Collection<int, string>,
     *     languages: Collection<int, string>,
     *     latestSeason: Season|null,
     *     latestSeasonEpisodes: Collection<int, Episode>,
     *     topRatedEpisodes: Collection<int, Episode>
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'genres:id,name,slug',
            'companies:id,name,slug,kind,country_code',
            'canonicalTitle:id,slug,is_published',
            'credits' => fn ($query) => $query
                ->select([
                    'id',
                    'title_id',
                    'person_id',
                    'department',
                    'job',
                    'character_name',
                    'billing_order',
                    'is_principal',
                    'credited_as',
                ])
                ->with('person:id,name,slug')
                ->orderBy('billing_order')
                ->orderBy('job'),
            'statistic:id,title_id,average_rating,rating_count,rating_distribution,review_count,watchlist_count,episodes_count,awards_nominated_count,awards_won_count',
            'mediaAssets' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'caption',
                    'width',
                    'height',
                    'provider',
                    'provider_key',
                    'language',
                    'duration_seconds',
                    'is_primary',
                    'position',
                    'published_at',
                ])
                ->ordered(),
            'titleVideos' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'caption',
                    'provider',
                    'provider_key',
                    'duration_seconds',
                    'published_at',
                ])
                ->whereIn('kind', [MediaKind::Trailer, MediaKind::Clip, MediaKind::Featurette])
                ->ordered()
                ->limit(4),
            'seasons' => fn ($query) => $query
                ->select(['id', 'series_id', 'name', 'slug', 'season_number', 'summary', 'release_year', 'meta_title', 'meta_description'])
                ->withCount('episodes')
                ->orderBy('season_number'),
            'reviews' => fn ($query) => $query
                ->select([
                    'id',
                    'user_id',
                    'title_id',
                    'headline',
                    'body',
                    'contains_spoilers',
                    'status',
                    'published_at',
                ])
                ->where('status', ReviewStatus::Published)
                ->withCount([
                    'votes as helpful_votes_count' => fn ($voteQuery) => $voteQuery->where('is_helpful', true),
                ])
                ->with('author:id,name,username')
                ->latest('published_at')
                ->limit(6),
            'awardNominations' => fn ($query) => $query
                ->select([
                    'id',
                    'award_event_id',
                    'award_category_id',
                    'title_id',
                    'person_id',
                    'company_id',
                    'episode_id',
                    'credited_name',
                    'details',
                    'is_winner',
                    'sort_order',
                ])
                ->with([
                    'awardEvent:id,award_id,name,slug,year',
                    'awardEvent.award:id,name,slug',
                    'awardCategory:id,award_id,name,slug',
                    'person:id,name,slug',
                    'company:id,name,slug',
                    'episode:id,title_id,series_id,season_id,season_number,episode_number',
                    'episode.title:id,name,slug',
                ])
                ->orderByDesc('is_winner')
                ->orderBy('sort_order'),
            'outgoingRelationships' => fn ($query) => $query
                ->select(['id', 'from_title_id', 'to_title_id', 'relationship_type', 'weight', 'notes'])
                ->with([
                    'toTitle' => fn ($titleQuery) => $titleQuery
                        ->select([
                            'id',
                            'name',
                            'slug',
                            'title_type',
                            'release_year',
                            'plot_outline',
                            'is_published',
                        ])
                        ->published()
                        ->with([
                            'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
                            'genres:id,name,slug',
                            'statistic:id,title_id,average_rating,review_count',
                        ]),
                ])
                ->orderByDesc('weight'),
            'incomingRelationships' => fn ($query) => $query
                ->select(['id', 'from_title_id', 'to_title_id', 'relationship_type', 'weight', 'notes'])
                ->with([
                    'fromTitle' => fn ($titleQuery) => $titleQuery
                        ->select([
                            'id',
                            'name',
                            'slug',
                            'title_type',
                            'release_year',
                            'plot_outline',
                            'is_published',
                        ])
                        ->published()
                        ->with([
                            'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
                            'genres:id,name,slug',
                            'statistic:id,title_id,average_rating,review_count',
                        ]),
                ])
                ->orderByDesc('weight'),
        ]);

        $poster = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Poster, MediaKind::Backdrop);
        $backdrop = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Backdrop, MediaKind::Poster);
        $galleryAssets = $title->mediaAssets
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array($mediaAsset->kind, [
                MediaKind::Poster,
                MediaKind::Backdrop,
                MediaKind::Gallery,
                MediaKind::Still,
            ], true))
            ->take(8)
            ->values();

        $castPreview = $title->credits
            ->where('department', 'Cast')
            ->take(8)
            ->values();

        $crewPriority = [
            'Director' => 1,
            'Creator' => 2,
            'Writer' => 3,
            'Screenplay' => 4,
            'Producer' => 5,
            'Composer' => 6,
            'Cinematographer' => 7,
            'Editor' => 8,
        ];

        $crewPreview = $title->credits
            ->where('department', '!=', 'Cast')
            ->groupBy(fn (Credit $credit): string => filled($credit->job) ? $credit->job : $credit->department)
            ->map(fn (Collection $credits, string $role): array => [
                'role' => $role,
                'department' => $credits->first()?->department,
                'credits' => $credits->take(3)->values(),
            ])
            ->sortBy(fn (array $group): int => $crewPriority[$group['role']] ?? 99)
            ->take(6)
            ->values();

        $ratingDistribution = $title->statistic?->normalizedRatingDistribution()
            ?? TitleStatistic::normalizeRatingDistribution();
        $ratingsTotal = array_sum($ratingDistribution);
        $ratingsBreakdown = collect($ratingDistribution)
            ->map(function (int $count, string $score) use ($ratingsTotal): array {
                return [
                    'score' => (int) $score,
                    'count' => $count,
                    'percentage' => $ratingsTotal > 0
                        ? (int) round(($count / $ratingsTotal) * 100)
                        : 0,
                ];
            })
            ->values();

        $detailItems = collect([
            ['label' => 'Original title', 'value' => $title->original_name !== $title->name ? $title->original_name : null],
            ['label' => 'Release date', 'value' => $title->release_date?->format('M j, Y')],
            ['label' => 'Country of origin', 'value' => $title->origin_country ? str($title->origin_country)->upper()->toString() : null],
            ['label' => 'Original language', 'value' => $title->original_language ? str($title->original_language)->upper()->toString() : null],
            ['label' => 'Certification', 'value' => $title->age_rating],
            ['label' => 'Production companies', 'value' => $title->companies->pluck('name')->implode(', ')],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();

        $technicalSpecItems = collect([
            ['label' => 'Runtime', 'value' => $title->runtime_minutes ? sprintf('%d min', $title->runtime_minutes) : null],
            ['label' => 'Format', 'value' => str($title->title_type->value)->headline()->toString()],
            ['label' => 'Published seasons', 'value' => $title->seasons->isNotEmpty() ? (string) $title->seasons->count() : null],
            ['label' => 'Published episodes', 'value' => $title->statistic?->episodes_count ? (string) $title->statistic->episodes_count : null],
            ['label' => 'Awards won', 'value' => $title->statistic?->awards_won_count ? (string) $title->statistic->awards_won_count : null],
            ['label' => 'Media assets', 'value' => $galleryAssets->isNotEmpty() ? (string) $galleryAssets->count() : null],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();

        $relatedTitles = $title->outgoingRelationships
            ->map(fn (TitleRelationship $relationship): array => [
                'title' => $relationship->toTitle,
                'relationship' => $relationship,
                'label' => str($relationship->relationship_type->value)->headline()->toString(),
            ])
            ->merge(
                $title->incomingRelationships->map(fn (TitleRelationship $relationship): array => [
                    'title' => $relationship->fromTitle,
                    'relationship' => $relationship,
                    'label' => str($relationship->relationship_type->value)->headline()->toString(),
                ])
            )
            ->filter(fn (array $item): bool => $item['title'] instanceof Title)
            ->unique(fn (array $item): string => $item['relationship']->relationship_type->value.'-'.$item['title']->id)
            ->sortByDesc(fn (array $item): int => (int) ($item['relationship']->weight ?? 0))
            ->take(6)
            ->values();

        $awardHighlights = $title->awardNominations
            ->sortByDesc(fn (AwardNomination $awardNomination): int => (int) $awardNomination->is_winner)
            ->take(6)
            ->values();

        $latestSeason = null;
        $latestSeasonEpisodes = collect();
        $topRatedEpisodes = collect();

        if (in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true) && $title->seasons->isNotEmpty()) {
            $latestSeason = $title->seasons
                ->sortByDesc('season_number')
                ->first();

            if ($latestSeason instanceof Season) {
                $latestSeason->load([
                    'episodes' => fn ($query) => $query
                        ->select([
                            'id',
                            'season_id',
                            'series_id',
                            'title_id',
                            'episode_number',
                            'season_number',
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
                                    'is_published',
                                ])
                                ->published()
                                ->with([
                                    'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                                    'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
                                ]),
                        ])
                        ->orderBy('episode_number')
                        ->limit(4),
                ]);

                $latestSeasonEpisodes = $latestSeason->episodes
                    ->filter(fn (Episode $episodeMeta): bool => $episodeMeta->title instanceof Title)
                    ->values();
            }

            $topRatedEpisodes = $title->seriesEpisodes()
                ->select([
                    'id',
                    'season_id',
                    'series_id',
                    'title_id',
                    'episode_number',
                    'season_number',
                    'aired_at',
                ])
                ->with([
                    'season:id,series_id,name,slug,season_number',
                    'title' => fn ($titleQuery) => $titleQuery
                        ->select([
                            'id',
                            'name',
                            'slug',
                            'title_type',
                            'release_year',
                            'runtime_minutes',
                            'plot_outline',
                            'is_published',
                        ])
                        ->published()
                        ->with([
                            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                        ]),
                ])
                ->get()
                ->filter(fn (Episode $episodeMeta): bool => $episodeMeta->title instanceof Title)
                ->filter(fn (Episode $episodeMeta): bool => (int) ($episodeMeta->title->statistic?->rating_count ?? 0) > 0)
                ->sortByDesc(function (Episode $episodeMeta): string {
                    return sprintf(
                        '%05.2f-%08d',
                        (float) ($episodeMeta->title->statistic?->average_rating ?? 0),
                        (int) ($episodeMeta->title->statistic?->rating_count ?? 0),
                    );
                })
                ->take(5)
                ->values();
        }

        $canonicalTitle = $title->canonicalTitle;
        $canonicalUrl = $canonicalTitle instanceof Title && $canonicalTitle->is_published
            ? route('public.titles.show', $canonicalTitle)
            : route('public.titles.show', $title);
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Titles', 'href' => route('public.titles.index')],
            ['label' => $title->name],
        ];
        $openGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'galleryAssets' => $galleryAssets,
            'castPreview' => $castPreview,
            'crewPreview' => $crewPreview,
            'reviews' => $title->reviews,
            'detailItems' => $detailItems,
            'technicalSpecItems' => $technicalSpecItems,
            'ratingsBreakdown' => $ratingsBreakdown,
            'relatedTitles' => $relatedTitles,
            'awardHighlights' => $awardHighlights,
            'countries' => $this->tokenizeList($title->origin_country),
            'languages' => $this->tokenizeList($title->original_language),
            'latestSeason' => $latestSeason,
            'latestSeasonEpisodes' => $latestSeasonEpisodes,
            'topRatedEpisodes' => $topRatedEpisodes,
            'seo' => new PageSeoData(
                title: $title->meta_title ?: $title->name,
                description: $title->meta_description ?: ($title->plot_outline ?: 'Read credits, ratings, and reviews for '.$title->name.'.'),
                canonical: $canonicalUrl,
                openGraphType: $openGraphType,
                openGraphImage: ($backdrop ?? $poster)?->url,
                openGraphImageAlt: ($backdrop ?? $poster)?->alt_text ?: $title->name,
                breadcrumbs: $breadcrumbs,
            ),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function tokenizeList(?string $value): Collection
    {
        return collect(preg_split('/[,|]/', (string) $value) ?: [])
            ->map(fn (string $item): string => str($item)->trim()->upper()->toString())
            ->filter()
            ->values();
    }
}
