<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\CatalogMediaAsset;
use App\Models\MovieCertificate;
use App\Models\MovieParentsGuideReview;
use App\Models\MovieParentsGuideSection;
use App\Models\MovieParentsGuideSeverityBreakdown;
use App\Models\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LoadTitleParentsGuideAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: CatalogMediaAsset|null,
     *     backdrop: CatalogMediaAsset|null,
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
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
            'certificateRecords:id,movie_id,certificate_rating_id,country_code,position',
            'certificateRecords.certificateRating:id,name',
            'certificateRecords.movieCertificateAttributes:movie_certificate_id,certificate_attribute_id,position',
            'certificateRecords.movieCertificateAttributes.certificateAttribute:id,name',
            'parentsGuideSections:id,movie_id,parents_guide_category_id,position',
            'parentsGuideSections.parentsGuideCategory:id,code',
            'parentsGuideSections.movieParentsGuideReviews:id,movie_parents_guide_section_id,text,is_spoiler,position',
            'parentsGuideSections.movieParentsGuideSeverityBreakdowns:movie_parents_guide_section_id,parents_guide_severity_level_id,vote_count,position',
            'parentsGuideSections.movieParentsGuideSeverityBreakdowns.parentsGuideSeverityLevel:id,name',
        ]);

        $poster = $title->preferredPoster();
        $backdrop = $title->preferredBackdrop();
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
        return $title->parentsGuideSections
            ->map(function (MovieParentsGuideSection $section): ?array {
                $reviews = $section->movieParentsGuideReviews
                    ->sortBy('position')
                    ->values();
                $nonSpoilerReviews = $reviews
                    ->reject(fn (MovieParentsGuideReview $review): bool => $review->is_spoiler)
                    ->pluck('text')
                    ->map(fn (mixed $text): ?string => $this->nullableString($text))
                    ->filter()
                    ->values();
                $text = $nonSpoilerReviews->implode(' ');

                if ($text === '') {
                    $text = $reviews
                        ->pluck('text')
                        ->map(fn (mixed $reviewText): ?string => $this->nullableString($reviewText))
                        ->filter()
                        ->implode(' ');
                }

                if ($text === '') {
                    return null;
                }

                $breakdowns = $section->movieParentsGuideSeverityBreakdowns
                    ->sortByDesc('vote_count')
                    ->values();
                $topBreakdown = $breakdowns->first();
                $severityLabel = $topBreakdown?->parentsGuideSeverityLevel?->name
                    ? Str::of($topBreakdown->parentsGuideSeverityLevel->name)->headline()->toString()
                    : 'Unrated';
                $totalVotes = $breakdowns->sum('vote_count');
                $consensus = ($topBreakdown instanceof MovieParentsGuideSeverityBreakdown && $totalVotes > 0)
                    ? (int) round(($topBreakdown->vote_count / $totalVotes) * 100)
                    : null;
                $voteSplitLabel = $breakdowns->isNotEmpty()
                    ? $breakdowns
                        ->take(3)
                        ->map(function (MovieParentsGuideSeverityBreakdown $breakdown): string {
                            $severityName = $breakdown->parentsGuideSeverityLevel?->name
                                ? Str::of($breakdown->parentsGuideSeverityLevel->name)->headline()->toString()
                                : 'Unrated';

                            return $severityName.' '.number_format((int) $breakdown->vote_count);
                        })
                        ->implode(' · ')
                    : 'Vote record not available';

                return [
                    'category' => Str::of($section->parentsGuideCategory?->code ?? 'Advisory')
                        ->replace(['_', '-'], ' ')
                        ->headline()
                        ->toString(),
                    'severityLabel' => $severityLabel,
                    'severityColor' => $this->severityColor($severityLabel),
                    'text' => Str::of($text)->squish()->toString(),
                    'reviewCount' => $reviews->count(),
                    'yesVotes' => null,
                    'noVotes' => null,
                    'totalVotes' => $totalVotes > 0 ? $totalVotes : null,
                    'consensus' => $consensus,
                    'voteSplitLabel' => $voteSplitLabel,
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
        return $title->resolvedMovieCertificates()
            ->map(function (MovieCertificate $certificate): ?array {
                $rating = $this->nullableString($certificate->certificateRating?->name);

                if ($rating === null) {
                    return null;
                }

                $attributes = $certificate->movieCertificateAttributes
                    ->map(fn ($attribute): ?string => $this->nullableString($attribute->certificateAttribute?->name))
                    ->filter()
                    ->take(3)
                    ->implode(', ');

                return [
                    'rating' => $rating,
                    'country' => $this->nullableString($certificate->resolvedCountryLabel()),
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
        return $title->parentsGuideSections
            ->flatMap(
                fn (MovieParentsGuideSection $section): Collection => $section->movieParentsGuideReviews
                    ->filter(fn (MovieParentsGuideReview $review): bool => $review->is_spoiler)
                    ->pluck('text'),
            )
            ->map(fn (mixed $spoiler): ?string => $this->nullableString($spoiler))
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

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
