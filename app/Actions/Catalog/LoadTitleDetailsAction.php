<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\AwardNomination;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleRelationship;
use App\Models\TitleStatistic;
use Illuminate\Support\Collection;

class LoadTitleDetailsAction
{
    public function __construct(private LoadTitleTriviaAndGoofsAction $loadTitleTriviaAndGoofs) {}

    /**
     * @return array{
     *     title: Title,
     *     poster: MediaAsset|null,
     *     backdrop: MediaAsset|null,
     *     galleryAssets: Collection<int, MediaAsset>,
     *     castPreview: Collection<int, Credit>,
     *     crewPreview: Collection<int, array{role: string, department: string|null, credits: Collection<int, Credit>}>,
     *     detailItems: Collection<int, array{label: string, value: string}>,
     *     technicalSpecItems: Collection<int, array{label: string, value: string}>,
     *     keywordItems: Collection<int, string>,
     *     releaseDateItems: Collection<int, array{country: string, date: string}>,
     *     parentGuideItems: Collection<int, array{category: string, severity: string|null, severityColor: string, text: string}>,
     *     parentGuideSpoilers: Collection<int, string>,
     *     certificateItems: Collection<int, array{rating: string, country: string|null, attributes: string|null}>,
     *     boxOfficeItems: Collection<int, array{label: string, value: string}>,
     *     triviaItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     goofItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     triviaTotalCount: int,
     *     goofTotalCount: int,
     *     ratingsBreakdown: Collection<int, array{score: int, count: int, percentage: int}>,
     *     relatedTitles: Collection<int, array{title: Title, relationship: TitleRelationship, label: string}>,
     *     awardHighlights: Collection<int, AwardNomination>,
     *     countries: Collection<int, string>,
     *     languages: Collection<int, string>,
     *     latestSeason: Season|null,
     *     latestSeasonEpisodes: Collection<int, Episode>,
     *     topRatedEpisodes: Collection<int, Episode>,
     *     shareModalId: string,
     *     shareUrl: string,
     *     isSeriesLike: bool,
     *     ratingCount: int,
     *     maxBreakdownCount: int,
     *     titleDirectory: Collection<int, array{href: string, label: string}>,
     *     heroStats: Collection<int, array{label: string, value: string, copy: string}>
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
        $keywordItems = $this->buildKeywordItems($title);
        $releaseDateItems = $this->buildReleaseDateItems($title);
        $parentGuideItems = $this->buildParentGuideItems($title);
        $parentGuideSpoilers = $this->buildParentGuideSpoilers($title);
        $certificateItems = $this->buildCertificateItems($title);
        $boxOfficeItems = $this->buildBoxOfficeItems($title);
        $triviaAndGoofsSummary = $this->loadTitleTriviaAndGoofs->summarize($title);

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
                            'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
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
        $isSeriesLike = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true);
        $ratingCount = (int) ($title->statistic?->rating_count ?? 0);
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Titles', 'href' => route('public.titles.index')],
            ['label' => $title->name],
        ];
        $openGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';
        $titleDirectory = collect([
            ['href' => '#title-storyline', 'label' => 'Plot'],
            ['href' => '#title-credits', 'label' => 'Cast & crew'],
            ['href' => '#title-awards', 'label' => 'Awards'],
            ['href' => '#title-keywords', 'label' => 'Keywords'],
            ['href' => '#title-parents-guide', 'label' => 'Parents guide'],
            ['href' => '#title-trivia', 'label' => 'Trivia'],
            ['href' => '#title-goofs', 'label' => 'Goofs'],
            ['href' => '#title-release-dates', 'label' => 'Release dates'],
            ['href' => '#title-technical-specs', 'label' => 'Technical specs'],
            ['href' => '#title-where-to-watch', 'label' => 'Where to watch'],
            ['href' => '#title-box-office', 'label' => 'Box office'],
            ['href' => '#title-related', 'label' => 'Related titles'],
            ['href' => '#title-reviews', 'label' => 'Reviews'],
        ]);
        $heroStats = $isSeriesLike
            ? collect([
                [
                    'label' => 'Audience',
                    'value' => $title->statistic?->average_rating ? number_format((float) $title->statistic->average_rating, 1) : 'N/A',
                    'copy' => number_format($ratingCount).' ratings',
                ],
                [
                    'label' => 'Seasons',
                    'value' => number_format($title->seasons->count()),
                    'copy' => 'Published runs',
                ],
                [
                    'label' => 'Episodes',
                    'value' => number_format((int) ($title->statistic?->episodes_count ?? 0)),
                    'copy' => 'Episode records',
                ],
                [
                    'label' => 'Reviews',
                    'value' => number_format((int) ($title->statistic?->review_count ?? 0)),
                    'copy' => 'Published reviews',
                ],
            ])
            : collect([
                [
                    'label' => 'Audience',
                    'value' => $title->statistic?->average_rating ? number_format((float) $title->statistic->average_rating, 1) : 'N/A',
                    'copy' => number_format($ratingCount).' ratings',
                ],
                [
                    'label' => 'Reviews',
                    'value' => number_format((int) ($title->statistic?->review_count ?? 0)),
                    'copy' => 'Published reviews',
                ],
                [
                    'label' => 'Watchlists',
                    'value' => number_format((int) ($title->statistic?->watchlist_count ?? 0)),
                    'copy' => 'Members tracking',
                ],
                [
                    'label' => 'Awards',
                    'value' => number_format((int) ($title->statistic?->awards_won_count ?? 0)),
                    'copy' => number_format((int) ($title->statistic?->awards_nominated_count ?? 0)).' nominations',
                ],
            ]);

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'galleryAssets' => $galleryAssets,
            'castPreview' => $castPreview,
            'crewPreview' => $crewPreview,
            'detailItems' => $detailItems,
            'technicalSpecItems' => $technicalSpecItems,
            'keywordItems' => $keywordItems,
            'releaseDateItems' => $releaseDateItems,
            'parentGuideItems' => $parentGuideItems,
            'parentGuideSpoilers' => $parentGuideSpoilers,
            'certificateItems' => $certificateItems,
            'boxOfficeItems' => $boxOfficeItems,
            'triviaItems' => $triviaAndGoofsSummary['triviaItems'],
            'goofItems' => $triviaAndGoofsSummary['goofItems'],
            'triviaTotalCount' => $triviaAndGoofsSummary['triviaTotalCount'],
            'goofTotalCount' => $triviaAndGoofsSummary['goofTotalCount'],
            'ratingsBreakdown' => $ratingsBreakdown,
            'relatedTitles' => $relatedTitles,
            'awardHighlights' => $awardHighlights,
            'countries' => $this->tokenizeList($title->origin_country),
            'languages' => $this->tokenizeList($title->original_language),
            'latestSeason' => $latestSeason,
            'latestSeasonEpisodes' => $latestSeasonEpisodes,
            'topRatedEpisodes' => $topRatedEpisodes,
            'shareModalId' => 'share-title-'.$title->id,
            'shareUrl' => route('public.titles.show', $title),
            'isSeriesLike' => $isSeriesLike,
            'ratingCount' => $ratingCount,
            'maxBreakdownCount' => max(1, (int) $ratingsBreakdown->max('count')),
            'titleDirectory' => $titleDirectory,
            'heroStats' => $heroStats,
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

    /**
     * @return Collection<int, string>
     */
    private function buildKeywordItems(Title $title): Collection
    {
        $interestKeywords = collect($title->imdb_interests ?? [])
            ->map(function (mixed $interest): ?string {
                if (is_array($interest)) {
                    return $this->nullableString(data_get($interest, 'name'));
                }

                return null;
            });

        return $this->tokenizeKeywords($title->search_keywords)
            ->merge($interestKeywords)
            ->filter()
            ->map(fn (string $keyword): string => str($keyword)->trim()->headline()->toString())
            ->unique(fn (string $keyword): string => str($keyword)->lower()->toString())
            ->take(10)
            ->values();
    }

    /**
     * @return Collection<int, array{country: string, date: string}>
     */
    private function buildReleaseDateItems(Title $title): Collection
    {
        $releaseDates = collect(data_get($title->imdbPayloadSection('releaseDates'), 'releaseDates', []))
            ->map(function (mixed $releaseDate): ?array {
                if (! is_array($releaseDate)) {
                    return null;
                }

                $country = $this->nullableString(data_get($releaseDate, 'country.name'))
                    ?? $this->nullableString(data_get($releaseDate, 'country.code'))
                    ?? 'Global';
                $date = $this->formatPrecisionDate(data_get($releaseDate, 'releaseDate'));

                if ($date === null) {
                    return null;
                }

                return [
                    'country' => $country,
                    'date' => $date,
                ];
            })
            ->filter()
            ->unique(fn (array $item): string => $item['country'].'-'.$item['date'])
            ->take(8)
            ->values();

        if ($releaseDates->isNotEmpty()) {
            return $releaseDates;
        }

        if ($title->release_date !== null) {
            return collect([
                [
                    'country' => $title->origin_country ? str($title->origin_country)->upper()->toString() : 'Global',
                    'date' => $title->release_date->format('M j, Y'),
                ],
            ]);
        }

        if ($title->release_year !== null) {
            return collect([
                [
                    'country' => $title->origin_country ? str($title->origin_country)->upper()->toString() : 'Global',
                    'date' => (string) $title->release_year,
                ],
            ]);
        }

        return collect();
    }

    /**
     * @return Collection<int, array{category: string, severity: string|null, severityColor: string, text: string}>
     */
    private function buildParentGuideItems(Title $title): Collection
    {
        return collect(data_get($title->imdbPayloadSection('parentsGuide'), 'advisories', []))
            ->map(function (mixed $advisory): ?array {
                if (! is_array($advisory)) {
                    return null;
                }

                $reviewText = collect(data_get($advisory, 'reviews', []))
                    ->map(fn (mixed $review): ?string => is_array($review) ? $this->nullableString(data_get($review, 'text')) : null)
                    ->filter()
                    ->implode(' ');
                $text = $this->nullableString(data_get($advisory, 'text'))
                    ?? ($reviewText !== '' ? $reviewText : null);

                if ($text === null) {
                    return null;
                }

                return [
                    'category' => str($this->nullableString(data_get($advisory, 'category')) ?? 'Advisory')
                        ->replace(['_', '-'], ' ')
                        ->headline()
                        ->toString(),
                    'severity' => ($severity = $this->nullableString(data_get($advisory, 'severity')))
                        ? str($severity)->replace(['_', '-'], ' ')->headline()->toString()
                        : null,
                    'severityColor' => match (str($this->nullableString(data_get($advisory, 'severity')) ?? '')->lower()->toString()) {
                        'severe' => 'red',
                        'moderate' => 'amber',
                        'mild' => 'slate',
                        default => 'neutral',
                    },
                    'text' => $text,
                ];
            })
            ->filter()
            ->take(6)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function buildParentGuideSpoilers(Title $title): Collection
    {
        return collect(data_get($title->imdbPayloadSection('parentsGuide'), 'spoilers', []))
            ->map(fn (mixed $spoiler): ?string => is_string($spoiler) ? trim($spoiler) : null)
            ->filter()
            ->take(3)
            ->values();
    }

    /**
     * @return Collection<int, array{rating: string, country: string|null, attributes: string|null}>
     */
    private function buildCertificateItems(Title $title): Collection
    {
        return collect(data_get($title->imdbPayloadSection('certificates'), 'certificates', []))
            ->map(function (mixed $certificate): ?array {
                if (! is_array($certificate)) {
                    return null;
                }

                $rating = $this->nullableString(data_get($certificate, 'rating'));

                if ($rating === null) {
                    return null;
                }

                $attributes = collect(data_get($certificate, 'attributes', []))
                    ->map(fn (mixed $attribute): ?string => is_string($attribute) ? str($attribute)->replace(['_', '-'], ' ')->headline()->toString() : null)
                    ->filter()
                    ->take(2)
                    ->implode(', ');

                return [
                    'rating' => $rating,
                    'country' => $this->nullableString(data_get($certificate, 'country.name'))
                        ?? $this->nullableString(data_get($certificate, 'country.code')),
                    'attributes' => $attributes !== '' ? $attributes : null,
                ];
            })
            ->filter()
            ->take(6)
            ->values();
    }

    /**
     * @return Collection<int, array{label: string, value: string}>
     */
    private function buildBoxOfficeItems(Title $title): Collection
    {
        $boxOffice = $title->imdbPayloadSection('boxOffice');

        if ($boxOffice === null) {
            return collect();
        }

        $boxOfficeItems = collect([
            ['label' => 'Budget', 'value' => $this->formatMoney(data_get($boxOffice, 'budget.amount'), data_get($boxOffice, 'budget.currency'))],
            ['label' => 'Opening weekend', 'value' => $this->formatMoney(data_get($boxOffice, 'openingWeekendGross.amount'), data_get($boxOffice, 'openingWeekendGross.currency'))],
            ['label' => 'Domestic gross', 'value' => $this->formatMoney(data_get($boxOffice, 'domesticGross.amount'), data_get($boxOffice, 'domesticGross.currency'))],
            ['label' => 'Worldwide gross', 'value' => $this->formatMoney(data_get($boxOffice, 'worldwideGross.amount'), data_get($boxOffice, 'worldwideGross.currency'))],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();

        $theatricalRun = collect(data_get($boxOffice, 'theatricalRuns', []))
            ->map(function (mixed $run): ?string {
                if (! is_array($run)) {
                    return null;
                }

                $weeks = data_get($run, 'weeks');
                $market = $this->nullableString(data_get($run, 'market'));

                if ($weeks === null && $market === null) {
                    return null;
                }

                $weeksLabel = $weeks !== null ? number_format((int) $weeks).' weeks' : null;

                return collect([$weeksLabel, $market])
                    ->filter()
                    ->implode(' in ');
            })
            ->filter()
            ->first();

        if ($theatricalRun !== null) {
            $boxOfficeItems->push([
                'label' => 'Theatrical run',
                'value' => $theatricalRun,
            ]);
        }

        return $boxOfficeItems->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function tokenizeKeywords(?string $value): Collection
    {
        return collect(preg_split('/[,|]/', (string) $value) ?: [])
            ->map(fn (string $item): string => str($item)->trim()->toString())
            ->filter()
            ->values();
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function formatMoney(mixed $amount, mixed $currency): ?string
    {
        if (! is_scalar($amount) || ! is_numeric((string) $amount)) {
            return null;
        }

        $formattedAmount = number_format((float) $amount, 0, '.', ',');
        $currencyCode = $this->nullableString(is_scalar($currency) ? (string) $currency : null);

        if ($currencyCode === null) {
            return $formattedAmount;
        }

        return strtoupper($currencyCode).' '.$formattedAmount;
    }

    private function formatPrecisionDate(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        $year = (int) data_get($value, 'year', 0);
        $month = (int) data_get($value, 'month', 0);
        $day = (int) data_get($value, 'day', 0);

        if ($year < 1) {
            return null;
        }

        $months = [
            1 => ['short' => 'Jan', 'long' => 'January'],
            2 => ['short' => 'Feb', 'long' => 'February'],
            3 => ['short' => 'Mar', 'long' => 'March'],
            4 => ['short' => 'Apr', 'long' => 'April'],
            5 => ['short' => 'May', 'long' => 'May'],
            6 => ['short' => 'Jun', 'long' => 'June'],
            7 => ['short' => 'Jul', 'long' => 'July'],
            8 => ['short' => 'Aug', 'long' => 'August'],
            9 => ['short' => 'Sep', 'long' => 'September'],
            10 => ['short' => 'Oct', 'long' => 'October'],
            11 => ['short' => 'Nov', 'long' => 'November'],
            12 => ['short' => 'Dec', 'long' => 'December'],
        ];

        if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
            return $months[$month]['short'].' '.$day.', '.$year;
        }

        if ($month >= 1 && $month <= 12) {
            return $months[$month]['long'].' '.$year;
        }

        return (string) $year;
    }
}
