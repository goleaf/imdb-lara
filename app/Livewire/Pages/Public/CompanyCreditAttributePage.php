<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadCompanyCreditAttributeDetailsAction;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\CompanyCreditAttribute;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CompanyCreditAttributePage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;

    public ?CompanyCreditAttribute $companyCreditAttribute = null;

    public function mount(CompanyCreditAttribute $companyCreditAttribute): void
    {
        $this->companyCreditAttribute = $companyCreditAttribute;
    }

    public function render(LoadCompanyCreditAttributeDetailsAction $loadCompanyCreditAttributeDetails): View
    {
        abort_unless($this->companyCreditAttribute instanceof CompanyCreditAttribute, 404);

        $data = $this->withCollectionDefaults(
            $loadCompanyCreditAttributeDetails->handle($this->companyCreditAttribute),
            ['summaryItems', 'typeOptions', 'companyOptions', 'countryOptions', 'categoryOptions'],
        );

        return $this->renderPageView(
            'company-credit-attributes.show',
            $data,
        );
    }
}
