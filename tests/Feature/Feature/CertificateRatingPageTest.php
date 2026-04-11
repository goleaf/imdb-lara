<?php

namespace Tests\Feature\Feature;

use App\Models\MovieCertificate;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CertificateRatingPageTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_certificate_rating_page_renders_related_titles_and_filter_state(): void
    {
        $movieCertificate = MovieCertificate::query()
            ->select(['id', 'movie_id', 'certificate_rating_id', 'country_code', 'position'])
            ->with([
                'country:code,name',
                'certificateRating:id,name',
                'title' => fn ($titleQuery) => $titleQuery
                    ->select($this->remoteTitleColumns())
                    ->publishedCatalog(),
                'movieCertificateAttributes' => fn ($movieCertificateAttributeQuery) => $movieCertificateAttributeQuery
                    ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
                    ->with([
                        'certificateAttribute:id,name',
                    ])
                    ->orderBy('position'),
            ])
            ->whereHas('title', fn ($titleQuery) => $titleQuery->publishedCatalog())
            ->whereHas('certificateRating', fn ($ratingQuery) => $ratingQuery->whereNotNull('name'))
            ->whereHas('movieCertificateAttributes.certificateAttribute', fn ($attributeQuery) => $attributeQuery->whereNotNull('name'))
            ->whereNotNull('country_code')
            ->orderBy('id')
            ->first();

        if (! $movieCertificate instanceof MovieCertificate || ! $movieCertificate->title || ! $movieCertificate->certificateRating) {
            $this->markTestSkipped('The remote catalog does not currently expose a renderable certificate rating archive sample.');
        }

        $response = $this->get(route('public.certificate-ratings.show', $movieCertificate->certificateRating));

        $response
            ->assertOk()
            ->assertSeeHtml('data-slot="certificate-rating-detail-hero"')
            ->assertSeeHtml('data-slot="certificate-rating-detail-records"')
            ->assertSee($movieCertificate->certificateRating->resolvedLabel())
            ->assertSee($movieCertificate->certificateRating->shortDescription())
            ->assertSeeHtml('data-slot="certificate-rating-chip"')
            ->assertSee($movieCertificate->title->name)
            ->assertSee(route('public.titles.show', $movieCertificate->title), false);

        $attribute = $movieCertificate->movieCertificateAttributes
            ->map(fn ($movieCertificateAttribute) => $movieCertificateAttribute->certificateAttribute)
            ->first();

        if ($attribute !== null) {
            $response
                ->assertSee($attribute->name)
                ->assertSee(route('public.certificate-attributes.show', $attribute), false);
        }

        $filteredResponse = $this->get(route('public.certificate-ratings.show', [
            'certificateRating' => $movieCertificate->certificateRating,
            'q' => $movieCertificate->title->name,
            'type' => $movieCertificate->title->title_type->value,
            'country' => $movieCertificate->country_code,
        ]));

        $filteredResponse
            ->assertOk()
            ->assertSeeHtml('data-slot="certificate-rating-detail-filters"')
            ->assertSee($movieCertificate->title->name)
            ->assertSee($movieCertificate->certificateRating->resolvedLabel());
    }
}
