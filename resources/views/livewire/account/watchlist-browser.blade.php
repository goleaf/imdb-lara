@php
    $watchlistStateIcons = [
        'all' => 'squares-2x2',
        'watched' => 'check-circle',
        'unwatched' => 'eye',
        'planned' => 'bookmark',
        'watching' => 'play-circle',
        'completed' => 'check-circle',
        'paused' => 'pause-circle',
        'dropped' => 'x-circle',
    ];

    $watchlistTypeIcons = [
        'movie' => 'film',
        'series' => 'tv',
        'mini_series' => 'tv',
        'documentary' => 'camera',
        'short' => 'film',
        'special' => 'sparkles',
        'episode' => 'rectangle-stack',
    ];

    $watchlistSortIcons = [
        'added' => 'calendar-days',
        'year' => 'calendar-days',
        'rating' => 'star',
        'title' => 'bars-arrow-down',
    ];
@endphp

<div class="space-y-4">
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
                        <x-ui.badge variant="outline" color="neutral" icon="bookmark">
                            {{ number_format($watchlist->items_count) }} saved
                        </x-ui.badge>
                        <x-ui.badge variant="outline" color="green" icon="check-circle">
                            {{ number_format((int) $watchlist->watched_items_count) }} watched
                        </x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" icon="queue-list">
                            {{ number_format(max(0, (int) $watchlist->items_count - (int) $watchlist->watched_items_count)) }} queued
                        </x-ui.badge>
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
                                <x-ui.combobox.option value="private">Private</x-ui.combobox.option>
                                <x-ui.combobox.option value="public">Public</x-ui.combobox.option>
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

                    @if ($watchlist->visibility === \App\Enums\ListVisibility::Public)
                        <x-ui.link :href="route('public.lists.show', [auth()->user(), $watchlist])" variant="ghost" iconAfter="arrow-right">
                            View public watchlist
                        </x-ui.link>
                    @endif
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card class="!max-w-none">
        <div class="grid gap-4 lg:grid-cols-[repeat(5,minmax(0,1fr))]">
            <x-ui.field>
                <x-ui.label>State</x-ui.label>
                <x-ui.combobox wire:model.live="state" class="w-full" placeholder="All tracking">
                    @foreach ($filterOptions['stateOptions'] as $stateOption)
                        <x-ui.combobox.option
                            wire:key="watchlist-state-{{ $stateOption['value'] }}"
                            value="{{ $stateOption['value'] }}"
                            :icon="$watchlistStateIcons[$stateOption['value']] ?? 'squares-2x2'"
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
                            :icon="$watchlistTypeIcons[$titleType->value] ?? 'film'"
                        >
                            {{ str($titleType->value)->headline() }}
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
                            :icon="$watchlistSortIcons[$sortOption['value']] ?? 'bars-arrow-down'"
                        >
                            {{ $sortOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>
        </div>
    </x-ui.card>

    <div wire:loading.delay class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
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

    <div wire:loading.remove class="space-y-4">
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
                            :variant="$item->watch_state === \App\Enums\WatchState::Completed ? 'outline' : 'primary'"
                            wire:click="toggleWatched({{ $item->title_id }})"
                            wire:target="toggleWatched({{ $item->title_id }})"
                            icon="check-circle"
                        >
                            {{ $item->watch_state === \App\Enums\WatchState::Completed ? 'Mark unwatched' : 'Mark watched' }}
                        </x-ui.button>

                        <x-ui.button
                            type="button"
                            size="sm"
                            variant="ghost"
                            wire:click="removeFromWatchlist({{ $item->title_id }})"
                            wire:target="removeFromWatchlist({{ $item->title_id }})"
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
                        <x-ui.heading level="h3">No titles match the current watchlist filters.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Adjust the state, genre, year, or type filters to widen the page.
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
