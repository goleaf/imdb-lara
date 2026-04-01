<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\DeleteRatingAction;
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

    public ?string $statusMessage = null;

    public function mount(Title $title, GetUserRatingForTitleAction $getUserRatingForTitle): void
    {
        $this->title = $title->loadMissing('statistic:id,title_id,average_rating,rating_count');
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

        $rating = $upsertRating->handle(auth()->user(), $this->title, $this->form->validatedScore());

        $this->refreshTitleState($rating->score);
        $this->statusMessage = sprintf('Saved as %d/10.', $rating->score);
    }

    public function remove(DeleteRatingAction $deleteRating): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        if (! $deleteRating->handle(auth()->user(), $this->title)) {
            $this->statusMessage = 'There is no saved rating to remove.';

            return;
        }

        $this->refreshTitleState(null);
        $this->statusMessage = 'Your rating was removed.';
    }

    private function refreshTitleState(?int $score): void
    {
        $this->title = $this->title->fresh(['statistic:id,title_id,average_rating,rating_count']) ?? $this->title;
        $this->score = $score;
        $this->form->score = $score;
    }

    public function render()
    {
        return view('livewire.titles.rating-panel');
    }
}
