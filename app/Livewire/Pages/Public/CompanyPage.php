<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadCompanyDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyPage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;
    use WithPagination;

    public ?Company $company = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $country = '';

    #[Url]
    public string $category = '';

    public function mount(Company $company): void
    {
        $this->company = $company;
        $this->normalizeFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->country = '';
        $this->category = '';

        $this->resetPage(pageName: 'company_records');
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'company_records');
    }

    public function updatedType(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'company_records');
    }

    public function updatedCountry(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'company_records');
    }

    public function updatedCategory(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'company_records');
    }

    public function render(LoadCompanyDetailsAction $loadCompanyDetails): View
    {
        abort_unless($this->company instanceof Company, 404);

        $data = $this->withCollectionDefaults(
            $loadCompanyDetails->handle($this->company, $this->filters()),
            ['summaryItems', 'typeOptions', 'countryOptions', 'categoryOptions'],
        );

        return $this->renderPageView(
            'companies.show',
            $data,
        );
    }

    /**
     * @return array{q: string, type: string, country: string, category: string}
     */
    private function filters(): array
    {
        return [
            'q' => $this->search,
            'type' => $this->type,
            'country' => $this->country,
            'category' => $this->category,
        ];
    }

    private function normalizeFilters(): void
    {
        $this->type = TitleType::tryFrom($this->type)?->value ?? '';
        $this->country = str($this->country)->trim()->upper()->toString();

        $category = trim($this->category);
        $this->category = is_numeric($category) ? (string) (int) $category : '';
    }
}
