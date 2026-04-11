<x-ui.card class="!max-w-none">
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h3" size="md">Season watch progress</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Track how far you are through {{ $season->name }} and mark the remaining episodes finished in one step.
                </x-ui.text>
            </div>

            <x-ui.button
                wire:click="markSeasonWatched"
                wire:target="markSeasonWatched"
                :variant="$markButton['variant']"
                icon="check-circle"
            >
                {{ $markButton['label'] }}
            </x-ui.button>
        </div>

        @guest
            <x-ui.alerts variant="info" icon="information-circle">
                <x-ui.alerts.heading>Sign in to track season progress.</x-ui.alerts.heading>
                <x-ui.alerts.description>
                    Mark finished episodes in bulk and keep your season progress synced with your watch history.
                </x-ui.alerts.description>
            </x-ui.alerts>
        @else
            @if ($statusMessage && $statusAlert)
                <x-ui.alerts
                    :variant="$statusAlert['variant']"
                    :icon="$statusAlert['icon']"
                >
                    <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                </x-ui.alerts>
            @endif

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                    <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Completed</div>
                    <div class="mt-2 text-2xl font-semibold">{{ number_format($watchedEpisodes) }}</div>
                </div>
                <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                    <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Remaining</div>
                    <div class="mt-2 text-2xl font-semibold">{{ number_format($remainingEpisodes) }}</div>
                </div>
                <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                    <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Season progress</div>
                    <div class="mt-2 text-2xl font-semibold">{{ $percentage }}%</div>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3 text-sm text-neutral-500 dark:text-neutral-400">
                    <span>{{ $progressSummary }}</span>
                    <span>{{ $percentage }}%</span>
                </div>

                <div class="h-2 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ $percentage }}%;"></div>
                </div>
            </div>
        @endguest
    </div>
</x-ui.card>
