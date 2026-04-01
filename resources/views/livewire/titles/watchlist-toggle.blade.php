<x-ui.card class="!max-w-none">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-ui.heading level="h3" size="md">Watchlist</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Save this title to your personal queue for later viewing.
            </x-ui.text>
        </div>

        <x-ui.button
            wire:click="toggle"
            wire:target="toggle"
            :variant="$inWatchlist ? 'outline' : 'primary'"
            :icon="$inWatchlist ? 'bookmark-square' : 'bookmark'"
        >
            {{ $inWatchlist ? 'Saved to watchlist' : 'Save to watchlist' }}
        </x-ui.button>
    </div>
</x-ui.card>
