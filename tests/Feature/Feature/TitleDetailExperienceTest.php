<?php

namespace Tests\Feature\Feature;

use App\Models\MovieAkaAttribute;
use App\Models\MovieCertificateAttribute;
use App\Models\MovieCompanyCredit;
use App\Models\MovieCompanyCreditAttribute;
use App\Models\Title;
use App\Models\TitleAkaType;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleDetailExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_page_renders_the_current_catalog_detail_surface(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitleWithMedia();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-hero"')
            ->assertSee($title->name)
            ->assertSee('Overview')
            ->assertSee('Gallery')
            ->assertSee('Archive Views')
            ->assertSee('Media Gallery')
            ->assertSee('Keywords & Connections')
            ->assertSee('Trivia & Goofs');
    }

    public function test_title_page_exposes_the_catalog_credit_and_related_title_surface(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitleWithCredits();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('Featured cast')
            ->assertSee('Key crew')
            ->assertSee('Full Cast & Crew');
    }

    public function test_title_page_stays_catalog_only_without_write_side_actions(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertDontSee('Write a review')
            ->assertDontSee('Your rating')
            ->assertDontSee('Watchlist')
            ->assertDontSee('Edit title');
    }

    public function test_title_page_surfaces_the_imported_discovery_profile_when_interest_tags_exist(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitleWithInterests();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('Discovery profile')
            ->assertSee('Imported interest tags')
            ->assertSeeHtml('data-slot="title-discovery-profile"')
            ->assertSee('Open the full keywords and connections view');
    }

    public function test_title_page_surfaces_raw_aka_attribute_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieAkaAttribute = MovieAkaAttribute::query()
            ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
            ->with([
                'akaAttribute:id,name',
                'movieAka:id,movie_id,text,position',
            ])
            ->whereHas('akaAttribute', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_aka_id')
            ->orderBy('position')
            ->first();

        if (! $movieAkaAttribute instanceof MovieAkaAttribute || ! $movieAkaAttribute->movieAka || ! $movieAkaAttribute->akaAttribute) {
            $this->markTestSkipped('The remote catalog does not currently expose movie aka attribute rows with linked attributes.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieAkaAttribute->movieAka->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie aka attribute row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-aka-attributes"')
            ->assertSee('id')
            ->assertSee('name')
            ->assertSee((string) $movieAkaAttribute->akaAttribute->id)
            ->assertSee($movieAkaAttribute->akaAttribute->name);
    }

    public function test_title_page_surfaces_raw_aka_type_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $titleAkaType = TitleAkaType::query()
            ->select(['title_aka_id', 'aka_type_id', 'position'])
            ->with([
                'akaType:id,name',
                'titleAka:id,titleid,ordering,title',
            ])
            ->whereHas('akaType', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('title_aka_id')
            ->orderBy('position')
            ->first();

        if (! $titleAkaType instanceof TitleAkaType || ! $titleAkaType->titleAka || ! $titleAkaType->akaType) {
            $this->markTestSkipped('The remote catalog does not currently expose title aka type rows with linked types.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->where('tconst', $titleAkaType->titleAka->titleid)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected title aka type row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-aka-types"')
            ->assertSee('id')
            ->assertSee('name')
            ->assertSee((string) $titleAkaType->akaType->id)
            ->assertSee($titleAkaType->akaType->name);
    }

    public function test_title_page_surfaces_raw_award_category_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('awardNominations', fn ($query) => $query->whereHas('awardCategory'))
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with award nominations.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-award-categories"')
            ->assertSee('id')
            ->assertSee('name');

        $category = $title->load([
            'awardNominations' => fn ($query) => $query
                ->select(['id', 'movie_id', 'award_category_id'])
                ->with([
                    'awardCategory:id,name',
                ]),
        ])->awardNominations
            ->map(fn ($nomination) => $nomination->awardCategory)
            ->filter()
            ->first();

        if ($category !== null) {
            $response
                ->assertSee((string) $category->id)
                ->assertSee($category->name);
        }
    }

    public function test_title_page_surfaces_raw_award_event_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('awardNominations', fn ($query) => $query->whereHas('awardEvent'))
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with award event rows.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-award-events"')
            ->assertSee('imdb_id')
            ->assertSee('name');

        $awardEvent = $title->load([
            'awardNominations' => fn ($query) => $query
                ->select(['id', 'movie_id', 'event_imdb_id'])
                ->with([
                    'awardEvent:imdb_id,name',
                ]),
        ])->awardNominations
            ->map(fn ($nomination) => $nomination->awardEvent)
            ->filter()
            ->first();

        if ($awardEvent !== null) {
            $response
                ->assertSee($awardEvent->imdb_id)
                ->assertSee($awardEvent->name);
        }
    }

    public function test_title_page_surfaces_raw_certificate_attribute_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCertificateAttribute = MovieCertificateAttribute::query()
            ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
            ->with([
                'certificateAttribute:id,name',
                'movieCertificate:id,movie_id,position',
            ])
            ->whereHas('certificateAttribute', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_certificate_id')
            ->orderBy('position')
            ->first();

        if (! $movieCertificateAttribute instanceof MovieCertificateAttribute || ! $movieCertificateAttribute->movieCertificate || ! $movieCertificateAttribute->certificateAttribute) {
            $this->markTestSkipped('The remote catalog does not currently expose movie certificate attribute rows with linked attributes.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCertificateAttribute->movieCertificate->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie certificate attribute row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-certificate-attributes"')
            ->assertSee('id')
            ->assertSee('name')
            ->assertSee((string) $movieCertificateAttribute->certificateAttribute->id)
            ->assertSee($movieCertificateAttribute->certificateAttribute->name);
    }

    public function test_title_page_surfaces_raw_certificate_rating_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('certificateRecords', fn ($query) => $query->whereHas('certificateRating'))
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with certificate ratings.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-certificate-ratings"')
            ->assertSee('id')
            ->assertSee('name');

        $certificateRating = $title->load([
            'certificateRecords' => fn ($query) => $query
                ->select(['id', 'movie_id', 'certificate_rating_id'])
                ->with([
                    'certificateRating:id,name',
                ]),
        ])->certificateRecords
            ->map(fn ($certificate) => $certificate->certificateRating)
            ->filter()
            ->first();

        if ($certificateRating !== null) {
            $response
                ->assertSee((string) $certificateRating->id)
                ->assertSee($certificateRating->name);
        }
    }

    public function test_title_page_surfaces_raw_company_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCredit = MovieCompanyCredit::query()
            ->select(['id', 'movie_id', 'company_imdb_id', 'position'])
            ->with([
                'company:imdb_id,name',
            ])
            ->whereHas('company', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCredit instanceof MovieCompanyCredit || ! $movieCompanyCredit->company) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit rows with linked companies.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-companies"')
            ->assertSee('imdb_id')
            ->assertSee('name')
            ->assertSee($movieCompanyCredit->company->imdb_id)
            ->assertSee($movieCompanyCredit->company->name);
    }

    public function test_title_page_surfaces_raw_company_credit_attribute_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCreditAttribute = MovieCompanyCreditAttribute::query()
            ->select(['movie_company_credit_id', 'company_credit_attribute_id', 'position'])
            ->with([
                'companyCreditAttribute:id,name',
                'movieCompanyCredit:id,movie_id,position',
            ])
            ->whereHas('companyCreditAttribute', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_company_credit_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCreditAttribute instanceof MovieCompanyCreditAttribute || ! $movieCompanyCreditAttribute->movieCompanyCredit || ! $movieCompanyCreditAttribute->companyCreditAttribute) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit attribute rows with linked attributes.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCreditAttribute->movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit attribute row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-company-credit-attributes"')
            ->assertSee('id')
            ->assertSee('name')
            ->assertSee((string) $movieCompanyCreditAttribute->companyCreditAttribute->id)
            ->assertSee($movieCompanyCreditAttribute->companyCreditAttribute->name);
    }

    public function test_title_page_surfaces_raw_company_credit_category_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCredit = MovieCompanyCredit::query()
            ->select(['id', 'movie_id', 'company_credit_category_id', 'position'])
            ->with([
                'companyCreditCategory:id,name',
            ])
            ->whereHas('companyCreditCategory', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCredit instanceof MovieCompanyCredit || ! $movieCompanyCredit->companyCreditCategory) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit rows with linked categories.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit category row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-company-credit-categories"')
            ->assertSee('id')
            ->assertSee('name')
            ->assertSee((string) $movieCompanyCredit->companyCreditCategory->id)
            ->assertSee($movieCompanyCredit->companyCreditCategory->name);
    }

    public function test_title_page_surfaces_raw_country_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('countries')
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with countries.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-countries"')
            ->assertSee('code')
            ->assertSee('name');

        $country = $title->load([
            'countries:code,name',
        ])->countries->first();

        if ($country !== null) {
            $response->assertSee(strtoupper((string) $country->code));

            if ($country->name !== null) {
                $response->assertSee($country->name);
            }
        }
    }
}
