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
                @auth x-on:click="$wire.inWatchlist = ! $wire.inWatchlist" @endauth
                :variant="$buttonVariant"
                :icon="$buttonIcon"
                class="transition-transform duration-200 active:scale-[.98] data-loading:opacity-50 not-data-loading:opacity-100"
            >
                <span wire:text="inWatchlist ? 'Saved to watchlist' : 'Save to watchlist'">
                    {{ $inWatchlist ? 'Saved to watchlist' : 'Save to watchlist' }}
                </span>
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
            <x-ui.alerts :variant="$noticeVariant" :icon="$noticeIcon">
                <x-ui.alerts.description>
                    <span wire:text="inWatchlist ? 'This title is already in your private watchlist.' : 'Add it now to keep it in your personal queue.'">
                        {{ $inWatchlist ? 'This title is already in your private watchlist.' : 'Add it now to keep it in your personal queue.' }}
                    </span>
                </x-ui.alerts.description>
            </x-ui.alerts>
        @endguest
    </div>
</x-ui.card>
