<?php

namespace Tests\Feature\Feature;

use App\Models\MovieCertificateAttribute;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CertificateAttributePageTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_certificate_attribute_page_renders_related_titles_and_filter_state(): void
    {
        $movieCertificateAttribute = MovieCertificateAttribute::query()
            ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
            ->with([
                'certificateAttribute:id,name',
                'movieCertificate:id,movie_id,certificate_rating_id,country_code,position',
                'movieCertificate.country:code,name',
                'movieCertificate.certificateRating:id,name',
                'movieCertificate.title' => fn ($titleQuery) => $titleQuery
                    ->select($this->remoteTitleColumns())
                    ->publishedCatalog(),
                'movieCertificate.movieCertificateAttributes' => fn ($movieCertificateAttributeQuery) => $movieCertificateAttributeQuery
                    ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
                    ->with([
                        'certificateAttribute:id,name',
                    ])
                    ->orderBy('position'),
            ])
            ->whereHas('certificateAttribute', fn ($attributeQuery) => $attributeQuery->whereNotNull('name'))
            ->whereHas('movieCertificate.title', fn ($titleQuery) => $titleQuery->publishedCatalog())
            ->whereHas('movieCertificate.certificateRating', fn ($ratingQuery) => $ratingQuery->whereNotNull('name'))
            ->whereHas('movieCertificate', fn ($movieCertificateQuery) => $movieCertificateQuery->whereNotNull('country_code'))
            ->orderBy('movie_certificate_id')
            ->orderBy('position')
            ->first();

        if (! $movieCertificateAttribute instanceof MovieCertificateAttribute
            || ! $movieCertificateAttribute->certificateAttribute
            || ! $movieCertificateAttribute->movieCertificate
            || ! $movieCertificateAttribute->movieCertificate->title) {
            $this->markTestSkipped('The remote catalog does not currently expose a renderable certificate attribute archive sample.');
        }

        $response = $this->get(route('public.certificate-attributes.show', $movieCertificateAttribute->certificateAttribute));

        $response
            ->assertOk()
            ->assertSeeHtml('data-slot="certificate-attribute-detail-hero"')
            ->assertSeeHtml('data-slot="certificate-attribute-detail-filters"')
            ->assertSeeHtml('data-slot="certificate-attribute-detail-records"')
            ->assertSee($movieCertificateAttribute->certificateAttribute->name)
            ->assertSee($movieCertificateAttribute->movieCertificate->title->name)
            ->assertSee('Updates live')
            ->assertDontSee('<form method="GET"', false)
            ->assertSee(route('public.titles.show', $movieCertificateAttribute->movieCertificate->title), false);

        if ($movieCertificateAttribute->movieCertificate->certificateRating !== null) {
            $response
                ->assertSee($movieCertificateAttribute->movieCertificate->certificateRating->resolvedLabel())
                ->assertSeeHtml('data-slot="certificate-rating-chip"')
                ->assertSee(route('public.certificate-ratings.show', $movieCertificateAttribute->movieCertificate->certificateRating), false);
        }

        $filteredResponse = $this->get(route('public.certificate-attributes.show', [
            'certificateAttribute' => $movieCertificateAttribute->certificateAttribute,
            'q' => $movieCertificateAttribute->movieCertificate->title->name,
            'type' => $movieCertificateAttribute->movieCertificate->title->title_type->value,
            'country' => $movieCertificateAttribute->movieCertificate->country_code,
        ]));

        $filteredResponse
            ->assertOk()
            ->assertSeeHtml('data-slot="certificate-attribute-detail-filters"')
            ->assertSee($movieCertificateAttribute->movieCertificate->title->name)
            ->assertSee($movieCertificateAttribute->certificateAttribute->name)
            ->assertSee('Updates live')
            ->assertDontSee('<form method="GET"', false);
    }
}
