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
                :variant="$watchState === \App\Enums\WatchState::Completed ? 'outline' : 'primary'"
                icon="check-circle"
            >
                {{ $watchState === \App\Enums\WatchState::Completed ? 'Mark unwatched' : 'Mark watched' }}
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
            @if ($statusMessage)
                <x-ui.alerts variant="success" icon="check-circle">
                    <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                </x-ui.alerts>
            @endif

            <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                <x-ui.badge
                    variant="outline"
                    :color="$watchState === \App\Enums\WatchState::Completed ? 'green' : 'neutral'"
                >
                    {{ $watchState ? str($watchState->value)->headline() : 'Not tracked yet' }}
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
