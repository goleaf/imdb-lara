<?php

namespace App\Livewire\Reviews;

use App\Actions\Catalog\BuildTitleReviewsQueryAction;
use App\Actions\Titles\ToggleReviewHelpfulVoteAction;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
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
        $viewer = auth()->user();
        $reviews = $buildTitleReviewsQuery
            ->handle($this->title, $this->sort, $viewer)
            ->paginate(10, pageName: 'reviewsPage')
            ->withQueryString();
        $reviewCollection = $reviews->getCollection();

        return view('livewire.reviews.title-review-list', [
            'helpfulButtons' => $this->helpfulButtons($reviewCollection),
            'reviewPermissions' => $this->reviewPermissions($reviewCollection, $viewer),
            'reviews' => $reviews,
            'sortOptions' => $this->sortOptions(),
        ]);
    }

    /**
     * @return list<array{color: string, label: string, value: string, variant: string}>
     */
    private function sortOptions(): array
    {
        return collect([
            ['value' => 'newest', 'label' => 'Newest'],
            ['value' => 'helpful', 'label' => 'Most helpful'],
        ])->map(fn (array $sortOption): array => [
            ...$sortOption,
            'color' => $this->sort === $sortOption['value'] ? 'amber' : 'neutral',
            'variant' => $this->sort === $sortOption['value'] ? 'primary' : 'outline',
        ])->all();
    }

    /**
     * @param  Collection<int, Review>  $reviews
     * @return array<int, array{color: string, label: string, variant: string}>
     */
    private function helpfulButtons(Collection $reviews): array
    {
        return $reviews
            ->mapWithKeys(function (Review $review): array {
                $hasHelpfulVote = (int) ($review->current_user_helpful_votes_count ?? 0) > 0;

                return [
                    $review->id => [
                        'color' => $hasHelpfulVote ? 'amber' : 'neutral',
                        'label' => $hasHelpfulVote ? 'Helpful saved' : 'Mark helpful',
                        'variant' => $hasHelpfulVote ? 'primary' : 'outline',
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, Review>  $reviews
     * @return array<int, array{canReport: bool, canVoteHelpful: bool}>
     */
    private function reviewPermissions(Collection $reviews, ?User $viewer): array
    {
        return $reviews
            ->mapWithKeys(fn (Review $review): array => [
                $review->id => [
                    'canReport' => $viewer === null || $viewer->can('report', $review),
                    'canVoteHelpful' => $viewer?->can('voteHelpful', $review) ?? false,
                ],
            ])
            ->all();
    }
}
