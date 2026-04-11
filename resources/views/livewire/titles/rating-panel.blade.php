<x-ui.card class="!max-w-none" :id="$anchorId">
    <form wire:submit="save" class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <x-ui.heading level="h3" size="md">Your rating</x-ui.heading>
                <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                    Score this title on a ten-point scale. Saving a rating also marks the title as watched in your private history.
                </x-ui.text>
            </div>

            @auth
                <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                    <x-ui.badge variant="outline" :color="$score !== null ? 'amber' : 'neutral'">
                        {{ $score !== null ? sprintf('Saved as %d/10', $score) : 'Not rated yet' }}
                    </x-ui.badge>
                    <span>{{ number_format($ratingCount) }} votes</span>
                </div>
            @endauth
        </div>

        @guest
            <x-ui.alerts variant="info" icon="information-circle">
                <x-ui.alerts.heading>Sign in to save a rating.</x-ui.alerts.heading>
                <x-ui.alerts.description>
                    Your score is stored on a ten-point scale and contributes to the audience average.
                </x-ui.alerts.description>
            </x-ui.alerts>
        @else
            @if ($statusMessage)
                <x-ui.alerts variant="success" icon="check-circle">
                    <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                </x-ui.alerts>
            @endif
        @endguest

        <div class="grid gap-4 sm:grid-cols-[minmax(0,0.5fr)_auto_auto] sm:items-end">
            <x-ui.field>
                <x-ui.label>Score</x-ui.label>
                <x-ui.input
                    wire:model.live.blur="form.score"
                    name="score"
                    type="number"
                    min="1"
                    max="10"
                    step="1"
                />
                <x-ui.error name="form.score" />
            </x-ui.field>

            <x-ui.button type="submit" icon="star" wire:target="save,remove">
                Save rating
            </x-ui.button>

            @auth
                <x-ui.button
                    type="button"
                    variant="ghost"
                    icon="trash"
                    wire:click="remove"
                    wire:target="save,remove"
                >
                    Remove
                </x-ui.button>
            @endauth
        </div>

        <div class="text-sm text-neutral-500 dark:text-neutral-400">
            Average audience rating:
            <span class="font-medium text-neutral-800 dark:text-neutral-100">
                {{ $title->statistic?->average_rating ? number_format((float) $title->statistic->average_rating, 1) : 'Not enough data yet' }}
            </span>
            <span class="ml-2">
                from {{ number_format($ratingCount) }} ratings
            </span>
        </div>

        <div class="space-y-3 rounded-box border border-black/5 p-4 dark:border-white/10">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <x-ui.heading level="h4" size="sm">Audience distribution</x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        A compact score breakdown that updates with every saved rating.
                    </x-ui.text>
                </div>

                <x-ui.badge variant="outline" color="neutral" icon="chart-bar">
                    {{ number_format($ratingCount) }} total ratings
                </x-ui.badge>
            </div>

            @if ($ratingCount > 0)
                <div class="space-y-2">
                    @foreach ($ratingsBreakdown as $bucket)
                        <div class="grid grid-cols-[1.75rem_minmax(0,1fr)_2.75rem] items-center gap-3" wire:key="rating-bucket-{{ $bucket['score'] }}">
                            <div class="text-sm font-medium text-neutral-700 dark:text-neutral-200">{{ $bucket['score'] }}</div>
                            <div class="h-2 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                                <div
                                    class="h-full rounded-full bg-amber-500 dark:bg-amber-400"
                                    style="width: {{ $bucket['percentage'] }}%;"
                                ></div>
                            </div>
                            <div class="text-right text-sm text-neutral-500 dark:text-neutral-400">{{ $bucket['count'] }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                    No audience distribution yet.
                </x-ui.text>
            @endif
        </div>
    </form>
</x-ui.card>
