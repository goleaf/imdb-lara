<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\AkaAttribute;
use App\Models\MovieAka;
use App\Models\MovieAkaAttribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LoadAkaAttributeDetailsAction
{
    private const PAGE_SIZE = 12;

    /**
     * @return array{
     *     akaAttribute: AkaAttribute,
     *     filters: array{q: string, type: string, country: string, language: string},
     *     hasActiveFilters: bool,
     *     typeOptions: Collection<int, array{value: string, label: string}>,
     *     countryOptions: Collection<int, array{value: string, label: string}>,
     *     languageOptions: Collection<int, array{value: string, label: string}>,
     *     summaryItems: Collection<int, array{label: string, value: string}>,
     *     archiveRecords: LengthAwarePaginator<int, array{
     *         key: string,
     *         titleHref: string,
     *         titleLabel: string,
     *         titleMeta: string|null,
     *         posterUrl: string|null,
     *         posterAlt: string|null,
     *         akaText: string,
     *         countryCode: string|null,
     *         countryLabel: string|null,
     *         languageCode: string|null,
     *         languageLabel: string|null,
     *         attributeLinks: Collection<int, array{id: int, href: string, label: string}>
     *     }>,
     *     seo: PageSeoData
     * }
     */
    public function handle(AkaAttribute $akaAttribute, array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $archiveQuery = $this->baseArchiveQuery($akaAttribute, $filters);
        $countryOptions = $this->countryOptions($akaAttribute, $filters);
        $languageOptions = $this->languageOptions($akaAttribute, $filters);

        $archiveRecords = (clone $archiveQuery)
            ->selectArchiveColumns()
            ->withArchiveRelations()
            ->archiveOrdered()
            ->paginate(self::PAGE_SIZE, pageName: 'aka_records')
            ->withQueryString()
            ->through(fn (MovieAka $movieAka): array => $this->mapArchiveRecord($movieAka, $akaAttribute));

        $recordCount = (clone $archiveQuery)->count();
        $titleCount = (clone $archiveQuery)->distinct('movie_id')->count('movie_id');
        $countryCount = (clone $archiveQuery)
            ->whereNotNull('country_code')
            ->distinct('country_code')
            ->count('country_code');
        $languageCount = (clone $archiveQuery)
            ->whereNotNull('language_code')
            ->distinct('language_code')
            ->count('language_code');

        $summaryItems = collect([
            ['label' => 'Titles', 'value' => number_format((int) $titleCount)],
            ['label' => 'Countries', 'value' => number_format((int) $countryCount)],
            ['label' => 'Languages', 'value' => number_format((int) $languageCount)],
            ['label' => 'AKA records', 'value' => number_format((int) $recordCount)],
        ])->values();

        $pageTitle = $akaAttribute->resolvedLabel().' AKA attribute';
        $description = 'Browse published alternate-title records, languages, countries, and other movies or shows that use the '.$akaAttribute->resolvedLabel().' AKA attribute.';
        $openGraphAsset = data_get($archiveRecords->getCollection()->first(), 'posterUrl');

        return [
            'akaAttribute' => $akaAttribute,
            'filters' => $filters,
            'hasActiveFilters' => $this->hasActiveFilters($filters),
            'typeOptions' => $this->typeOptions(),
            'countryOptions' => $countryOptions,
            'languageOptions' => $languageOptions,
            'summaryItems' => $summaryItems,
            'archiveRecords' => $archiveRecords,
            'seo' => new PageSeoData(
                title: $pageTitle,
                description: $description,
                canonical: route('public.aka-attributes.show', $akaAttribute),
                openGraphType: 'article',
                openGraphImage: $openGraphAsset,
                openGraphImageAlt: $akaAttribute->resolvedLabel(),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'AKA attribute archive', 'href' => route('public.aka-attributes.show', $akaAttribute)],
                    ['label' => $akaAttribute->resolvedLabel()],
                ],
            ),
        ];
    }

    private function baseArchiveQuery(AkaAttribute $akaAttribute, array $filters): Builder
    {
        $query = MovieAka::query()->forAkaAttribute($akaAttribute);

        return $this->applyArchiveFilters($query, $filters);
    }

    private function applyArchiveFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                $filters['country'] !== '',
                fn (Builder $movieAkaQuery): Builder => $movieAkaQuery->where('country_code', $filters['country']),
            )
            ->when(
                $filters['language'] !== '',
                fn (Builder $movieAkaQuery): Builder => $movieAkaQuery->where('language_code', $filters['language']),
            )
            ->whereHas('title', fn (Builder $titleQuery): Builder => $this->applyTitleFilters($titleQuery->publishedCatalog(), $filters));
    }

    private function applyTitleFilters(Builder $query, array $filters): Builder
    {
        $titleType = TitleType::tryFrom($filters['type']);

        return $query
            ->when(
                $filters['q'] !== '',
                fn (Builder $titleQuery): Builder => $titleQuery->matchingSearch($filters['q']),
            )
            ->when(
                $titleType instanceof TitleType,
                fn (Builder $titleQuery): Builder => $titleQuery->forType($titleType),
            );
    }

    /**
     * @return array{q: string, type: string, country: string, language: string}
     */
    /**
     * @param  array{q?: string, type?: string, country?: string, language?: string}  $filters
     * @return array{q: string, type: string, country: string, language: string}
     */
    private function normalizeFilters(array $filters): array
    {
        $type = TitleType::tryFrom((string) ($filters['type'] ?? ''));

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'type' => $type?->value ?? '',
            'country' => str((string) ($filters['country'] ?? ''))->trim()->upper()->toString(),
            'language' => str((string) ($filters['language'] ?? ''))->trim()->lower()->toString(),
        ];
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['type'] !== ''
            || $filters['country'] !== ''
            || $filters['language'] !== '';
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function typeOptions(): Collection
    {
        return collect(TitleType::cases())
            ->map(fn (TitleType $titleType): array => [
                'value' => $titleType->value,
                'label' => $titleType->label(),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function countryOptions(AkaAttribute $akaAttribute, array $filters): Collection
    {
        $filtersWithoutCountry = [...$filters, 'country' => ''];

        return (clone $this->baseArchiveQuery($akaAttribute, $filtersWithoutCountry))
            ->select(['country_code'])
            ->with(['country:code,name'])
            ->whereNotNull('country_code')
            ->orderBy('country_code')
            ->get()
            ->map(function (MovieAka $movieAka): ?array {
                if (! filled($movieAka->country_code)) {
                    return null;
                }

                return [
                    'value' => strtoupper((string) $movieAka->country_code),
                    'label' => $movieAka->resolvedCountryLabel() ?? strtoupper((string) $movieAka->country_code),
                ];
            })
            ->filter()
            ->unique('value')
            ->sortBy('label')
            ->values();
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function languageOptions(AkaAttribute $akaAttribute, array $filters): Collection
    {
        $filtersWithoutLanguage = [...$filters, 'language' => ''];

        return (clone $this->baseArchiveQuery($akaAttribute, $filtersWithoutLanguage))
            ->select(['language_code'])
            ->with(['language:code,name'])
            ->whereNotNull('language_code')
            ->orderBy('language_code')
            ->get()
            ->map(function (MovieAka $movieAka): ?array {
                if (! filled($movieAka->language_code)) {
                    return null;
                }

                return [
                    'value' => strtolower((string) $movieAka->language_code),
                    'label' => $movieAka->resolvedLanguageLabel() ?? strtoupper((string) $movieAka->language_code),
                ];
            })
            ->filter()
            ->unique('value')
            ->sortBy('label')
            ->values();
    }

    /**
     * @return array{
     *     key: string,
     *     titleHref: string,
     *     titleLabel: string,
     *     titleMeta: string|null,
     *     posterUrl: string|null,
     *     posterAlt: string|null,
     *     akaText: string,
     *     countryCode: string|null,
     *     countryLabel: string|null,
     *     languageCode: string|null,
     *     languageLabel: string|null,
     *     attributeLinks: Collection<int, array{id: int, href: string, label: string}>
     * }
     */
    private function mapArchiveRecord(MovieAka $movieAka, AkaAttribute $akaAttribute): array
    {
        $title = $movieAka->title;
        $poster = $title?->preferredPoster();

        $attributeLinks = $movieAka->movieAkaAttributes
            ->map(fn (MovieAkaAttribute $movieAkaAttribute): ?AkaAttribute => $movieAkaAttribute->akaAttribute)
            ->filter(fn (mixed $linkedAkaAttribute): bool => $linkedAkaAttribute instanceof AkaAttribute && filled($linkedAkaAttribute->name))
            ->unique('id')
            ->map(fn (AkaAttribute $linkedAkaAttribute): array => [
                'id' => (int) $linkedAkaAttribute->getKey(),
                'href' => route('public.aka-attributes.show', $linkedAkaAttribute),
                'label' => $linkedAkaAttribute->resolvedLabel(),
            ])
            ->values();

        return [
            'key' => 'aka-record-'.$movieAka->getKey(),
            'titleHref' => $title ? route('public.titles.show', $title) : '#',
            'titleLabel' => $title?->name ?? 'Archived title',
            'titleMeta' => collect([
                $title?->typeLabel(),
                $title?->release_year ? (string) $title->release_year : null,
            ])->filter()->implode(' · ') ?: null,
            'posterUrl' => $poster?->url,
            'posterAlt' => $poster?->alt_text ?: $title?->name,
            'akaText' => (string) ($movieAka->text ?: 'Untitled AKA'),
            'countryCode' => filled($movieAka->country_code) ? strtoupper((string) $movieAka->country_code) : null,
            'countryLabel' => $movieAka->resolvedCountryLabel(),
            'languageCode' => filled($movieAka->language_code) ? strtolower((string) $movieAka->language_code) : null,
            'languageLabel' => $movieAka->resolvedLanguageLabel(),
            'attributeLinks' => $attributeLinks,
        ];
    }
}
