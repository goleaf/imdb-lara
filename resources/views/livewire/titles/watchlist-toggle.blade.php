<x-ui.card class="!max-w-none">
    <div class="space-y-4">
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

        @guest
            <x-ui.alerts variant="info" icon="information-circle">
                <x-ui.alerts.heading>Sign in to track titles in your watchlist.</x-ui.alerts.heading>
                <x-ui.alerts.description>
                    Your watchlist is private and follows you across the catalog.
                </x-ui.alerts.description>
            </x-ui.alerts>
        @else
            <x-ui.alerts :variant="$inWatchlist ? 'success' : 'info'" :icon="$inWatchlist ? 'check-circle' : 'information-circle'">
                <x-ui.alerts.description>
                    {{ $inWatchlist ? 'This title is already in your private watchlist.' : 'Add it now to keep it in your personal queue.' }}
                </x-ui.alerts.description>
            </x-ui.alerts>
        @endguest
    </div>
</x-ui.card>
