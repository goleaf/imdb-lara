<x-ui.card class="!max-w-none">
    <form wire:submit="save" class="space-y-4" id="title-rating">
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
                    <span>{{ number_format((int) ($title->statistic?->rating_count ?? 0)) }} votes</span>
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
                    wire:model.live="form.score"
                    name="score"
                    type="number"
                    min="1"
                    max="10"
                    step="1"
                />
                <x-ui.error name="form.score" />
            </x-ui.field>

            <x-ui.button type="submit" icon="star" wire:target="save,remove" wire:loading.attr="disabled">
                Save rating
            </x-ui.button>

            @auth
                <x-ui.button
                    type="button"
                    variant="ghost"
                    icon="trash"
                    wire:click="remove"
                    wire:target="save,remove"
                    wire:loading.attr="disabled"
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
                from {{ number_format((int) ($title->statistic?->rating_count ?? 0)) }} ratings
            </span>
        </div>
    </form>
</x-ui.card>
