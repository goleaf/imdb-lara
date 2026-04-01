<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\StoreReviewAction;
use App\Models\Title;
use App\ReviewStatus;
use Livewire\Component;

class ReviewComposer extends Component
{
    public Title $title;

    public string $headline = '';

    public string $body = '';

    public bool $containsSpoilers = false;

    public ?string $statusMessage = null;

    protected function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:160'],
            'body' => ['required', 'string', 'min:20'],
            'containsSpoilers' => ['boolean'],
        ];
    }

    public function mount(Title $title): void
    {
        $this->title = $title;
    }

    public function save(StoreReviewAction $storeReview): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $validated = $this->validate();

        $review = $storeReview->handle(auth()->user(), $this->title, [
            'headline' => $validated['headline'],
            'body' => $validated['body'],
            'contains_spoilers' => $validated['containsSpoilers'],
        ]);

        $this->statusMessage = $review->status === ReviewStatus::Published
            ? 'Review published.'
            : 'Review sent to moderation.';

        $this->title->refresh()->load('statistic');
    }

    public function render()
    {
        return view('livewire.titles.review-composer');
    }
}
