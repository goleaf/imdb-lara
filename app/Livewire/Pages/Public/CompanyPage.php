<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadCompanyDetailsAction;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CompanyPage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;

    public ?Company $company = null;

    public function mount(Company $company): void
    {
        $this->company = $company;
    }

    public function render(LoadCompanyDetailsAction $loadCompanyDetails): View
    {
        abort_unless($this->company instanceof Company, 404);

        $data = $this->withCollectionDefaults(
            $loadCompanyDetails->handle($this->company),
            ['summaryItems', 'typeOptions', 'countryOptions', 'categoryOptions'],
        );

        return $this->renderPageView(
            'companies.show',
            $data,
        );
    }
}
