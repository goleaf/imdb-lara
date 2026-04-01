<div class="space-y-4">
    <x-ui.card class="!max-w-none">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.7fr)_minmax(0,0.7fr)]">
            <x-ui.field>
                <x-ui.label>Keyword</x-ui.label>
                <x-ui.input
                    wire:model.live.debounce.300ms="search"
                    name="people_search"
                    placeholder="Search names, alternate names, or keywords"
                    left-icon="magnifying-glass"
                />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Profession</x-ui.label>
                <x-ui.combobox
                    wire:model.live="profession"
                    class="w-full"
                    size="sm"
                    placeholder="All professions"
                    clearable
                >
                    @foreach ($professions as $professionOption)
                        <x-ui.combobox.option
                            wire:key="people-profession-{{ str($professionOption)->slug()->value() }}"
                            value="{{ $professionOption }}"
                        >
                            {{ $professionOption }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Sort</x-ui.label>
                <x-ui.combobox
                    wire:model.live="sort"
                    class="w-full"
                    size="sm"
                    placeholder="Sort people"
                >
                    @foreach ($sortOptions as $sortOption)
                        <x-ui.combobox.option
                            wire:key="people-sort-{{ $sortOption['value'] }}"
                            value="{{ $sortOption['value'] }}"
                        >
                            {{ $sortOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-70">
        @forelse ($people as $person)
            <x-catalog.person-card :person="$person" />
        @empty
            <div class="sm:col-span-2 xl:col-span-3">
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.heading level="h3">No people match the current filters.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        Adjust the keyword or profession filter to widen the directory.
                    </x-ui.text>
                </x-ui.empty>
            </div>
        @endforelse
    </div>

    <div>
        {{ $people->links() }}
    </div>
</div>
