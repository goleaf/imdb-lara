<x-ui.card class="!max-w-none">
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h3" size="md">Watched status</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Mark this title as watched once you have finished it. The status is stored with your watchlist history.
                </x-ui.text>
            </div>

            <x-ui.button
                wire:click="toggleWatched"
                wire:target="toggleWatched"
                @auth x-on:click="$wire.isCompleted = ! $wire.isCompleted" @endauth
                :variant="$buttonVariant"
                icon="check-circle"
                class="transition-transform duration-200 active:scale-[.98] data-loading:opacity-50 not-data-loading:opacity-100"
            >
                <span wire:text="isCompleted ? 'Mark unwatched' : 'Mark watched'">
                    {{ $watchState === \App\Enums\WatchState::Completed ? 'Mark unwatched' : 'Mark watched' }}
                </span>
            </x-ui.button>
        </div>

        @guest
            <x-ui.alerts variant="info" icon="information-circle">
                <x-ui.alerts.heading>Sign in to track watched history.</x-ui.alerts.heading>
                <x-ui.alerts.description>
                    Your watched state and finished dates are saved to your private watch history.
                </x-ui.alerts.description>
            </x-ui.alerts>
        @else
            <x-ui.alerts wire:show="statusMessage" variant="success" icon="check-circle">
                <x-ui.alerts.description>
                    <span wire:text="statusMessage">{{ $statusMessage }}</span>
                </x-ui.alerts.description>
            </x-ui.alerts>

            <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                <x-ui.badge
                    variant="outline"
                    :color="$trackedStateColor"
                >
                    {{ $trackedStateLabel }}
                </x-ui.badge>

                @if ($startedAt)
                    <span>Started {{ $startedAt->format('M j, Y') }}</span>
                @endif

                @if ($watchedAt)
                    <span>Finished {{ $watchedAt->format('M j, Y') }}</span>
                @endif
            </div>
        @endguest
    </div>
</x-ui.card>
