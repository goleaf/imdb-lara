<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\LoadAkaAttributeDetailsAction;
use App\Actions\Catalog\LoadCertificateAttributeDetailsAction;
use App\Actions\Catalog\LoadCertificateRatingDetailsAction;
use App\Actions\Catalog\LoadCompanyCreditAttributeDetailsAction;
use App\Actions\Catalog\LoadCompanyDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Public\AkaAttributePage;
use App\Livewire\Pages\Public\CertificateAttributePage;
use App\Livewire\Pages\Public\CertificateRatingPage;
use App\Livewire\Pages\Public\CompanyCreditAttributePage;
use App\Livewire\Pages\Public\CompanyPage;
use App\Models\AkaAttribute;
use App\Models\CertificateAttribute;
use App\Models\CertificateRating;
use App\Models\Company;
use App\Models\CompanyCreditAttribute;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class PublicArchiveLivewireFilterShellTest extends TestCase
{
    public function test_aka_attribute_page_renders_a_livewire_filter_shell(): void
    {
        $akaAttribute = (new AkaAttribute)->forceFill([
            'id' => 1,
            'name' => 'festival-cut',
        ]);
        $akaAttribute->exists = true;

        $payload = [
            'akaAttribute' => $akaAttribute,
            'filters' => ['q' => '', 'type' => '', 'country' => '', 'language' => ''],
            'hasActiveFilters' => false,
            'typeOptions' => collect([['value' => 'movie', 'label' => 'Movie']]),
            'countryOptions' => collect(),
            'languageOptions' => collect(),
            'summaryItems' => collect(),
            'archiveRecords' => $this->emptyPaginator('aka_records'),
            'seo' => $this->seoPayload('AKA attribute archive', route('public.aka-attributes.show', $akaAttribute)),
        ];

        $this->mock(LoadAkaAttributeDetailsAction::class, function (MockInterface $mock) use ($akaAttribute, $payload): void {
            $mock->shouldReceive('handle')
                ->once()
                ->with($akaAttribute, ['q' => '', 'type' => '', 'country' => '', 'language' => ''])
                ->andReturn($payload);
        });

        $this->shareViewErrors();

        Livewire::test(AkaAttributePage::class, ['akaAttribute' => $akaAttribute])
            ->assertSee('Updates live')
            ->assertSeeHtml('wire:model.live.debounce.300ms="search"')
            ->assertSeeHtml('wire:model.live="type"')
            ->assertSeeHtml('wire:model.live="country"')
            ->assertSeeHtml('wire:model.live="language"')
            ->assertDontSee('<form method="GET"', false);
    }

    public function test_company_page_renders_a_livewire_filter_shell(): void
    {
        $company = (new Company)->forceFill([
            'imdb_id' => 'co0000001',
            'name' => 'Studio North',
        ]);
        $company->exists = true;

        $payload = [
            'company' => $company,
            'filters' => ['q' => '', 'type' => '', 'country' => '', 'category' => ''],
            'hasActiveFilters' => false,
            'typeOptions' => collect([['value' => 'movie', 'label' => 'Movie']]),
            'countryOptions' => collect(),
            'categoryOptions' => collect(),
            'summaryItems' => collect(),
            'archiveRecords' => $this->emptyPaginator('company_records'),
            'seo' => $this->seoPayload('Company archive', route('public.companies.show', $company)),
        ];

        $this->mock(LoadCompanyDetailsAction::class, function (MockInterface $mock) use ($company, $payload): void {
            $mock->shouldReceive('handle')
                ->once()
                ->with($company, ['q' => '', 'type' => '', 'country' => '', 'category' => ''])
                ->andReturn($payload);
        });

        $this->shareViewErrors();

        Livewire::test(CompanyPage::class, ['company' => $company])
            ->assertSee('Updates live')
            ->assertSeeHtml('wire:model.live.debounce.300ms="search"')
            ->assertSeeHtml('wire:model.live="type"')
            ->assertSeeHtml('wire:model.live="country"')
            ->assertSeeHtml('wire:model.live="category"')
            ->assertDontSee('<form method="GET"', false);
    }

    public function test_company_credit_attribute_page_renders_a_livewire_filter_shell(): void
    {
        $companyCreditAttribute = (new CompanyCreditAttribute)->forceFill([
            'id' => 1,
            'name' => 'distribution',
        ]);
        $companyCreditAttribute->exists = true;

        $payload = [
            'companyCreditAttribute' => $companyCreditAttribute,
            'filters' => ['q' => '', 'type' => '', 'country' => '', 'company' => '', 'category' => ''],
            'hasActiveFilters' => false,
            'typeOptions' => collect([['value' => 'movie', 'label' => 'Movie']]),
            'companyOptions' => collect(),
            'countryOptions' => collect(),
            'categoryOptions' => collect(),
            'summaryItems' => collect(),
            'archiveRecords' => $this->emptyPaginator('attribute_records'),
            'seo' => $this->seoPayload('Company credit attribute archive', route('public.company-credit-attributes.show', $companyCreditAttribute)),
        ];

        $this->mock(LoadCompanyCreditAttributeDetailsAction::class, function (MockInterface $mock) use ($companyCreditAttribute, $payload): void {
            $mock->shouldReceive('handle')
                ->once()
                ->with($companyCreditAttribute, ['q' => '', 'type' => '', 'country' => '', 'company' => '', 'category' => ''])
                ->andReturn($payload);
        });

        $this->shareViewErrors();

        Livewire::test(CompanyCreditAttributePage::class, ['companyCreditAttribute' => $companyCreditAttribute])
            ->assertSee('Updates live')
            ->assertSeeHtml('wire:model.live.debounce.300ms="search"')
            ->assertSeeHtml('wire:model.live="type"')
            ->assertSeeHtml('wire:model.live="company"')
            ->assertSeeHtml('wire:model.live="category"')
            ->assertSeeHtml('wire:model.live="country"')
            ->assertDontSee('<form method="GET"', false);
    }

    public function test_certificate_attribute_page_renders_a_livewire_filter_shell(): void
    {
        $certificateAttribute = (new CertificateAttribute)->forceFill([
            'id' => 1,
            'name' => 'unedited',
        ]);
        $certificateAttribute->exists = true;

        $payload = [
            'certificateAttribute' => $certificateAttribute,
            'filters' => ['q' => '', 'type' => '', 'country' => ''],
            'hasActiveFilters' => false,
            'typeOptions' => collect([['value' => 'movie', 'label' => 'Movie']]),
            'countryOptions' => collect(),
            'summaryItems' => collect(),
            'archiveRecords' => $this->emptyPaginator('attribute_records'),
            'seo' => $this->seoPayload('Certificate attribute archive', route('public.certificate-attributes.show', $certificateAttribute)),
        ];

        $this->mock(LoadCertificateAttributeDetailsAction::class, function (MockInterface $mock) use ($certificateAttribute, $payload): void {
            $mock->shouldReceive('handle')
                ->once()
                ->with($certificateAttribute, ['q' => '', 'type' => '', 'country' => ''])
                ->andReturn($payload);
        });

        $this->shareViewErrors();

        Livewire::test(CertificateAttributePage::class, ['certificateAttribute' => $certificateAttribute])
            ->assertSee('Updates live')
            ->assertSeeHtml('wire:model.live.debounce.300ms="search"')
            ->assertSeeHtml('wire:model.live="type"')
            ->assertSeeHtml('wire:model.live="country"')
            ->assertDontSee('<form method="GET"', false);
    }

    public function test_certificate_rating_page_renders_a_livewire_filter_shell(): void
    {
        $certificateRating = (new CertificateRating)->forceFill([
            'id' => 1,
            'name' => 'PG-13',
        ]);
        $certificateRating->exists = true;

        $payload = [
            'certificateRating' => $certificateRating,
            'filters' => ['q' => '', 'type' => '', 'country' => ''],
            'hasActiveFilters' => false,
            'typeOptions' => collect([['value' => 'movie', 'label' => 'Movie']]),
            'countryOptions' => collect(),
            'summaryItems' => collect(),
            'archiveRecords' => $this->emptyPaginator('rating_records'),
            'seo' => $this->seoPayload('Certificate rating archive', route('public.certificate-ratings.show', $certificateRating)),
        ];

        $this->mock(LoadCertificateRatingDetailsAction::class, function (MockInterface $mock) use ($certificateRating, $payload): void {
            $mock->shouldReceive('handle')
                ->once()
                ->with($certificateRating, ['q' => '', 'type' => '', 'country' => ''])
                ->andReturn($payload);
        });

        $this->shareViewErrors();

        Livewire::test(CertificateRatingPage::class, ['certificateRating' => $certificateRating])
            ->assertSee('Updates live')
            ->assertSeeHtml('wire:model.live.debounce.300ms="search"')
            ->assertSeeHtml('wire:model.live="type"')
            ->assertSeeHtml('wire:model.live="country"')
            ->assertDontSee('<form method="GET"', false);
    }

    private function emptyPaginator(string $pageName): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: [],
            total: 0,
            perPage: 12,
            currentPage: 1,
            options: [
                'path' => '/',
                'pageName' => $pageName,
            ],
        );
    }

    private function seoPayload(string $title, string $canonical): PageSeoData
    {
        return new PageSeoData(
            title: $title,
            description: $title,
            canonical: $canonical,
        );
    }

    private function shareViewErrors(): void
    {
        view()->share('errors', new ViewErrorBag);
    }
}
