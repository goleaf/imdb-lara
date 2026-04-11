<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadAwardNominationDetailsAction;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\AwardNomination;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AwardNominationPage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;

    public ?AwardNomination $awardNomination = null;

    public function mount(AwardNomination $awardNomination): void
    {
        $this->awardNomination = $awardNomination;
    }

    public function render(LoadAwardNominationDetailsAction $loadAwardNominationDetails): View
    {
        abort_unless($this->awardNomination instanceof AwardNomination, 404);

        return $this->renderPageView(
            'awards.nominations.show',
            $this->withCollectionDefaults(
                $loadAwardNominationDetails->handle($this->awardNomination),
                ['linkedNominees', 'linkedTitles', 'summaryItems', 'cohortEntries'],
            ),
        );
    }
}
