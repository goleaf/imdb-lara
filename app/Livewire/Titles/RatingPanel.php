<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\UpsertRatingAction;
use App\Models\Title;
use Livewire\Component;

class RatingPanel extends Component
{
    public Title $title;

    public ?int $score = null;

    protected function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'between:1,10'],
        ];
    }

    public function mount(Title $title): void
    {
        $this->title = $title;
        $this->score = auth()->check()
            ? auth()->user()->ratings()->where('title_id', $title->id)->value('score')
            : null;
    }

    public function save(UpsertRatingAction $upsertRating): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $validated = $this->validate();

        $upsertRating->handle(auth()->user(), $this->title, (int) $validated['score']);
        $this->title->refresh()->load('statistic');
    }

    public function render()
    {
        return view('livewire.titles.rating-panel');
    }
}
