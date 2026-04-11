<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\Company;
use App\Models\CompanyCreditAttribute;
use App\Models\CompanyCreditCategory;
use App\Models\MovieCompanyCredit;
use App\Models\MovieCompanyCreditAttribute;
use App\Models\MovieCompanyCreditCountry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LoadCompanyCreditAttributeDetailsAction
{
    private const PAGE_SIZE = 12;

    /**
     * @return array{
     *     companyCreditAttribute: CompanyCreditAttribute,
     *     filters: array{q: string, type: string, country: string, company: string, category: string},
     *     hasActiveFilters: bool,
     *     typeOptions: Collection<int, array{value: string, label: string}>,
     *     companyOptions: Collection<int, array{value: string, label: string}>,
     *     countryOptions: Collection<int, array{value: string, label: string}>,
     *     categoryOptions: Collection<int, array{value: string, label: string}>,
     *     summaryItems: Collection<int, array{label: string, value: string}>,
     *     archiveRecords: LengthAwarePaginator<int, array{
     *         key: string,
     *         titleHref: string,
     *         titleLabel: string,
     *         titleMeta: string|null,
     *         posterUrl: string|null,
     *         posterAlt: string|null,
     *         companyHref: string|null,
     *         companyLabel: string|null,
     *         categoryHref: string|null,
     *         categoryLabel: string|null,
     *         activeYearsLabel: string|null,
     *         countryBadges: Collection<int, array{code: string, label: string}>,
     *         attributeLinks: Collection<int, array{id: int, href: string, label: string}>
     *     }>,
     *     seo: PageSeoData
     * }
     */
    public function handle(CompanyCreditAttribute $companyCreditAttribute, array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $archiveQuery = $this->baseArchiveQuery($companyCreditAttribute, $filters);

        $archiveRecords = (clone $archiveQuery)
            ->select(['id', 'movie_id', 'company_imdb_id', 'company_credit_category_id', 'start_year', 'end_year', 'position'])
            ->with([
                'company:imdb_id,name',
                'companyCreditCategory:id,name',
                'title' => fn ($titleQuery) => $titleQuery
                    ->selectCatalogCardColumns()
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
                'movieCompanyCreditAttributes' => fn ($movieCompanyCreditAttributeQuery) => $movieCompanyCreditAttributeQuery
                    ->select(['movie_company_credit_id', 'company_credit_attribute_id', 'position'])
                    ->with([
                        'companyCreditAttribute:id,name',
                    ])
                    ->orderBy('position'),
                'movieCompanyCreditCountries' => fn ($movieCompanyCreditCountryQuery) => $movieCompanyCreditCountryQuery
                    ->select(['movie_company_credit_id', 'country_code', 'position'])
                    ->with([
                        'country:code,name',
                    ])
                    ->orderBy('position'),
            ])
            ->orderBy('start_year')
            ->orderBy('end_year')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(self::PAGE_SIZE, pageName: 'attribute_records')
            ->withQueryString()
            ->through(fn (MovieCompanyCredit $movieCompanyCredit): array => $this->mapArchiveRecord($companyCreditAttribute, $movieCompanyCredit));

        $recordCount = (clone $archiveQuery)->count();
        $titleCount = (clone $archiveQuery)->distinct('movie_id')->count('movie_id');
        $companyCount = (clone $archiveQuery)->distinct('company_imdb_id')->count('company_imdb_id');
        $categoryCount = (clone $archiveQuery)
            ->whereNotNull('company_credit_category_id')
            ->distinct('company_credit_category_id')
            ->count('company_credit_category_id');
        $countryCount = MovieCompanyCreditCountry::query()
            ->whereHas('movieCompanyCredit', fn (Builder $movieCompanyCreditQuery): Builder => $this->applyArchiveFilters(
                $movieCompanyCreditQuery
                    ->whereHas('movieCompanyCreditAttributes', fn (Builder $attributeQuery): Builder => $attributeQuery->where('company_credit_attribute_id', $companyCreditAttribute->getKey())),
                $filters,
            ))
            ->distinct('country_code')
            ->count('country_code');

        $summaryItems = collect([
            ['label' => 'Titles', 'value' => number_format((int) $titleCount)],
            ['label' => 'Companies', 'value' => number_format((int) $companyCount)],
            ['label' => 'Categories', 'value' => number_format((int) $categoryCount)],
            ['label' => 'Countries', 'value' => number_format((int) $countryCount)],
            ['label' => 'Credit records', 'value' => number_format((int) $recordCount)],
        ])->values();

        $description = 'Browse published company credit records, linked companies, categories, and titles that use the '.($companyCreditAttribute->name ?: 'selected').' attribute.';
        $openGraphAsset = data_get($archiveRecords->getCollection()->first(), 'posterUrl');

        return [
            'companyCreditAttribute' => $companyCreditAttribute,
            'filters' => $filters,
            'hasActiveFilters' => $this->hasActiveFilters($filters),
            'typeOptions' => $this->typeOptions(),
            'companyOptions' => $this->companyOptions($companyCreditAttribute, $filters),
            'countryOptions' => $this->countryOptions($companyCreditAttribute, $filters),
            'categoryOptions' => $this->categoryOptions($companyCreditAttribute, $filters),
            'summaryItems' => $summaryItems,
            'archiveRecords' => $archiveRecords,
            'seo' => new PageSeoData(
                title: ($companyCreditAttribute->name ?: 'Company credit attribute').' archive',
                description: $description,
                canonical: route('public.company-credit-attributes.show', $companyCreditAttribute),
                openGraphType: 'article',
                openGraphImage: $openGraphAsset,
                openGraphImageAlt: $companyCreditAttribute->name ?: 'Company credit attribute',
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => $companyCreditAttribute->name ?: 'Company credit attribute'],
                ],
            ),
        ];
    }

    private function baseArchiveQuery(CompanyCreditAttribute $companyCreditAttribute, array $filters): Builder
    {
        return $this->applyArchiveFilters(
            MovieCompanyCredit::query()
                ->whereHas(
                    'movieCompanyCreditAttributes',
                    fn (Builder $movieCompanyCreditAttributeQuery): Builder => $movieCompanyCreditAttributeQuery
                        ->where('company_credit_attribute_id', $companyCreditAttribute->getKey()),
                ),
            $filters,
        );
    }

    private function applyArchiveFilters(Builder $query, array $filters): Builder
    {
        $categoryId = is_numeric($filters['category']) ? (int) $filters['category'] : null;

        return $query
            ->when(
                $filters['company'] !== '',
                fn (Builder $movieCompanyCreditQuery): Builder => $movieCompanyCreditQuery->where('company_imdb_id', $filters['company']),
            )
            ->when(
                $categoryId !== null,
                fn (Builder $movieCompanyCreditQuery): Builder => $movieCompanyCreditQuery->where('company_credit_category_id', $categoryId),
            )
            ->when(
                $filters['country'] !== '',
                fn (Builder $movieCompanyCreditQuery): Builder => $movieCompanyCreditQuery->whereHas(
                    'movieCompanyCreditCountries',
                    fn (Builder $movieCompanyCreditCountryQuery): Builder => $movieCompanyCreditCountryQuery->where('country_code', $filters['country']),
                ),
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
     * @return array{q: string, type: string, country: string, company: string, category: string}
     */
    /**
     * @param  array{q?: string, type?: string, country?: string, company?: string, category?: string}  $filters
     * @return array{q: string, type: string, country: string, company: string, category: string}
     */
    private function normalizeFilters(array $filters): array
    {
        $type = TitleType::tryFrom((string) ($filters['type'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'type' => $type?->value ?? '',
            'country' => str((string) ($filters['country'] ?? ''))->trim()->upper()->toString(),
            'company' => trim((string) ($filters['company'] ?? '')),
            'category' => is_numeric($category) ? (string) (int) $category : '',
        ];
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['type'] !== ''
            || $filters['country'] !== ''
            || $filters['company'] !== ''
            || $filters['category'] !== '';
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
    private function companyOptions(CompanyCreditAttribute $companyCreditAttribute, array $filters): Collection
    {
        $filtersWithoutCompany = [...$filters, 'company' => ''];

        return (clone $this->baseArchiveQuery($companyCreditAttribute, $filtersWithoutCompany))
            ->select(['company_imdb_id'])
            ->with(['company:imdb_id,name'])
            ->whereNotNull('company_imdb_id')
            ->orderBy('company_imdb_id')
            ->get()
            ->map(function (MovieCompanyCredit $movieCompanyCredit): ?array {
                if (! $movieCompanyCredit->company instanceof Company || ! filled($movieCompanyCredit->company->name)) {
                    return null;
                }

                return [
                    'value' => (string) $movieCompanyCredit->company->imdb_id,
                    'label' => (string) $movieCompanyCredit->company->name,
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
    private function countryOptions(CompanyCreditAttribute $companyCreditAttribute, array $filters): Collection
    {
        $filtersWithoutCountry = [...$filters, 'country' => ''];

        return MovieCompanyCreditCountry::query()
            ->select(['movie_company_credit_id', 'country_code'])
            ->with(['country:code,name'])
            ->whereHas('movieCompanyCredit', fn (Builder $movieCompanyCreditQuery): Builder => $this->applyArchiveFilters(
                $movieCompanyCreditQuery
                    ->whereHas('movieCompanyCreditAttributes', fn (Builder $attributeQuery): Builder => $attributeQuery->where('company_credit_attribute_id', $companyCreditAttribute->getKey())),
                $filtersWithoutCountry,
            ))
            ->whereNotNull('country_code')
            ->orderBy('country_code')
            ->get()
            ->map(function (MovieCompanyCreditCountry $movieCompanyCreditCountry): ?array {
                if (! filled($movieCompanyCreditCountry->country_code)) {
                    return null;
                }

                return [
                    'value' => strtoupper((string) $movieCompanyCreditCountry->country_code),
                    'label' => $movieCompanyCreditCountry->resolvedCountryLabel() ?? strtoupper((string) $movieCompanyCreditCountry->country_code),
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
    private function categoryOptions(CompanyCreditAttribute $companyCreditAttribute, array $filters): Collection
    {
        $filtersWithoutCategory = [...$filters, 'category' => ''];

        return (clone $this->baseArchiveQuery($companyCreditAttribute, $filtersWithoutCategory))
            ->select(['company_credit_category_id'])
            ->with(['companyCreditCategory:id,name'])
            ->whereNotNull('company_credit_category_id')
            ->orderBy('company_credit_category_id')
            ->get()
            ->map(function (MovieCompanyCredit $movieCompanyCredit): ?array {
                if (! $movieCompanyCredit->companyCreditCategory instanceof CompanyCreditCategory || ! filled($movieCompanyCredit->companyCreditCategory->name)) {
                    return null;
                }

                return [
                    'value' => (string) $movieCompanyCredit->companyCreditCategory->getKey(),
                    'label' => (string) $movieCompanyCredit->companyCreditCategory->name,
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
     *     companyHref: string|null,
     *     companyLabel: string|null,
     *     categoryHref: string|null,
     *     categoryLabel: string|null,
     *     activeYearsLabel: string|null,
     *     countryBadges: Collection<int, array{code: string, label: string}>,
     *     attributeLinks: Collection<int, array{id: int, href: string, label: string}>
     * }
     */
    private function mapArchiveRecord(CompanyCreditAttribute $companyCreditAttribute, MovieCompanyCredit $movieCompanyCredit): array
    {
        $title = $movieCompanyCredit->title;
        $poster = $title?->preferredPoster();
        $company = $movieCompanyCredit->company;
        $category = $movieCompanyCredit->companyCreditCategory;

        $countryBadges = $movieCompanyCredit->movieCompanyCreditCountries
            ->map(function (MovieCompanyCreditCountry $movieCompanyCreditCountry): ?array {
                if (! filled($movieCompanyCreditCountry->country_code)) {
                    return null;
                }

                return [
                    'code' => strtoupper((string) $movieCompanyCreditCountry->country_code),
                    'label' => $movieCompanyCreditCountry->resolvedCountryLabel() ?? strtoupper((string) $movieCompanyCreditCountry->country_code),
                ];
            })
            ->filter()
            ->unique('code')
            ->values();

        $attributeLinks = $movieCompanyCredit->movieCompanyCreditAttributes
            ->map(fn (MovieCompanyCreditAttribute $movieCompanyCreditAttribute): ?CompanyCreditAttribute => $movieCompanyCreditAttribute->companyCreditAttribute)
            ->filter(fn (mixed $linkedAttribute): bool => $linkedAttribute instanceof CompanyCreditAttribute && filled($linkedAttribute->name))
            ->unique('id')
            ->map(fn (CompanyCreditAttribute $linkedAttribute): array => [
                'id' => (int) $linkedAttribute->getKey(),
                'href' => route('public.company-credit-attributes.show', $linkedAttribute),
                'label' => (string) $linkedAttribute->name,
            ])
            ->values();

        return [
            'key' => 'company-credit-attribute-'.$movieCompanyCredit->getKey(),
            'titleHref' => $title ? route('public.titles.show', $title) : '#',
            'titleLabel' => $title?->name ?? 'Archived title',
            'titleMeta' => collect([
                $title?->typeLabel(),
                $title?->release_year ? (string) $title->release_year : null,
                $title?->runtimeMinutesLabel(),
            ])->filter()->implode(' · ') ?: null,
            'posterUrl' => $poster?->url,
            'posterAlt' => $poster?->alt_text ?: $title?->name,
            'companyHref' => $company ? route('public.companies.show', $company) : null,
            'companyLabel' => $company?->name,
            'categoryHref' => $company && $category ? route('public.companies.show', ['company' => $company, 'category' => (string) $category->getKey()]) : null,
            'categoryLabel' => $category?->name,
            'activeYearsLabel' => $movieCompanyCredit->activeYearsLabel(),
            'countryBadges' => $countryBadges,
            'attributeLinks' => $attributeLinks,
        ];
    }
}
