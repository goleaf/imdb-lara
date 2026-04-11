<?php

namespace App\Livewire\Titles;

use App\Actions\Titles\DeleteRatingAction;
use App\Actions\Titles\GetUserRatingForTitleAction;
use App\Actions\Titles\UpsertRatingAction;
use App\Livewire\Forms\Titles\RatingForm;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RatingPanel extends Component
{
    #[Locked]
    public Title $title;

    public string $anchorId = 'title-rating';

    public ?int $score = null;

    public RatingForm $form;

    public ?string $statusMessage = null;

    public function mount(Title $title, GetUserRatingForTitleAction $getUserRatingForTitle): void
    {
        $this->title = $title->loadMissing('statistic:id,title_id,average_rating,rating_count,rating_distribution');
        $this->score = auth()->check()
            ? $getUserRatingForTitle->handle(auth()->user(), $title)
            : null;
        $this->form->score = $this->score;
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
        $this->dispatch('title-personal-tracking-updated');
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
        $this->title = $this->title->fresh(['statistic:id,title_id,average_rating,rating_count,rating_distribution']) ?? $this->title;
        $this->score = $score;
        $this->form->score = $score;
    }

    #[Computed]
    public function viewData(): array
    {
        $ratingDistribution = $this->title->statistic?->normalizedRatingDistribution()
            ?? TitleStatistic::normalizeRatingDistribution();
        $maxDistributionCount = max(1, max($ratingDistribution));
        $ratingsBreakdown = collect($ratingDistribution)
            ->map(function (int $count, string $score) use ($maxDistributionCount): array {
                return [
                    'score' => (int) $score,
                    'count' => $count,
                    'percentage' => $count > 0
                        ? max(8, (int) round(($count / $maxDistributionCount) * 100))
                        : 0,
                ];
            })
            ->values();

        return [
            'ratingCount' => (int) ($this->title->statistic?->rating_count ?? 0),
            'ratingsBreakdown' => $ratingsBreakdown,
        ];
    }

    public function render(): View
    {
        return view('livewire.titles.rating-panel', $this->viewData);
    }
}
