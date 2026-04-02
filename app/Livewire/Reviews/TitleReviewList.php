<?php

namespace App\Livewire\Reviews;

use App\Actions\Catalog\BuildTitleReviewsQueryAction;
use App\Actions\Titles\ToggleReviewHelpfulVoteAction;
use App\Models\Review;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TitleReviewList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Title $title;

    public string $sort = 'newest';

    public ?string $statusMessage = null;

    public function mount(Title $title): void
    {
        $this->title = $title;
    }

    public function setSort(string $sort): void
    {
        if (! in_array($sort, ['newest', 'helpful'], true)) {
            return;
        }

        if ($this->sort === $sort) {
            return;
        }

        $this->sort = $sort;
        $this->resetPage(pageName: 'reviewsPage');
    }

    public function toggleHelpful(int $reviewId, ToggleReviewHelpfulVoteAction $toggleReviewHelpfulVote): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $review = Review::query()
            ->select(['id', 'user_id', 'title_id', 'status'])
            ->with('author:id')
            ->whereBelongsTo($this->title)
            ->findOrFail($reviewId);

        $this->authorize('voteHelpful', $review);

        $voteState = $toggleReviewHelpfulVote->handle(auth()->user(), $review);

        $this->statusMessage = $voteState['hasHelpfulVote']
            ? 'Marked as helpful.'
            : 'Helpful vote removed.';
    }

    #[On('title-review-updated')]
    public function refreshReviewList(): void
    {
        $this->statusMessage = null;
        $this->resetPage(pageName: 'reviewsPage');
    }

    public function render(BuildTitleReviewsQueryAction $buildTitleReviewsQuery): View
    {
        $reviews = $buildTitleReviewsQuery
            ->handle($this->title, $this->sort, auth()->user())
            ->paginate(10, pageName: 'reviewsPage')
            ->withQueryString();

        return view('livewire.reviews.title-review-list', [
            'helpfulVoteStates' => $reviews->getCollection()
                ->mapWithKeys(fn (Review $review): array => [$review->id => (int) ($review->current_user_helpful_votes_count ?? 0) > 0])
                ->all(),
            'reviews' => $reviews,
            'sortOptions' => [
                ['value' => 'newest', 'label' => 'Newest'],
                ['value' => 'helpful', 'label' => 'Most helpful'],
            ],
        ]);
    }
}
