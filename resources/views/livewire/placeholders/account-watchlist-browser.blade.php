<div class="space-y-4">
    <x-ui.card class="!max-w-none">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <div class="space-y-3">
                <div class="space-y-2">
                    <x-ui.heading level="h1" size="xl">Your Watchlist</x-ui.heading>
                    <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                        Loading your private tracking queue and filters.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" color="neutral" icon="bookmark">Loading</x-ui.badge>
                    <x-ui.badge variant="outline" color="green" icon="check-circle">Loading</x-ui.badge>
                    <x-ui.badge variant="outline" color="slate" icon="queue-list">Loading</x-ui.badge>
                </div>
            </div>

            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                <div class="space-y-3">
                    <div class="space-y-2">
                        <x-ui.heading level="h2" size="md">Watchlist visibility</x-ui.heading>
                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                            Loading sharing controls.
                        </x-ui.text>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                        <x-ui.field>
                            <x-ui.label>Visibility</x-ui.label>
                            <x-ui.skeleton class="h-10 w-full rounded-box" />
                        </x-ui.field>

                        <x-ui.skeleton class="h-10 w-24 rounded-full" />
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card class="!max-w-none">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-ui.skeleton.text class="w-2/3" />
                <x-ui.skeleton class="h-9 w-28 rounded-full" />
            </div>

            <div class="grid gap-4 lg:grid-cols-[repeat(5,minmax(0,1fr))]">
                @foreach (range(1, 5) as $index)
                    <x-ui.field wire:key="watchlist-placeholder-filter-{{ $index }}">
                        <x-ui.label>Loading</x-ui.label>
                        <x-ui.skeleton class="h-10 w-full rounded-box" />
                    </x-ui.field>
                @endforeach
            </div>
        </div>
    </x-ui.card>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach (range(1, 6) as $index)
            <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="watchlist-placeholder-card-{{ $index }}">
                <div class="space-y-4">
                    <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                    <x-ui.skeleton.text class="w-1/3" />
                    <x-ui.skeleton.text class="w-3/4" />
                    <x-ui.skeleton.text class="w-5/6" />
                </div>
            </x-ui.card>
        @endforeach
    </div>
</div>
