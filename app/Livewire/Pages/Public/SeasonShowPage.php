<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadSeasonDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SeasonShowPage extends Component
{
    use RendersLegacyPage;

    public ?Season $season = null;

    public ?Title $series = null;

    public function mount(Title $series, Season $season): void
    {
        abort_unless(
            in_array($series->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            && $season->series_id === $series->id
            && ($series->is_published || (auth()->user()?->can('view', $series) ?? false)),
            404,
        );

        $this->series = $series;
        $this->season = $season;
    }

    public function render(LoadSeasonDetailsAction $loadSeasonDetails): View
    {
        abort_unless($this->series instanceof Title && $this->season instanceof Season, 404);

        return $this->renderLegacyPage('seasons.show', $loadSeasonDetails->handle(
            $this->series,
            $this->season,
            auth()->user(),
        ));
    }
}
