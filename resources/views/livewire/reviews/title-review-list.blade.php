<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between has-data-loading:[&_[data-slot=review-list-loading]]:inline-flex">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($sortOptions as $sortOption)
                <x-ui.button
                    type="button"
                    size="sm"
                    :variant="$sortOption['variant']"
                    :color="$sortOption['color']"
                    wire:key="title-review-sort-{{ $sortOption['value'] }}"
                    wire:click="setSort('{{ $sortOption['value'] }}')"
                    wire:target="setSort('{{ $sortOption['value'] }}')"
                >
                    {{ $sortOption['label'] }}
                </x-ui.button>
            @endforeach
        </div>

        <div class="flex items-center gap-3 text-sm text-neutral-500 dark:text-neutral-400">
            <span>{{ number_format($reviews->total()) }} published reviews</span>
            <span data-slot="review-list-loading" class="hidden items-center gap-2">
                <x-ui.icon.loading variant="mini" class="size-4" />
                <span>Updating…</span>
            </span>
        </div>
    </div>

    @if ($statusMessage)
        <x-ui.alerts variant="success" icon="check-circle">
            <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
        </x-ui.alerts>
    @endif

    <div class="grid gap-4">
        @forelse ($reviews as $review)
            <x-ui.card class="sb-detail-section !max-w-none" wire:key="title-review-{{ $review->id }}">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-2">
                            <x-ui.heading level="h3" size="md">
                                {{ $review->headline ?: 'Member review' }}
                            </x-ui.heading>

                            <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                <span>{{ $review->author->name }}</span>
                                @if ($review->published_at)
                                    <span>·</span>
                                    <span>{{ $review->published_at->format('M j, Y') }}</span>
                                @endif
                                @if ($review->edited_at)
                                    <span>·</span>
                                    <span>Edited {{ $review->edited_at->format('M j, Y') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge variant="outline" color="neutral" icon="hand-thumb-up">
                                {{ number_format((int) $review->helpful_votes_count) }} helpful
                            </x-ui.badge>

                            @if ($review->contains_spoilers)
                                <x-ui.badge color="red" variant="outline" icon="exclamation-triangle">Spoilers</x-ui.badge>
                            @endif
                        </div>
                    </div>

                    <x-ui.text class="text-neutral-700 dark:text-neutral-200">
                        {{ $review->body }}
                    </x-ui.text>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($reviewPermissions[$review->id]['canVoteHelpful'] ?? false)
                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    :variant="$helpfulButtons[$review->id]['variant'] ?? 'outline'"
                                    :color="$helpfulButtons[$review->id]['color'] ?? 'neutral'"
                                    icon="hand-thumb-up"
                                    wire:click="toggleHelpful({{ $review->id }})"
                                    wire:target="toggleHelpful({{ $review->id }})"
                                >
                                    {{ $helpfulButtons[$review->id]['label'] ?? 'Mark helpful' }}
                                </x-ui.button>
                            @endif
                        </div>

                        @if ($reviewPermissions[$review->id]['canReport'] ?? true)
                            <div class="w-full sm:w-auto">
                                <livewire:reviews.report-review-form :review="$review" :key="'report-'.$review->id" />
                            </div>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="chat-bubble-left-right" class="size-8 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No published reviews yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Be the first member to publish a review for this title.
                </x-ui.text>
            </x-ui.empty>
        @endforelse
    </div>

    @if ($reviews->hasPages())
        <div>
            {{ $reviews->links() }}
        </div>
    @endif
</div>
