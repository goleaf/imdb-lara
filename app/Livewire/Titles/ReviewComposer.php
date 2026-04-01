<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\StoreReviewAction;
use App\Enums\ReviewStatus;
use App\Livewire\Forms\Titles\ReviewForm;
use App\Models\Title;
use Livewire\Component;

class ReviewComposer extends Component
{
    public Title $title;

    public string $headline = '';

    public string $body = '';

    public bool $containsSpoilers = false;

    public ReviewForm $form;

    public ?string $statusMessage = null;

    public function mount(Title $title): void
    {
        $this->title = $title;
        $this->form->headline = $this->headline;
        $this->form->body = $this->body;
        $this->form->containsSpoilers = $this->containsSpoilers;
    }

    public function updatedHeadline(string $value): void
    {
        $this->form->headline = $value;
    }

    public function updatedBody(string $value): void
    {
        $this->form->body = $value;
    }

    public function updatedContainsSpoilers(bool $value): void
    {
        $this->form->containsSpoilers = $value;
    }

    public function save(StoreReviewAction $storeReview): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $review = $storeReview->handle(auth()->user(), $this->title, $this->form->payload());

        $this->statusMessage = $review->status === ReviewStatus::Published
            ? 'Review published.'
            : 'Review sent to moderation.';

        $this->title->refresh()->load('statistic');
        $this->headline = $this->form->headline;
        $this->body = $this->form->body;
        $this->containsSpoilers = $this->form->containsSpoilers;
    }

    public function render()
    {
        return view('livewire.titles.review-composer');
    }
}
