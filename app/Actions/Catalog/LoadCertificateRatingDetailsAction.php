<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\CertificateAttribute;
use App\Models\CertificateRating;
use App\Models\MovieCertificate;
use App\Models\MovieCertificateAttribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LoadCertificateRatingDetailsAction
{
    private const PAGE_SIZE = 12;

    /**
     * @return array{
     *     certificateRating: CertificateRating,
     *     filters: array{q: string, type: string, country: string},
     *     hasActiveFilters: bool,
     *     typeOptions: Collection<int, array{value: string, label: string}>,
     *     countryOptions: Collection<int, array{value: string, label: string}>,
     *     summaryItems: Collection<int, array{label: string, value: string}>,
     *     archiveRecords: LengthAwarePaginator<int, array{
     *         key: string,
     *         titleHref: string,
     *         titleLabel: string,
     *         titleMeta: string|null,
     *         posterUrl: string|null,
     *         posterAlt: string|null,
     *         countryCode: string|null,
     *         countryLabel: string|null,
     *         ratingId: int|null,
     *         ratingHref: string|null,
     *         ratingLabel: string|null,
     *         attributeLinks: Collection<int, array{id: int, href: string, label: string}>
     *     }>,
     *     seo: PageSeoData
     * }
     */
    public function handle(CertificateRating $certificateRating): array
    {
        $filters = $this->filtersFromRequest();
        $archiveQuery = $this->baseArchiveQuery($certificateRating, $filters);
        $countryOptions = $this->countryOptions($certificateRating, $filters);

        $archiveRecords = (clone $archiveQuery)
            ->select(['id', 'movie_id', 'certificate_rating_id', 'country_code', 'position'])
            ->with([
                'country:code,name',
                'certificateRating:id,name',
                'title' => fn ($titleQuery) => $titleQuery
                    ->selectCatalogCardColumns()
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
                'movieCertificateAttributes' => fn ($movieCertificateAttributeQuery) => $movieCertificateAttributeQuery
                    ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
                    ->with([
                        'certificateAttribute:id,name',
                    ])
                    ->orderBy('position'),
            ])
            ->orderBy('country_code')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(self::PAGE_SIZE, pageName: 'rating_records')
            ->withQueryString()
            ->through(fn (MovieCertificate $movieCertificate): array => $this->mapArchiveRecord($movieCertificate));

        $recordCount = (clone $archiveQuery)->count();
        $titleCount = (clone $archiveQuery)->distinct('movie_id')->count('movie_id');
        $countryCount = (clone $archiveQuery)
            ->whereNotNull('country_code')
            ->distinct('country_code')
            ->count('country_code');
        $attributeCount = MovieCertificateAttribute::query()
            ->whereHas('movieCertificate', fn (Builder $movieCertificateQuery): Builder => $this->applyArchiveFilters(
                $movieCertificateQuery->where('certificate_rating_id', $certificateRating->getKey()),
                $filters,
            ))
            ->distinct('certificate_attribute_id')
            ->count('certificate_attribute_id');

        $summaryItems = collect([
            ['label' => 'Titles', 'value' => number_format((int) $titleCount)],
            ['label' => 'Countries', 'value' => number_format((int) $countryCount)],
            ['label' => 'Attributes', 'value' => number_format((int) $attributeCount)],
            ['label' => 'Certificate records', 'value' => number_format((int) $recordCount)],
        ])->values();

        $ratingName = filled($certificateRating->name) ? (string) $certificateRating->name : 'selected';
        $description = 'Browse related certificate records, linked attributes, and other published titles that share the '.$ratingName.' rating.';
        $openGraphAsset = data_get($archiveRecords->getCollection()->first(), 'posterUrl');
        $pageTitle = filled($certificateRating->name) ? $certificateRating->name.' certificate rating' : 'Certificate rating';

        return [
            'certificateRating' => $certificateRating,
            'filters' => $filters,
            'hasActiveFilters' => $this->hasActiveFilters($filters),
            'typeOptions' => $this->typeOptions(),
            'countryOptions' => $countryOptions,
            'summaryItems' => $summaryItems,
            'archiveRecords' => $archiveRecords,
            'seo' => new PageSeoData(
                title: $pageTitle,
                description: $description,
                canonical: route('public.certificate-ratings.show', $certificateRating),
                openGraphType: 'article',
                openGraphImage: $openGraphAsset,
                openGraphImageAlt: $certificateRating->name ?: 'Certificate rating',
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Certificate rating archive', 'href' => route('public.certificate-ratings.show', $certificateRating)],
                    ['label' => $certificateRating->name ?: 'Certificate rating'],
                ],
            ),
        ];
    }

    private function baseArchiveQuery(CertificateRating $certificateRating, array $filters): Builder
    {
        $query = MovieCertificate::query()
            ->where('certificate_rating_id', $certificateRating->getKey());

        return $this->applyArchiveFilters($query, $filters);
    }

    private function applyArchiveFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                $filters['country'] !== '',
                fn (Builder $movieCertificateQuery): Builder => $movieCertificateQuery->where('country_code', $filters['country']),
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
     * @return array{q: string, type: string, country: string}
     */
    private function filtersFromRequest(): array
    {
        $type = TitleType::tryFrom((string) request()->query('type', ''));

        return [
            'q' => trim((string) request()->query('q', '')),
            'type' => $type?->value ?? '',
            'country' => str((string) request()->query('country', ''))->trim()->upper()->toString(),
        ];
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== '' || $filters['type'] !== '' || $filters['country'] !== '';
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
    private function countryOptions(CertificateRating $certificateRating, array $filters): Collection
    {
        $filtersWithoutCountry = [...$filters, 'country' => ''];

        return (clone $this->baseArchiveQuery($certificateRating, $filtersWithoutCountry))
            ->select(['country_code'])
            ->with(['country:code,name'])
            ->whereNotNull('country_code')
            ->orderBy('country_code')
            ->get()
            ->map(function (MovieCertificate $movieCertificate): ?array {
                if (! filled($movieCertificate->country_code)) {
                    return null;
                }

                return [
                    'value' => strtoupper((string) $movieCertificate->country_code),
                    'label' => $movieCertificate->resolvedCountryLabel() ?? strtoupper((string) $movieCertificate->country_code),
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
     *     countryCode: string|null,
     *     countryLabel: string|null,
     *     ratingId: int|null,
     *     ratingHref: string|null,
     *     ratingLabel: string|null,
     *     attributeLinks: Collection<int, array{id: int, href: string, label: string}>
     * }
     */
    private function mapArchiveRecord(MovieCertificate $movieCertificate): array
    {
        $title = $movieCertificate->title;
        $poster = $title?->preferredPoster();

        $attributeLinks = $movieCertificate->movieCertificateAttributes
            ->map(fn (MovieCertificateAttribute $movieCertificateAttribute): ?CertificateAttribute => $movieCertificateAttribute->certificateAttribute)
            ->filter(fn (mixed $certificateAttribute): bool => $certificateAttribute instanceof CertificateAttribute && filled($certificateAttribute->name))
            ->unique('id')
            ->map(fn (CertificateAttribute $certificateAttribute): array => [
                'id' => (int) $certificateAttribute->getKey(),
                'href' => route('public.certificate-attributes.show', $certificateAttribute),
                'label' => (string) $certificateAttribute->name,
            ])
            ->values();

        return [
            'key' => 'certificate-record-'.$movieCertificate->getKey(),
            'titleHref' => $title ? route('public.titles.show', $title) : '#',
            'titleLabel' => $title?->name ?? 'Archived title',
            'titleMeta' => collect([
                $title?->typeLabel(),
                $title?->release_year ? (string) $title->release_year : null,
            ])->filter()->implode(' · ') ?: null,
            'posterUrl' => $poster?->url,
            'posterAlt' => $poster?->alt_text ?: $title?->name,
            'countryCode' => filled($movieCertificate->country_code) ? strtoupper((string) $movieCertificate->country_code) : null,
            'countryLabel' => $movieCertificate->resolvedCountryLabel(),
            'ratingId' => $movieCertificate->certificateRating?->getKey(),
            'ratingHref' => $movieCertificate->certificateRating ? route('public.certificate-ratings.show', $movieCertificate->certificateRating) : null,
            'ratingLabel' => $movieCertificate->certificateRating?->name,
            'attributeLinks' => $attributeLinks,
        ];
    }
}
