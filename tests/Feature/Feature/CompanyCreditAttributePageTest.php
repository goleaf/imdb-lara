<?php

namespace Tests\Feature\Feature;

use App\Models\MovieCompanyCreditAttribute;
use Livewire\Livewire;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CompanyCreditAttributePageTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_company_credit_attribute_page_renders_related_company_credit_archive(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCreditAttribute = MovieCompanyCreditAttribute::query()
            ->select(['movie_company_credit_id', 'company_credit_attribute_id', 'position'])
            ->with([
                'companyCreditAttribute:id,name',
                'movieCompanyCredit:id,movie_id,company_imdb_id,company_credit_category_id,start_year,end_year,position',
                'movieCompanyCredit.company:imdb_id,name',
                'movieCompanyCredit.companyCreditCategory:id,name',
                'movieCompanyCredit.title' => fn ($titleQuery) => $titleQuery
                    ->selectCatalogCardColumns()
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->whereHas('companyCreditAttribute', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('movieCompanyCredit.company', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('movieCompanyCredit.title', fn ($query) => $query->publishedCatalog()->whereNotNull('movies.primarytitle'))
            ->orderBy('movie_company_credit_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCreditAttribute instanceof MovieCompanyCreditAttribute
            || ! $movieCompanyCreditAttribute->companyCreditAttribute
            || ! $movieCompanyCreditAttribute->movieCompanyCredit
            || ! $movieCompanyCreditAttribute->movieCompanyCredit->company
            || ! $movieCompanyCreditAttribute->movieCompanyCredit->title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with a linked company credit attribute row.');
        }

        $response = $this->get(route('public.company-credit-attributes.show', $movieCompanyCreditAttribute->companyCreditAttribute))
            ->assertOk()
            ->assertSeeHtml('data-slot="company-credit-attribute-detail-hero"')
            ->assertSeeHtml('data-slot="company-credit-attribute-detail-filters"')
            ->assertSeeHtml('data-slot="company-credit-attribute-detail-records"')
            ->assertSee($movieCompanyCreditAttribute->companyCreditAttribute->name)
            ->assertSee($movieCompanyCreditAttribute->movieCompanyCredit->company->name)
            ->assertSee($movieCompanyCreditAttribute->movieCompanyCredit->title->name)
            ->assertSee('Updates live')
            ->assertDontSee('<form method="GET"', false)
            ->assertSee(route('public.companies.show', $movieCompanyCreditAttribute->movieCompanyCredit->company), false)
            ->assertSee(route('public.titles.show', $movieCompanyCreditAttribute->movieCompanyCredit->title), false);

        if ($movieCompanyCreditAttribute->movieCompanyCredit->companyCreditCategory?->name !== null) {
            $response->assertSee($movieCompanyCreditAttribute->movieCompanyCredit->companyCreditCategory->name);
        }

        $this->get(route('public.company-credit-attributes.show', [
            'companyCreditAttribute' => $movieCompanyCreditAttribute->companyCreditAttribute,
            'q' => $movieCompanyCreditAttribute->movieCompanyCredit->title->name,
            'type' => $movieCompanyCreditAttribute->movieCompanyCredit->title->title_type->value,
            'company' => $movieCompanyCreditAttribute->movieCompanyCredit->company->imdb_id,
        ]))
            ->assertOk()
            ->assertSeeHtml('data-slot="company-credit-attribute-detail-filters"')
            ->assertSee($movieCompanyCreditAttribute->movieCompanyCredit->title->name)
            ->assertSee('Updates live')
            ->assertDontSee('<form method="GET"', false);
    }
}
