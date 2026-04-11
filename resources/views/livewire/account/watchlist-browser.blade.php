<div>
@island(name: 'watchlist-browser-panel')
<div class="space-y-4" data-slot="watchlist-browser-island">
    <x-ui.card class="!max-w-none">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <div class="space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <x-ui.heading level="h1" size="xl">Your Watchlist</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                            Private title tracking with watched progress, sorting, and filterable queues.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($summaryBadges as $summaryBadge)
                            <x-ui.badge
                                variant="outline"
                                :color="$summaryBadge['color']"
                                :icon="$summaryBadge['icon']"
                            >
                                {{ $summaryBadge['label'] }}
                            </x-ui.badge>
                        @endforeach
                    </div>
                </div>

                @if ($statusMessage)
                    <x-ui.alerts variant="success" icon="check-circle">
                        <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                    </x-ui.alerts>
                @endif

                @if ($visibilityMessage)
                    <x-ui.alerts variant="success" icon="globe-alt">
                        <x-ui.alerts.description>{{ $visibilityMessage }}</x-ui.alerts.description>
                    </x-ui.alerts>
                @endif
            </div>

            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                <div class="space-y-3">
                    <div>
                        <x-ui.heading level="h2" size="md">Watchlist visibility</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Keep your tracking private or publish a public watchlist route on your profile.
                        </x-ui.text>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                        <x-ui.field>
                            <x-ui.label>Visibility</x-ui.label>
                            <x-ui.combobox
                                wire:model.live="visibility"
                                class="w-full"
                                placeholder="Select visibility"
                            >
                                @foreach ($visibilityOptions as $visibilityOption)
                                    <x-ui.combobox.option value="{{ $visibilityOption['value'] }}">
                                        {{ $visibilityOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                            <x-ui.error name="visibility" />
                        </x-ui.field>

                        <x-ui.button
                            type="button"
                            wire:click="saveVisibility"
                            wire:target="saveVisibility"
                            icon="globe-alt"
                        >
                            Save
                        </x-ui.button>
                    </div>

                    @if ($publicWatchlistUrl)
                        <x-ui.link :href="$publicWatchlistUrl" variant="ghost" iconAfter="arrow-right">
                            View public watchlist
                        </x-ui.link>
                    @endif
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="space-y-4 has-data-loading:[&_[data-slot=watchlist-browser-skeletons]]:grid has-data-loading:[&_[data-slot=watchlist-browser-results]]:hidden">
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        Filter by tracking state, title metadata, and sort order without leaving your private queue.
                    </x-ui.text>

                    @if ($hasActiveFilters)
                        <x-ui.button type="button" variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                            Clear filters
                        </x-ui.button>
                    @endif
                </div>

                <div class="grid gap-4 lg:grid-cols-[repeat(5,minmax(0,1fr))]">
                    <x-ui.field>
                        <x-ui.label>State</x-ui.label>
                        <x-ui.combobox wire:model.live="state" class="w-full" placeholder="All tracking">
                            @foreach ($filterOptions['stateOptions'] as $stateOption)
                                <x-ui.combobox.option
                                    wire:key="watchlist-state-{{ $stateOption['value'] }}"
                                    value="{{ $stateOption['value'] }}"
                                    :icon="$stateOption['icon']"
                                >
                                    {{ $stateOption['label'] }}
                                </x-ui.combobox.option>
                            @endforeach
                        </x-ui.combobox>
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Type</x-ui.label>
                        <x-ui.combobox wire:model.live="type" class="w-full" placeholder="All types" clearable>
                            @foreach ($filterOptions['titleTypes'] as $titleType)
                                <x-ui.combobox.option
                                    wire:key="watchlist-type-{{ $titleType->value }}"
                                    value="{{ $titleType->value }}"
                                    :icon="$titleType->icon()"
                                >
                                    {{ $titleType->label() }}
                                </x-ui.combobox.option>
                            @endforeach
                        </x-ui.combobox>
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Genre</x-ui.label>
                        <x-ui.combobox wire:model.live="genre" class="w-full" placeholder="All genres" clearable>
                            @foreach ($filterOptions['genres'] as $genreOption)
                                <x-ui.combobox.option
                                    wire:key="watchlist-genre-{{ $genreOption->id }}"
                                    value="{{ $genreOption->slug }}"
                                    icon="tag"
                                >
                                    {{ $genreOption->name }}
                                </x-ui.combobox.option>
                            @endforeach
                        </x-ui.combobox>
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Year</x-ui.label>
                        <x-ui.combobox wire:model.live="year" class="w-full" placeholder="Any year" clearable>
                            @foreach ($filterOptions['years'] as $yearOption)
                                <x-ui.combobox.option
                                    wire:key="watchlist-year-{{ $yearOption }}"
                                    value="{{ $yearOption }}"
                                    icon="calendar-days"
                                >
                                    {{ $yearOption }}
                                </x-ui.combobox.option>
                            @endforeach
                        </x-ui.combobox>
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Sort</x-ui.label>
                        <x-ui.combobox wire:model.live="sort" class="w-full" placeholder="Date added">
                            @foreach ($filterOptions['sortOptions'] as $sortOption)
                                <x-ui.combobox.option
                                    wire:key="watchlist-sort-{{ $sortOption['value'] }}"
                                    value="{{ $sortOption['value'] }}"
                                    :icon="$sortOption['icon']"
                                >
                                    {{ $sortOption['label'] }}
                                </x-ui.combobox.option>
                            @endforeach
                        </x-ui.combobox>
                    </x-ui.field>
                </div>
            </div>
        </x-ui.card>

        <div data-slot="watchlist-browser-skeletons" class="hidden gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach (range(1, 6) as $index)
                <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="watchlist-skeleton-{{ $index }}">
                    <div class="space-y-4">
                        <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                        <x-ui.skeleton.text class="w-1/3" />
                        <x-ui.skeleton.text class="w-3/4" />
                        <x-ui.skeleton.text class="w-5/6" />
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="space-y-4" data-slot="watchlist-browser-results">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($items as $item)
                    <div wire:key="watchlist-item-{{ $item->id }}">
                        <x-catalog.title-card
                            :title="$item->title"
                            :tracking-state="$item->watch_state"
                            :tracking-added-at="$item->created_at"
                            :tracking-watched-at="$item->watched_at"
                        >
                            <x-ui.button
                                type="button"
                                size="sm"
                                :variant="$itemActionStates[$item->id]['toggleWatchVariant']"
                                wire:click="{{ $itemActionStates[$item->id]['toggleWatchTarget'] }}"
                                wire:target="{{ $itemActionStates[$item->id]['toggleWatchTarget'] }}"
                                icon="check-circle"
                            >
                                {{ $itemActionStates[$item->id]['toggleWatchLabel'] }}
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                size="sm"
                                variant="ghost"
                                wire:click="{{ $itemActionStates[$item->id]['removeTarget'] }}"
                                wire:target="{{ $itemActionStates[$item->id]['removeTarget'] }}"
                                icon="bookmark-slash"
                            >
                                Remove
                            </x-ui.button>
                        </x-catalog.title-card>
                    </div>
                @empty
                    <div class="md:col-span-2 xl:col-span-3">
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                            <x-ui.empty.media>
                                <x-ui.icon name="bookmark" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">{{ $emptyState['heading'] }}</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                {{ $emptyState['text'] }}
                            </x-ui.text>
                        </x-ui.empty>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
@endisland
</div>
