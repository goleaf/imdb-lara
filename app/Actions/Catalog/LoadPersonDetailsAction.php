<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\AwardNomination;
use App\Models\Credit;
use App\Models\NameBasicAlternativeName;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadPersonDetailsAction
{
    /**
     * @return array{
     *     person: Person,
     *     headshot: mixed,
     *     photoGallery: Collection<int, mixed>,
     *     alternateNames: Collection<int, string>,
     *     alternativeNameRows: Collection<int, mixed>,
     *     professionLabels: Collection<int, string>,
     *     biographyIntro: string|null,
     *     detailItems: Collection<int, array{label: string, value: string}>,
     *     knownForTitles: Collection<int, Title>,
     *     frequentCollaborators: Collection<int, array{person: Person, sharedTitles: Collection<int, Title>, sharedCount: int}>,
     *     relatedTitles: Collection<int, Title>,
     *     careerProfileItems: Collection<int, array{label: string, value: string, copy: string}>,
     *     creditDepartmentHighlights: Collection<int, array{label: string, count: int}>,
     *     titleFormatHighlights: Collection<int, array{label: string, count: int}>,
     *     awardHighlights: Collection<int, AwardNomination>,
     *     awardWins: int,
     *     awardNominationsCount: int,
     *     publishedCreditCount: int,
     *     heroProfileItems: Collection<int, array{label: string, value: string}>,
     *     biographyParagraphs: Collection<int, string>,
     *     seo: PageSeoData
     * }
     */
    public function handle(Person $person): array
    {
        $person->loadMissing(Person::detailRelations());
        $this->loadPreviewCredits($person);
        $this->loadAwardHighlights($person);

        $headshot = $person->preferredHeadshot();
        $photoGallery = $person->groupedMediaAssetsByKind()
            ->flatten(1)
            ->reject(fn ($asset) => $headshot && $asset->url === $headshot->url)
            ->take(8)
            ->values();
        $alternativeNameRows = Person::usesCatalogOnlySchema()
            ? NameBasicAlternativeName::query()
                ->select(['name_basic_id', 'alternative_name', 'position'])
                ->where('name_basic_id', $person->getKey())
                ->orderBy('position')
                ->orderBy('alternative_name')
                ->get()
            : collect();
        $alternateNames = collect($person->imdb_alternative_names)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->concat($alternativeNameRows->pluck('alternative_name'))
            ->concat($person->resolvedAlternateNames())
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values();
        $professionLabels = collect($person->professionLabels(4))->values();
        $biographyIntro = $person->summaryText();
        $creditedTitles = $person->credits
            ->pluck('title')
            ->filter(fn ($title): bool => $title instanceof Title)
            ->unique('id')
            ->values();
        $knownForTitles = $person->knownForTitles
            ->filter(fn ($title): bool => $title instanceof Title)
            ->unique('id')
            ->values();

        if ($knownForTitles->isEmpty()) {
            $knownForTitles = $creditedTitles
                ->sortByDesc(fn (Title $title): string => sprintf(
                    '%01d-%05.2f-%09d-%05d',
                    $person->credits->where('title_id', $title->id)->contains(fn (Credit $credit) => $credit->is_principal) ? 1 : 0,
                    (float) ($title->statistic?->average_rating ?? 0),
                    (int) ($title->statistic?->rating_count ?? 0),
                    (int) ($title->release_year ?? 0),
                ))
                ->take(6)
                ->values();
        }
        $knownForTitleIds = $knownForTitles
            ->pluck('id')
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
        $relatedTitles = $creditedTitles
            ->reject(fn (Title $title): bool => in_array((int) $title->id, $knownForTitleIds, true))
            ->sortByDesc(fn (Title $title): string => sprintf(
                '%05.2f-%09d-%05d',
                (float) ($title->statistic?->average_rating ?? 0),
                (int) ($title->statistic?->rating_count ?? 0),
                (int) ($title->release_year ?? 0),
            ))
            ->take(6)
            ->values();
        $frequentCollaborators = collect();
        $collaborationTitleIds = $knownForTitles
            ->concat($relatedTitles)
            ->pluck('id')
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        if ($collaborationTitleIds !== []) {
            $frequentCollaborators = Credit::query()
                ->select(Credit::projectedColumns())
                ->with(Credit::projectedRelations())
                ->whereIn(Credit::qualifiedColumn('title_id'), $collaborationTitleIds)
                ->where(Credit::qualifiedColumn('person_id'), '!=', $person->getKey())
                ->ordered()
                ->withPersonPreview()
                ->with([
                    'title' => fn ($titleQuery) => $titleQuery
                        ->selectCatalogCardColumns()
                        ->publishedCatalog()
                        ->withCatalogCardRelations(),
                ])
                ->get()
                ->filter(fn (Credit $credit): bool => $credit->person instanceof Person && $credit->title instanceof Title)
                ->groupBy(fn (Credit $credit): string => (string) $credit->person_id)
                ->map(function (Collection $credits): array {
                    /** @var Person $collaborator */
                    $collaborator = $credits->first()->person;
                    /** @var Collection<int, Title> $sharedTitles */
                    $sharedTitles = $credits
                        ->pluck('title')
                        ->filter(fn ($title): bool => $title instanceof Title)
                        ->unique('id')
                        ->take(3)
                        ->values();

                    return [
                        'person' => $collaborator,
                        'sharedTitles' => $sharedTitles,
                        'sharedCount' => $sharedTitles->count(),
                    ];
                })
                ->sortByDesc(fn (array $item): int => $item['sharedCount'])
                ->take(6)
                ->values();
        }
        $detailItems = collect([
            ['label' => 'Known for', 'value' => $person->known_for_department],
            ['label' => 'Birth place', 'value' => $person->birth_place],
            ['label' => 'Nationality', 'value' => $person->nationality],
            ['label' => 'Published credits', 'value' => number_format($creditedTitles->count())],
            ['label' => 'People meter', 'value' => $person->popularity_rank ? '#'.number_format($person->popularity_rank) : null],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();
        $heroProfileItems = $detailItems->take(4)->values();
        $creditDepartmentHighlights = $person->credits
            ->groupBy(fn (Credit $credit): string => $credit->department)
            ->map(fn (Collection $credits, string $label): array => [
                'label' => $label,
                'count' => $credits->count(),
            ])
            ->sortByDesc('count')
            ->take(4)
            ->values();
        $titleFormatHighlights = $creditedTitles
            ->groupBy(fn (Title $title): string => $title->typeLabel())
            ->map(fn (Collection $titles, string $label): array => [
                'label' => $label,
                'count' => $titles->count(),
            ])
            ->sortByDesc('count')
            ->take(4)
            ->values();
        $releaseYears = $creditedTitles
            ->pluck('release_year')
            ->filter(fn (mixed $year): bool => is_numeric($year))
            ->map(fn (mixed $year): int => (int) $year)
            ->sort()
            ->values();
        $releaseSpanLabel = match ($releaseYears->count()) {
            0 => null,
            1 => (string) $releaseYears->first(),
            default => $releaseYears->first().' - '.$releaseYears->last(),
        };
        $topDepartment = $creditDepartmentHighlights->first();
        $topFormat = $titleFormatHighlights->first();
        $careerProfileItems = collect([
            [
                'label' => 'Primary lane',
                'value' => is_array($topDepartment) ? $topDepartment['label'] : 'Catalog profile',
                'copy' => is_array($topDepartment)
                    ? number_format((int) $topDepartment['count']).' imported credits in the strongest department.'
                    : 'The imported catalog has not grouped credits into departments yet.',
            ],
            [
                'label' => 'Release span',
                'value' => $releaseSpanLabel ?? 'Unscheduled',
                'copy' => $releaseYears->isNotEmpty()
                    ? 'Visible credits range from the earliest to the latest published title year.'
                    : 'No release years are attached to the imported credit sample yet.',
            ],
            [
                'label' => 'Top format',
                'value' => is_array($topFormat) ? $topFormat['label'] : 'Mixed',
                'copy' => is_array($topFormat)
                    ? number_format((int) $topFormat['count']).' imported titles in the strongest format cluster.'
                    : 'The imported titles are not grouped into a dominant format yet.',
            ],
        ])->values();
        $biographyParagraphs = collect(preg_split('/\R{2,}/', (string) ($person->biography ?? '')) ?: [])
            ->map(fn (string $paragraph): string => trim($paragraph))
            ->filter()
            ->values();
        $awardHighlights = $person->awardNominations->values();
        $awardWins = $awardHighlights->where('is_winner', true)->count();
        $awardNominationsCount = $awardHighlights->count();
        $publishedCreditCount = $creditedTitles->count();

        return [
            'person' => $person,
            'headshot' => $headshot,
            'photoGallery' => $photoGallery,
            'alternateNames' => $alternateNames,
            'alternativeNameRows' => $alternativeNameRows,
            'professionLabels' => $professionLabels,
            'biographyIntro' => $biographyIntro,
            'detailItems' => $detailItems,
            'knownForTitles' => $knownForTitles,
            'frequentCollaborators' => $frequentCollaborators,
            'relatedTitles' => $relatedTitles,
            'careerProfileItems' => $careerProfileItems,
            'creditDepartmentHighlights' => $creditDepartmentHighlights,
            'titleFormatHighlights' => $titleFormatHighlights,
            'awardHighlights' => $awardHighlights,
            'awardWins' => $awardWins,
            'awardNominationsCount' => $awardNominationsCount,
            'publishedCreditCount' => $publishedCreditCount,
            'heroProfileItems' => $heroProfileItems,
            'biographyParagraphs' => $biographyParagraphs,
            'seo' => new PageSeoData(
                title: $person->meta_title ?: $person->name,
                description: $person->meta_description ?: ($biographyIntro ?: 'Browse biography, credits, and career highlights for '.$person->name.'.'),
                canonical: route('public.people.show', $person),
                openGraphType: 'profile',
                openGraphImage: $headshot?->url,
                openGraphImageAlt: $headshot?->alt_text ?: $person->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'People', 'href' => route('public.people.index')],
                    ['label' => $person->name],
                ],
            ),
        ];
    }

    private function loadPreviewCredits(Person $person): void
    {
        $person->setRelation('credits', $person->credits()
            ->select(Credit::projectedColumns())
            ->with(Credit::projectedRelations())
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->selectCatalogCardColumns()
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->ordered()
            ->limit(40)
            ->get());
    }

    private function loadAwardHighlights(Person $person): void
    {
        $person->setRelation('awardNominations', collect());
    }
}
