<?php

namespace Tests\Feature\Feature;

use App\Models\MovieCompanyCredit;
use Livewire\Livewire;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CompanyPageTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_company_page_renders_related_company_credit_archive(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCredit = MovieCompanyCredit::query()
            ->select(['id', 'movie_id', 'company_imdb_id', 'company_credit_category_id', 'start_year', 'end_year', 'position'])
            ->with([
                'company:imdb_id,name',
                'companyCreditCategory:id,name',
                'title' => fn ($titleQuery) => $titleQuery
                    ->selectCatalogCardColumns()
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->whereHas('company', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('title', fn ($query) => $query->publishedCatalog()->whereNotNull('movies.primarytitle'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCredit instanceof MovieCompanyCredit || ! $movieCompanyCredit->company || ! $movieCompanyCredit->title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with a linked company credit row.');
        }

        $response = $this->get(route('public.companies.show', $movieCompanyCredit->company))
            ->assertOk()
            ->assertSeeHtml('data-slot="company-detail-hero"')
            ->assertSeeHtml('data-slot="company-detail-filters"')
            ->assertSeeHtml('data-slot="company-detail-records"')
            ->assertSee($movieCompanyCredit->company->name)
            ->assertSee($movieCompanyCredit->title->name)
            ->assertSee('Updates live')
            ->assertDontSee('<form method="GET"', false)
            ->assertSee(route('public.titles.show', $movieCompanyCredit->title), false);

        if ($movieCompanyCredit->companyCreditCategory?->name !== null) {
            $response->assertSee($movieCompanyCredit->companyCreditCategory->name);
        }

        $this->get(route('public.companies.show', [
            'company' => $movieCompanyCredit->company,
            'q' => $movieCompanyCredit->title->name,
            'type' => $movieCompanyCredit->title->title_type->value,
        ]))
            ->assertOk()
            ->assertSeeHtml('data-slot="company-detail-filters"')
            ->assertSee($movieCompanyCredit->title->name)
            ->assertSee('Updates live')
            ->assertDontSee('<form method="GET"', false);
    }
}
