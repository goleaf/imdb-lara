<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\GetUserRatingForTitleAction;
use App\Actions\Titles\UpsertRatingAction;
use App\Livewire\Forms\Titles\RatingForm;
use App\Models\Title;
use Livewire\Component;

class RatingPanel extends Component
{
    public Title $title;

    public ?int $score = null;

    public RatingForm $form;

    public function mount(Title $title, GetUserRatingForTitleAction $getUserRatingForTitle): void
    {
        $this->title = $title;
        $this->score = auth()->check()
            ? $getUserRatingForTitle->handle(auth()->user(), $title)
            : null;
        $this->form->score = $this->score;
    }

    public function updatedScore(?int $value): void
    {
        $this->form->score = $value;
    }

    public function save(UpsertRatingAction $upsertRating): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $upsertRating->handle(auth()->user(), $this->title, $this->form->validatedScore());
        $this->title->refresh()->load('statistic');
        $this->score = $this->form->score;
    }

    public function render()
    {
        return view('livewire.titles.rating-panel');
    }
}
