<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\MediaAsset;
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
     *     still: MediaAsset|null,
     *     seasonNavigation: Collection<int, Season>,
     *     seasonEpisodes: Collection<int, Episode>,
     *     previousEpisode: Episode|null,
     *     nextEpisode: Episode|null,
     *     guestCast: Collection<int, Credit>,
     *     keyCrew: Collection<int, array{role: string, credits: Collection<int, Credit>}>,
     *     parentGuideItems: Collection<int, array{category: string, severity: string|null, severityColor: string, text: string}>,
     *     parentGuideSpoilers: Collection<int, string>,
     *     certificateItems: Collection<int, array{rating: string, country: string|null, attributes: string|null}>,
     *     triviaItems: Collection<int, string>,
     *     goofItems: Collection<int, string>,
     *     detailItems: Collection<int, array{label: string, value: string}>,
     *     ratingCount: int,
     *     previousEpisodeTitle: Title|null,
     *     nextEpisodeTitle: Title|null,
     *     episodeDirectory: Collection<int, array{href: string, label: string}>
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
                ->ordered(),
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
                ->ordered(),
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
        $parentGuideItems = $this->buildParentGuideItems($episode);
        $parentGuideSpoilers = $this->buildParentGuideSpoilers($episode);
        $certificateItems = $this->buildCertificateItems($episode);
        $triviaItems = $this->buildTriviaItems($episode);
        $goofItems = $this->buildGoofItems($episode);
        $still = MediaAsset::preferredFrom($episode->mediaAssets, MediaKind::Still, MediaKind::Backdrop)
            ?? MediaAsset::preferredFrom($series->mediaAssets, MediaKind::Backdrop, MediaKind::Poster)
            ?? MediaAsset::preferredFrom($episode->mediaAssets);
        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'TV Shows', 'href' => route('public.series.index')],
            ['label' => $series->name, 'href' => route('public.titles.show', $series)],
            ['label' => $season->name, 'href' => route('public.seasons.show', ['series' => $series, 'season' => $season])],
            ['label' => $episode->name],
        ];

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
            'parentGuideItems' => $parentGuideItems,
            'parentGuideSpoilers' => $parentGuideSpoilers,
            'certificateItems' => $certificateItems,
            'triviaItems' => $triviaItems,
            'goofItems' => $goofItems,
            'detailItems' => $detailItems,
            'ratingCount' => (int) ($episode->statistic?->rating_count ?? 0),
            'episodeDirectory' => collect([
                ['href' => '#episode-plot', 'label' => 'Plot'],
                ['href' => '#episode-guest-cast', 'label' => 'Guest cast'],
                ['href' => '#episode-crew', 'label' => 'Crew'],
                ['href' => '#episode-parents-guide', 'label' => 'Parents guide'],
                ['href' => '#episode-trivia', 'label' => 'Trivia'],
                ['href' => '#episode-goofs', 'label' => 'Goofs'],
                ['href' => '#episode-season-lineup', 'label' => 'Season lineup'],
                ['href' => '#episode-reviews', 'label' => 'Reviews'],
            ]),
            'seo' => new PageSeoData(
                title: $episode->meta_title ?: $episode->name,
                description: $episode->meta_description ?: ($episode->plot_outline ?: 'Browse credits, reviews, and metadata for '.$episode->name.'.'),
                canonical: route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episode]),
                openGraphType: 'video.tv_show',
                openGraphImage: $still?->url,
                openGraphImageAlt: $still?->alt_text ?: $episode->name,
                breadcrumbs: $breadcrumbs,
            ),
        ];
    }

    /**
     * @return Collection<int, array{category: string, severity: string|null, severityColor: string, text: string}>
     */
    private function buildParentGuideItems(Title $episode): Collection
    {
        return collect(data_get($episode->imdbPayloadSection('parentsGuide'), 'advisories', []))
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
            ->take(3)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function buildParentGuideSpoilers(Title $episode): Collection
    {
        return collect(data_get($episode->imdbPayloadSection('parentsGuide'), 'spoilers', []))
            ->map(fn (mixed $spoiler): ?string => is_string($spoiler) ? trim($spoiler) : null)
            ->filter()
            ->take(2)
            ->values();
    }

    /**
     * @return Collection<int, array{rating: string, country: string|null, attributes: string|null}>
     */
    private function buildCertificateItems(Title $episode): Collection
    {
        return collect(data_get($episode->imdbPayloadSection('certificates'), 'certificates', []))
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
            ->take(3)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function buildTriviaItems(Title $episode): Collection
    {
        return collect(data_get($episode->imdbPayloadSection('trivia'), 'triviaEntries', []))
            ->map(function (mixed $trivia): ?string {
                if (is_array($trivia)) {
                    return $this->nullableString(data_get($trivia, 'text'))
                        ?? $this->nullableString(data_get($trivia, 'plainText'));
                }

                return is_string($trivia) ? trim($trivia) : null;
            })
            ->filter()
            ->take(3)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function buildGoofItems(Title $episode): Collection
    {
        return collect(
            data_get($episode->imdbPayloadSection('goofs'), 'goofEntries')
                ?? data_get($episode->imdbPayloadSection('goofs'), 'items')
                ?? []
        )
            ->map(function (mixed $goof): ?string {
                if (is_array($goof)) {
                    return $this->nullableString(data_get($goof, 'text'))
                        ?? $this->nullableString(data_get($goof, 'plainText'))
                        ?? $this->nullableString(data_get($goof, 'spoiler.text'));
                }

                return is_string($goof) ? trim($goof) : null;
            })
            ->filter()
            ->take(3)
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
}
