<x-ui.card class="!max-w-none">
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-2">
            <x-ui.heading level="h3" size="md">Your rating</x-ui.heading>
            <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                Score this title on a ten-point scale.
            </x-ui.text>
        </div>

        <div class="grid gap-4 sm:grid-cols-[minmax(0,0.5fr)_auto] sm:items-end">
            <x-ui.field>
                <x-ui.label>Score</x-ui.label>
                <x-ui.input
                    wire:model.live="score"
                    name="score"
                    type="number"
                    min="1"
                    max="10"
                    step="1"
                />
                <x-ui.error name="score" />
            </x-ui.field>

            <x-ui.button type="submit" icon="star">
                Save rating
            </x-ui.button>
        </div>

        <div class="text-sm text-neutral-500 dark:text-neutral-400">
            Average audience rating:
            <span class="font-medium text-neutral-800 dark:text-neutral-100">
                {{ $title->statistic?->average_rating ? number_format((float) $title->statistic->average_rating, 1) : 'Not enough data yet' }}
            </span>
        </div>
    </form>
</x-ui.card>
