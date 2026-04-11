<div class="space-y-4">
    <x-ui.card class="!max-w-none">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <div class="space-y-3">
                <div class="space-y-2">
                    <x-ui.heading level="h1" size="xl">Loading list</x-ui.heading>
                    <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                        Loading list details, sharing controls, and saved titles.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" color="neutral" icon="lock-closed">Loading</x-ui.badge>
                    <x-ui.badge variant="outline" color="slate" icon="queue-list">Loading</x-ui.badge>
                </div>
            </div>

            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                <div class="space-y-3">
                    <x-ui.heading level="h2" size="md">Share route</x-ui.heading>
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        Loading share-state controls.
                    </x-ui.text>
                    <x-ui.skeleton class="h-9 w-36 rounded-full" />
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="space-y-2">
                    <x-ui.heading level="h2" size="lg">List details</x-ui.heading>
                    <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                        Loading list metadata.
                    </x-ui.text>
                </div>

                <div class="grid gap-4">
                    @foreach (range(1, 3) as $index)
                        <x-ui.field wire:key="manage-list-placeholder-field-{{ $index }}">
                            <x-ui.label>Loading</x-ui.label>
                            <x-ui.skeleton class="h-10 w-full rounded-box" />
                        </x-ui.field>
                    @endforeach
                </div>

                <div class="flex flex-wrap justify-between gap-3">
                    <x-ui.skeleton class="h-10 w-28 rounded-full" />
                    <x-ui.skeleton class="h-10 w-32 rounded-full" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="space-y-2">
                    <x-ui.heading level="h2" size="lg">Add titles</x-ui.heading>
                    <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                        Loading catalog search for this list.
                    </x-ui.text>
                </div>

                <x-ui.field>
                    <x-ui.label>Find titles</x-ui.label>
                    <x-ui.skeleton class="h-10 w-full rounded-box" />
                </x-ui.field>

                <div class="space-y-2">
                    @foreach (range(1, 3) as $index)
                        <x-ui.skeleton.text class="w-full" wire:key="manage-list-placeholder-suggestion-{{ $index }}" />
                    @endforeach
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach (range(1, 6) as $index)
            <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="manage-list-placeholder-card-{{ $index }}">
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
