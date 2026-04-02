<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LoadTitleParentsGuideAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: MediaAsset|null,
     *     backdrop: MediaAsset|null,
     *     advisoryCount: int,
     *     spoilerCount: int,
     *     documentedVoteCount: int,
     *     advisorySections: Collection<int, array{
     *         category: string,
     *         severityLabel: string,
     *         severityColor: string,
     *         text: string,
     *         reviewCount: int,
     *         yesVotes: int|null,
     *         noVotes: int|null,
     *         totalVotes: int|null,
     *         consensus: int|null,
     *         voteSplitLabel: string
     *     }>,
     *     severitySummary: Collection<int, array{
     *         label: string,
     *         count: int,
     *         color: string
     *     }>,
     *     certificateItems: Collection<int, array{
     *         rating: string,
     *         country: string|null,
     *         attributes: string|null
     *     }>,
     *     spoilerItems: Collection<int, string>,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            'mediaAssets' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'position',
                    'is_primary',
                ])
                ->ordered(),
        ]);

        $poster = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Poster, MediaKind::Backdrop);
        $backdrop = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Backdrop, MediaKind::Poster);
        $advisorySections = $this->buildAdvisorySections($title);
        $severitySummary = $this->buildSeveritySummary($advisorySections);
        $certificateItems = $this->buildCertificateItems($title);
        $spoilerItems = $this->buildSpoilerItems($title);
        $documentedVoteCount = $advisorySections
            ->sum(fn (array $advisorySection): int => $advisorySection['totalVotes'] ?? 0);
        $openGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'advisoryCount' => $advisorySections->count(),
            'spoilerCount' => $spoilerItems->count(),
            'documentedVoteCount' => $documentedVoteCount,
            'advisorySections' => $advisorySections,
            'severitySummary' => $severitySummary,
            'certificateItems' => $certificateItems,
            'spoilerItems' => $spoilerItems,
            'seo' => new PageSeoData(
                title: $title->name.' Parents Guide',
                description: 'Read the parents guide for '.$title->name.' with structured content concerns, severity levels, and advisory notes.',
                canonical: route('public.titles.parents-guide', $title),
                openGraphType: $openGraphType,
                openGraphImage: $backdrop?->url ?? $poster?->url,
                openGraphImageAlt: $backdrop?->alt_text ?: $poster?->alt_text ?: $title->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                    ['label' => 'Parents Guide'],
                ],
                paginationPageName: null,
            ),
        ];
    }

    /**
     * @return Collection<int, array{
     *     category: string,
     *     severityLabel: string,
     *     severityColor: string,
     *     text: string,
     *     reviewCount: int,
     *     yesVotes: int|null,
     *     noVotes: int|null,
     *     totalVotes: int|null,
     *     consensus: int|null,
     *     voteSplitLabel: string
     * }>
     */
    private function buildAdvisorySections(Title $title): Collection
    {
        return collect(data_get($title->imdbPayloadSection('parentsGuide'), 'advisories', []))
            ->map(function (mixed $advisory): ?array {
                if (! is_array($advisory)) {
                    return null;
                }

                $reviewTexts = collect(data_get($advisory, 'reviews', []))
                    ->map(function (mixed $review): ?string {
                        if (! is_array($review)) {
                            return null;
                        }

                        return $this->nullableString(data_get($review, 'text'));
                    })
                    ->filter()
                    ->values();

                $text = $this->nullableString(data_get($advisory, 'text'))
                    ?? ($reviewTexts->isNotEmpty() ? $reviewTexts->implode(' ') : null);

                if ($text === null) {
                    return null;
                }

                $severity = $this->nullableString(data_get($advisory, 'severity'));
                $severityLabel = $severity !== null
                    ? Str::of($severity)->replace(['_', '-'], ' ')->headline()->toString()
                    : 'Unrated';
                $yesVotes = $this->firstNullableInt([
                    data_get($advisory, 'votes.yes'),
                    data_get($advisory, 'voteBreakdown.yes'),
                    data_get($advisory, 'yesVotes'),
                    data_get($advisory, 'upVotes'),
                ]);
                $noVotes = $this->firstNullableInt([
                    data_get($advisory, 'votes.no'),
                    data_get($advisory, 'voteBreakdown.no'),
                    data_get($advisory, 'noVotes'),
                    data_get($advisory, 'downVotes'),
                ]);
                $totalVotes = ($yesVotes !== null || $noVotes !== null)
                    ? max(0, ($yesVotes ?? 0) + ($noVotes ?? 0))
                    : $this->firstNullableInt([
                        data_get($advisory, 'votes.total'),
                        data_get($advisory, 'voteBreakdown.total'),
                        data_get($advisory, 'voteCount'),
                    ]);
                $consensus = ($yesVotes !== null && $noVotes !== null && $totalVotes > 0)
                    ? (int) round((max($yesVotes, $noVotes) / $totalVotes) * 100)
                    : null;

                return [
                    'category' => Str::of($this->nullableString(data_get($advisory, 'category')) ?? 'Advisory')
                        ->replace(['_', '-'], ' ')
                        ->headline()
                        ->toString(),
                    'severityLabel' => $severityLabel,
                    'severityColor' => $this->severityColor($severityLabel),
                    'text' => Str::of($text)->squish()->toString(),
                    'reviewCount' => $reviewTexts->count(),
                    'yesVotes' => $yesVotes,
                    'noVotes' => $noVotes,
                    'totalVotes' => $totalVotes,
                    'consensus' => $consensus,
                    'voteSplitLabel' => match (true) {
                        $yesVotes !== null && $noVotes !== null => number_format($yesVotes).' / '.number_format($noVotes).' split',
                        $totalVotes !== null => number_format($totalVotes).' recorded votes',
                        default => 'Vote record not available',
                    },
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, array{
     *     category: string,
     *     severityLabel: string,
     *     severityColor: string,
     *     text: string,
     *     reviewCount: int,
     *     yesVotes: int|null,
     *     noVotes: int|null,
     *     totalVotes: int|null,
     *     consensus: int|null,
     *     voteSplitLabel: string
     * }>  $advisorySections
     * @return Collection<int, array{
     *     label: string,
     *     count: int,
     *     color: string
     * }>
     */
    private function buildSeveritySummary(Collection $advisorySections): Collection
    {
        $severityPriority = [
            'Severe' => 1,
            'Moderate' => 2,
            'Mild' => 3,
            'Unrated' => 4,
        ];

        return $advisorySections
            ->countBy('severityLabel')
            ->map(function (int $count, string $label) use ($severityPriority): array {
                return [
                    'label' => $label,
                    'count' => $count,
                    'color' => $this->severityColor($label),
                    'priority' => $severityPriority[$label] ?? 99,
                ];
            })
            ->sortBy('priority')
            ->values()
            ->map(function (array $summary): array {
                unset($summary['priority']);

                return $summary;
            });
    }

    /**
     * @return Collection<int, array{
     *     rating: string,
     *     country: string|null,
     *     attributes: string|null
     * }>
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
                    ->map(function (mixed $attribute): ?string {
                        if (! is_string($attribute)) {
                            return null;
                        }

                        return Str::of($attribute)->replace(['_', '-'], ' ')->headline()->toString();
                    })
                    ->filter()
                    ->take(3)
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
     * @return Collection<int, string>
     */
    private function buildSpoilerItems(Title $title): Collection
    {
        return collect(data_get($title->imdbPayloadSection('parentsGuide'), 'spoilers', []))
            ->map(fn (mixed $spoiler): ?string => is_string($spoiler) ? trim($spoiler) : null)
            ->filter()
            ->take(5)
            ->values();
    }

    private function severityColor(string $severityLabel): string
    {
        return match (Str::of($severityLabel)->lower()->toString()) {
            'severe' => 'red',
            'moderate' => 'amber',
            'mild' => 'slate',
            default => 'neutral',
        };
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function firstNullableInt(array $values): ?int
    {
        foreach ($values as $value) {
            $normalizedValue = $this->nullableInt($value);

            if ($normalizedValue !== null) {
                return $normalizedValue;
            }
        }

        return null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if (! is_scalar($value) || ! is_numeric((string) $value)) {
            return null;
        }

        return (int) $value;
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
