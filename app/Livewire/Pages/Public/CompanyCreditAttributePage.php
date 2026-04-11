<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadCompanyCreditAttributeDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\CompanyCreditAttribute;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyCreditAttributePage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;
    use WithPagination;

    public ?CompanyCreditAttribute $companyCreditAttribute = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $company = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $country = '';

    public function mount(CompanyCreditAttribute $companyCreditAttribute): void
    {
        $this->companyCreditAttribute = $companyCreditAttribute;
        $this->normalizeFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->company = '';
        $this->category = '';
        $this->country = '';

        $this->resetPage(pageName: 'attribute_records');
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'attribute_records');
    }

    public function updatedType(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'attribute_records');
    }

    public function updatedCompany(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'attribute_records');
    }

    public function updatedCategory(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'attribute_records');
    }

    public function updatedCountry(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'attribute_records');
    }

    public function render(LoadCompanyCreditAttributeDetailsAction $loadCompanyCreditAttributeDetails): View
    {
        abort_unless($this->companyCreditAttribute instanceof CompanyCreditAttribute, 404);

        $data = $this->withCollectionDefaults(
            $loadCompanyCreditAttributeDetails->handle($this->companyCreditAttribute, $this->filters()),
            ['summaryItems', 'typeOptions', 'companyOptions', 'countryOptions', 'categoryOptions'],
        );

        return $this->renderPageView(
            'company-credit-attributes.show',
            $data,
        );
    }

    /**
     * @return array{q: string, type: string, country: string, company: string, category: string}
     */
    private function filters(): array
    {
        return [
            'q' => $this->search,
            'type' => $this->type,
            'country' => $this->country,
            'company' => $this->company,
            'category' => $this->category,
        ];
    }

    private function normalizeFilters(): void
    {
        $this->type = TitleType::tryFrom($this->type)?->value ?? '';
        $this->country = str($this->country)->trim()->upper()->toString();
        $this->company = trim($this->company);

        $category = trim($this->category);
        $this->category = is_numeric($category) ? (string) (int) $category : '';
    }
}
