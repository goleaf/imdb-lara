<?php

use App\Actions\Home\GetLatestReviewFeedAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

new class extends Component
{
    public EloquentCollection $reviews;

    public ?string $errorMessage = null;

    public function mount(GetLatestReviewFeedAction $getLatestReviewFeed): void
    {
        $this->reviews = new EloquentCollection;

        try {
            $this->reviews = $getLatestReviewFeed->handle();
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Latest reviews could not be loaded right now.';
        }
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <x-ui.heading level="h2" size="lg">Latest Reviews</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                        Loading the freshest published community writing.
                    </x-ui.text>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                @foreach (range(1, 4) as $index)
                    <x-ui.card class="!max-w-none" wire:key="home-review-placeholder-{{ $index }}">
                        <div class="grid gap-4 md:grid-cols-[8rem_minmax(0,1fr)]">
                            <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                            <div class="space-y-3">
                                <x-ui.skeleton.text class="w-1/4" />
                                <x-ui.skeleton.text class="w-2/3" />
                                <x-ui.skeleton.text class="w-full" />
                                <x-ui.skeleton.text class="w-4/5" />
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    @endplaceholder

    <div class="sb-home-section space-y-4 rounded-[1.6rem] p-4 sm:p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg" class="sb-home-section-heading inline-flex items-center gap-2">
                    <x-ui.icon name="chat-bubble-left-right" class="size-5 text-[#d6b574]" />
                    <span>Latest Reviews</span>
                </x-ui.heading>
                <x-ui.text class="sb-home-section-copy max-w-3xl text-sm">
                    Freshly published audience reviews across movies, TV, and documentaries.
                </x-ui.text>
            </div>

            <x-ui.link :href="route('public.reviews.latest')" variant="ghost">
                See all reviews
            </x-ui.link>
        </div>

        @if ($errorMessage)
            <x-ui.card class="!max-w-none border-dashed border-red-200/70 dark:border-red-400/40">
                <div class="space-y-2">
                    <x-ui.heading level="h3">Section unavailable</x-ui.heading>
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        {{ $errorMessage }}
                    </x-ui.text>
                </div>
            </x-ui.card>
        @elseif ($reviews->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="chat-bubble-left-right" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No published reviews are available yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Reviews will appear here once they pass moderation and go public.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($reviews as $review)
                    <x-ui.card class="sb-poster-card !max-w-none rounded-[1.35rem]" wire:key="home-review-{{ $review->id }}">
                        <div class="grid gap-4 md:grid-cols-[8rem_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                @if ($review->title->preferredPoster())
                                    <img
                                        src="{{ $review->title->preferredPoster()->url }}"
                                        alt="{{ $review->title->preferredPoster()->alt_text ?: $review->title->name }}"
                                        class="aspect-[2/3] w-full object-cover"
                                    >
                                @else
                                    <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                        <x-ui.icon name="chat-bubble-left-right" class="size-10" />
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge variant="outline" icon="film">{{ str($review->title->title_type->value)->headline() }}</x-ui.badge>
                                    @if ($review->contains_spoilers)
                                        <x-ui.badge variant="outline" color="red" icon="eye-slash">Spoilers</x-ui.badge>
                                    @endif
                                    @if ($review->published_at)
                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $review->published_at->format('M j, Y') }}</x-ui.badge>
                                    @endif
                                </div>

                                <div>
                                    <x-ui.heading level="h3" size="md">
                                        <a href="{{ route('public.titles.show', $review->title) }}" class="hover:opacity-80">
                                            {{ $review->headline ?: 'Member review for '.$review->title->name }}
                                        </a>
                                    </x-ui.heading>
                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $review->author->name }} on {{ $review->title->name }}
                                    </div>
                                </div>

                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ str($review->body)->limit(220) }}
                                </x-ui.text>

                                <x-ui.link :href="route('public.titles.show', $review->title)" variant="ghost">
                                    View title page
                                </x-ui.link>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>
</div>
