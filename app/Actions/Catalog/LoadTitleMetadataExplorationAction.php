<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleRelationshipType;
use App\Models\MediaAsset;
use App\Models\Title;
use App\Models\TitleRelationship;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LoadTitleMetadataExplorationAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: MediaAsset|null,
     *     backdrop: MediaAsset|null,
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
            'genres:id,name,slug',
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
                            'statistic:id,title_id,average_rating,rating_count,review_count',
                        ]),
                ])
                ->orderByDesc('weight')
                ->orderBy('id'),
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
                            'statistic:id,title_id,average_rating,rating_count,review_count',
                        ]),
                ])
                ->orderByDesc('weight')
                ->orderBy('id'),
        ]);

        $poster = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Poster, MediaKind::Backdrop);
        $backdrop = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Backdrop, MediaKind::Poster);
        $keywordItems = $this->buildKeywordItems($title);
        $keywordGroups = $this->buildKeywordGroups($keywordItems);
        $connectionEntries = $this->buildConnectionEntries($title);
        $connectionGroups = $connectionEntries
            ->groupBy('groupLabel')
            ->map(function (Collection $entries, string $groupLabel): array {
                $leadEntry = $entries->first();

                return [
                    'label' => $groupLabel,
                    'copy' => $leadEntry['groupCopy'],
                    'count' => $entries->count(),
                    'items' => $entries
                        ->sortByDesc(fn (array $entry): int => $entry['weight'] ?? 0)
                        ->values()
                        ->map(function (array $entry): array {
                            unset($entry['groupLabel'], $entry['groupCopy'], $entry['groupPriority']);

                            return $entry;
                        }),
                    'priority' => $leadEntry['groupPriority'],
                ];
            })
            ->sortBy('priority')
            ->values()
            ->map(function (array $group): array {
                unset($group['priority']);

                return $group;
            });

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'keywordCount' => $keywordItems->count(),
            'connectionCount' => $connectionEntries->count(),
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
     *     groupLabel: string,
     *     groupCopy: string,
     *     groupPriority: int,
     *     title: Title,
     *     badgeLabel: string,
     *     weight: int|null,
     *     typeLabel: string,
     *     yearLabel: string|null,
     *     ratingLabel: string|null,
     *     note: string|null
     * }>
     */
    private function buildConnectionEntries(Title $title): Collection
    {
        $outgoing = $title->outgoingRelationships
            ->map(fn (TitleRelationship $relationship): ?array => $this->mapConnectionEntry($relationship, false))
            ->filter();

        $incoming = $title->incomingRelationships
            ->map(fn (TitleRelationship $relationship): ?array => $this->mapConnectionEntry($relationship, true))
            ->filter();

        return $outgoing
            ->merge($incoming)
            ->unique(fn (array $entry): string => $entry['groupLabel'].'-'.$entry['title']->id)
            ->values();
    }

    /**
     * @return array{
     *     groupLabel: string,
     *     groupCopy: string,
     *     groupPriority: int,
     *     title: Title,
     *     badgeLabel: string,
     *     weight: int|null,
     *     typeLabel: string,
     *     yearLabel: string|null,
     *     ratingLabel: string|null,
     *     note: string|null
     * }|null
     */
    private function mapConnectionEntry(TitleRelationship $relationship, bool $isIncoming): ?array
    {
        $relatedTitle = $isIncoming ? $relationship->fromTitle : $relationship->toTitle;

        if (! $relatedTitle instanceof Title) {
            return null;
        }

        $presentation = $this->connectionPresentation($relationship->relationship_type, $isIncoming);

        return [
            'groupLabel' => $presentation['groupLabel'],
            'groupCopy' => $presentation['groupCopy'],
            'groupPriority' => $presentation['groupPriority'],
            'title' => $relatedTitle,
            'badgeLabel' => $presentation['badgeLabel'],
            'weight' => $relationship->weight,
            'typeLabel' => Str::headline($relatedTitle->title_type->value),
            'yearLabel' => $relatedTitle->release_year ? (string) $relatedTitle->release_year : null,
            'ratingLabel' => $relatedTitle->statistic?->average_rating
                ? number_format((float) $relatedTitle->statistic->average_rating, 1)
                : null,
            'note' => $this->relationshipNote($relationship, $relatedTitle),
        ];
    }

    /**
     * @return array{groupLabel: string, groupCopy: string, groupPriority: int, badgeLabel: string}
     */
    private function connectionPresentation(TitleRelationshipType $relationshipType, bool $isIncoming): array
    {
        return match ($relationshipType) {
            TitleRelationshipType::Sequel => [
                'groupLabel' => 'Series Order',
                'groupCopy' => 'Chronological continuations and return journeys inside the same story lane.',
                'groupPriority' => 1,
                'badgeLabel' => $isIncoming ? 'Followed By' : 'Follows',
            ],
            TitleRelationshipType::Prequel => [
                'groupLabel' => 'Series Order',
                'groupCopy' => 'Chronological continuations and return journeys inside the same story lane.',
                'groupPriority' => 1,
                'badgeLabel' => $isIncoming ? 'Preceded By' : 'Precedes',
            ],
            TitleRelationshipType::SharedUniverse => [
                'groupLabel' => 'Shared Universe',
                'groupCopy' => 'Titles that share canon, setting, or larger narrative architecture.',
                'groupPriority' => 2,
                'badgeLabel' => 'Shared Universe',
            ],
            TitleRelationshipType::Similar => [
                'groupLabel' => 'Similar Mood',
                'groupCopy' => 'Editorially adjacent titles for tone, premise, or viewer appetite.',
                'groupPriority' => 3,
                'badgeLabel' => 'Similar To',
            ],
            TitleRelationshipType::SpinOff => [
                'groupLabel' => 'Expanded Storyworld',
                'groupCopy' => 'Branches, side lanes, and character-led extensions of the core world.',
                'groupPriority' => 4,
                'badgeLabel' => $isIncoming ? 'Spun Off' : 'Spin-off Of',
            ],
            TitleRelationshipType::Adaptation => [
                'groupLabel' => 'Adaptations',
                'groupCopy' => 'Connected works shaped through source material or re-interpretation.',
                'groupPriority' => 5,
                'badgeLabel' => $isIncoming ? 'Adapted Into' : 'Adaptation Of',
            ],
            TitleRelationshipType::Remake => [
                'groupLabel' => 'Reinterpretations',
                'groupCopy' => 'Later reworkings, remakes, and refreshed takes on the same core idea.',
                'groupPriority' => 6,
                'badgeLabel' => $isIncoming ? 'Remade As' : 'Remake Of',
            ],
            TitleRelationshipType::Franchise => [
                'groupLabel' => 'Franchise Links',
                'groupCopy' => 'Higher-level franchise relationships beyond direct sequel ordering.',
                'groupPriority' => 7,
                'badgeLabel' => 'Franchise Link',
            ],
        };
    }

    private function relationshipNote(TitleRelationship $relationship, Title $relatedTitle): ?string
    {
        if (filled($relationship->notes)) {
            return Str::of((string) $relationship->notes)->squish()->limit(130)->toString();
        }

        if (filled($relatedTitle->plot_outline)) {
            return Str::of((string) $relatedTitle->plot_outline)->squish()->limit(130)->toString();
        }

        return null;
    }

    /**
     * @return Collection<int, string>
     */
    private function tokenizeKeywords(?string $value): Collection
    {
        return collect(preg_split('/[,|]/', (string) $value) ?: [])
            ->map(fn (string $item): string => Str::of($item)->trim()->toString())
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
}
