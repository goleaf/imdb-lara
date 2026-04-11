<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadAkaAttributeDetailsAction;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\AkaAttribute;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AkaAttributePage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;

    public ?AkaAttribute $akaAttribute = null;

    public function mount(AkaAttribute $akaAttribute): void
    {
        $this->akaAttribute = $akaAttribute;
    }

    public function render(LoadAkaAttributeDetailsAction $loadAkaAttributeDetails): View
    {
        abort_unless($this->akaAttribute instanceof AkaAttribute, 404);

        $data = $this->withCollectionDefaults(
            $loadAkaAttributeDetails->handle($this->akaAttribute),
            ['summaryItems', 'typeOptions', 'countryOptions', 'languageOptions'],
        );

        return $this->renderPageView(
            'aka-attributes.show',
            $data,
        );
    }
}
