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

class LoadCompanyDetailsAction
{
    private const PAGE_SIZE = 12;

    /**
     * @return array{
     *     company: Company,
     *     filters: array{q: string, type: string, country: string, category: string},
     *     hasActiveFilters: bool,
     *     typeOptions: Collection<int, array{value: string, label: string}>,
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
     *         categoryLabel: string|null,
     *         activeYearsLabel: string|null,
     *         countryBadges: Collection<int, array{code: string, label: string}>,
     *         attributeBadges: Collection<int, array{id: int, label: string}>
     *     }>,
     *     seo: PageSeoData
     * }
     */
    public function handle(Company $company): array
    {
        $filters = $this->filtersFromRequest();
        $archiveQuery = $this->baseArchiveQuery($company, $filters);

        $archiveRecords = (clone $archiveQuery)
            ->select(['id', 'movie_id', 'company_imdb_id', 'company_credit_category_id', 'start_year', 'end_year', 'position'])
            ->with([
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
            ->paginate(self::PAGE_SIZE, pageName: 'company_records')
            ->withQueryString()
            ->through(fn (MovieCompanyCredit $movieCompanyCredit): array => $this->mapArchiveRecord($movieCompanyCredit));

        $recordCount = (clone $archiveQuery)->count();
        $titleCount = (clone $archiveQuery)->distinct('movie_id')->count('movie_id');
        $categoryCount = (clone $archiveQuery)
            ->whereNotNull('company_credit_category_id')
            ->distinct('company_credit_category_id')
            ->count('company_credit_category_id');
        $countryCount = MovieCompanyCreditCountry::query()
            ->whereHas('movieCompanyCredit', fn (Builder $movieCompanyCreditQuery): Builder => $this->applyArchiveFilters(
                $movieCompanyCreditQuery->where('company_imdb_id', $company->imdb_id),
                $filters,
            ))
            ->distinct('country_code')
            ->count('country_code');
        $attributeCount = MovieCompanyCreditAttribute::query()
            ->whereHas('movieCompanyCredit', fn (Builder $movieCompanyCreditQuery): Builder => $this->applyArchiveFilters(
                $movieCompanyCreditQuery->where('company_imdb_id', $company->imdb_id),
                $filters,
            ))
            ->distinct('company_credit_attribute_id')
            ->count('company_credit_attribute_id');

        $summaryItems = collect([
            ['label' => 'Titles', 'value' => number_format((int) $titleCount)],
            ['label' => 'Categories', 'value' => number_format((int) $categoryCount)],
            ['label' => 'Countries', 'value' => number_format((int) $countryCount)],
            ['label' => 'Attributes', 'value' => number_format((int) $attributeCount)],
            ['label' => 'Credit records', 'value' => number_format((int) $recordCount)],
        ])->values();

        $description = 'Browse other published movies and series connected to '.($company->name ?: 'this company').' through imported company credit records, categories, countries, and attributes.';
        $openGraphAsset = data_get($archiveRecords->getCollection()->first(), 'posterUrl');

        return [
            'company' => $company,
            'filters' => $filters,
            'hasActiveFilters' => $this->hasActiveFilters($filters),
            'typeOptions' => $this->typeOptions(),
            'countryOptions' => $this->countryOptions($company, $filters),
            'categoryOptions' => $this->categoryOptions($company, $filters),
            'summaryItems' => $summaryItems,
            'archiveRecords' => $archiveRecords,
            'seo' => new PageSeoData(
                title: ($company->name ?: 'Company').' archive',
                description: $description,
                canonical: route('public.companies.show', $company),
                openGraphType: 'article',
                openGraphImage: $openGraphAsset,
                openGraphImageAlt: $company->name ?: 'Company archive',
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => $company->name ?: 'Company'],
                ],
            ),
        ];
    }

    private function baseArchiveQuery(Company $company, array $filters): Builder
    {
        return $this->applyArchiveFilters(
            MovieCompanyCredit::query()->where('company_imdb_id', $company->imdb_id),
            $filters,
        );
    }

    private function applyArchiveFilters(Builder $query, array $filters): Builder
    {
        $categoryId = is_numeric($filters['category']) ? (int) $filters['category'] : null;

        return $query
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
     * @return array{q: string, type: string, country: string, category: string}
     */
    private function filtersFromRequest(): array
    {
        $type = TitleType::tryFrom((string) request()->query('type', ''));
        $category = trim((string) request()->query('category', ''));

        return [
            'q' => trim((string) request()->query('q', '')),
            'type' => $type?->value ?? '',
            'country' => str((string) request()->query('country', ''))->trim()->upper()->toString(),
            'category' => is_numeric($category) ? (string) (int) $category : '',
        ];
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['type'] !== ''
            || $filters['country'] !== ''
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
    private function countryOptions(Company $company, array $filters): Collection
    {
        $filtersWithoutCountry = [...$filters, 'country' => ''];

        return MovieCompanyCreditCountry::query()
            ->select(['movie_company_credit_id', 'country_code'])
            ->with(['country:code,name'])
            ->whereHas('movieCompanyCredit', fn (Builder $movieCompanyCreditQuery): Builder => $this->applyArchiveFilters(
                $movieCompanyCreditQuery->where('company_imdb_id', $company->imdb_id),
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
    private function categoryOptions(Company $company, array $filters): Collection
    {
        $filtersWithoutCategory = [...$filters, 'category' => ''];

        return (clone $this->baseArchiveQuery($company, $filtersWithoutCategory))
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
     *     categoryLabel: string|null,
     *     activeYearsLabel: string|null,
     *     countryBadges: Collection<int, array{code: string, label: string}>,
     *     attributeBadges: Collection<int, array{id: int, label: string}>
     * }
     */
    private function mapArchiveRecord(MovieCompanyCredit $movieCompanyCredit): array
    {
        $title = $movieCompanyCredit->title;
        $poster = $title?->preferredPoster();

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

        $attributeBadges = $movieCompanyCredit->movieCompanyCreditAttributes
            ->map(fn (MovieCompanyCreditAttribute $movieCompanyCreditAttribute): ?CompanyCreditAttribute => $movieCompanyCreditAttribute->companyCreditAttribute)
            ->filter(fn (mixed $companyCreditAttribute): bool => $companyCreditAttribute instanceof CompanyCreditAttribute && filled($companyCreditAttribute->name))
            ->unique('id')
            ->map(fn (CompanyCreditAttribute $companyCreditAttribute): array => [
                'id' => (int) $companyCreditAttribute->getKey(),
                'label' => (string) $companyCreditAttribute->name,
            ])
            ->values();

        return [
            'key' => 'company-credit-'.$movieCompanyCredit->getKey(),
            'titleHref' => $title ? route('public.titles.show', $title) : '#',
            'titleLabel' => $title?->name ?? 'Archived title',
            'titleMeta' => collect([
                $title?->typeLabel(),
                $title?->release_year ? (string) $title->release_year : null,
                $title?->runtimeMinutesLabel(),
            ])->filter()->implode(' · ') ?: null,
            'posterUrl' => $poster?->url,
            'posterAlt' => $poster?->alt_text ?: $title?->name,
            'categoryLabel' => $movieCompanyCredit->companyCreditCategory?->name,
            'activeYearsLabel' => $movieCompanyCredit->activeYearsLabel(),
            'countryBadges' => $countryBadges,
            'attributeBadges' => $attributeBadges,
        ];
    }
}
