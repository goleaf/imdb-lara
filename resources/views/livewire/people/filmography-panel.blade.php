<div>
<div
    class="has-data-loading:[&_[data-slot=person-filmography-skeletons]]:block has-data-loading:[&_[data-slot=person-filmography-results]]:hidden"
    data-slot="person-filmography-island"
>
<div id="person-filmography" data-slot="person-filmography-panel" class="space-y-4">
    <x-ui.card class="sb-detail-section sb-person-filmography-shell !max-w-none">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <x-ui.heading level="h2" size="lg">Filmography</x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Filter imported MySQL credit groups by role family and reorder titles without leaving the profile page.
                    </x-ui.text>
                </div>

                <x-ui.badge variant="outline" color="neutral" icon="film">
                    {{ $summaryBadgeLabel }}
                </x-ui.badge>
            </div>

            <div class="sb-person-filmography-toolbar">
                <x-ui.field>
                    <x-ui.label>Credit group</x-ui.label>
                    <x-ui.combobox
                        wire:model.live="profession"
                        class="w-full"
                        size="sm"
                        placeholder="All groups"
                        clearable
                    >
                        @foreach ($professionOptions as $professionOption)
                            <x-ui.combobox.option
                                wire:key="filmography-profession-{{ $professionOption['key'] }}"
                                value="{{ $professionOption['value'] }}"
                                icon="briefcase"
                            >
                                {{ $professionOption['label'] }}
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
                        placeholder="Sort credits"
                    >
                        @foreach ($sortOptions as $sortOption)
                            <x-ui.combobox.option
                                wire:key="filmography-sort-{{ $sortOption['value'] }}"
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

    <div class="space-y-4">
        <div data-slot="person-filmography-skeletons" class="hidden space-y-4">
            @foreach (range(1, 2) as $groupIndex)
                <x-ui.card class="sb-detail-section sb-person-filmography-group !max-w-none" wire:key="filmography-skeleton-group-{{ $groupIndex }}">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.skeleton.text class="w-1/4" />
                            <x-ui.skeleton.text class="w-1/6" />
                        </div>

                        <div class="grid gap-3">
                            @foreach (range(1, 3) as $rowIndex)
                                <div class="sb-person-filmography-row" wire:key="filmography-skeleton-row-{{ $groupIndex }}-{{ $rowIndex }}">
                                    <x-ui.skeleton class="aspect-[2/3] w-full rounded-[1rem] sm:max-w-[5rem]" />
                                    <div class="space-y-3">
                                        <x-ui.skeleton.text class="w-1/3" />
                                        <x-ui.skeleton.text class="w-5/6" />
                                        <x-ui.skeleton.text class="w-2/5" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="space-y-4" data-slot="person-filmography-results">
            @forelse ($groups as $group)
                <x-ui.card class="sb-detail-section sb-person-filmography-group !max-w-none">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h3" size="md">{{ $group['label'] }}</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $group['description'] }}
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="film">{{ $group['titleCountLabel'] }}</x-ui.badge>
                        </div>

                        <div class="grid gap-3">
                            @foreach ($group['rows'] as $row)
                                <div class="sb-person-filmography-row">
                                    <a href="{{ route('public.titles.show', $row['title']) }}" class="sb-person-filmography-poster">
                                        @if ($row['title']->preferredPoster())
                                            <img
                                                src="{{ $row['title']->preferredPoster()->url }}"
                                                alt="{{ $row['title']->preferredPoster()->alt_text ?: $row['title']->name }}"
                                                class="aspect-[2/3] w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex aspect-[2/3] items-center justify-center bg-neutral-200 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                                                <x-ui.icon name="film" class="size-8" />
                                            </div>
                                        @endif
                                    </a>

                                    <div class="min-w-0 space-y-3">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-ui.heading level="h4" size="md" class="font-[family-name:var(--font-editorial)] text-[1.2rem] font-semibold tracking-[-0.03em] text-[#f4eee5]">
                                                    <a href="{{ route('public.titles.show', $row['title']) }}" class="hover:opacity-80">
                                                        {{ $row['title']->name }}
                                                    </a>
                                                </x-ui.heading>
                                                <x-ui.badge variant="outline" :icon="$row['title']->typeIcon()">{{ $row['title']->typeLabel() }}</x-ui.badge>
                                                @if ($row['title']->release_year)
                                                    <a href="{{ route('public.years.show', ['year' => $row['title']->release_year]) }}">
                                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $row['title']->release_year }}</x-ui.badge>
                                                    </a>
                                                @endif
                                                @if ($row['title']->displayAverageRating())
                                                    <x-ui.badge icon="star" color="amber">
                                                        {{ number_format($row['title']->displayAverageRating(), 1) }}
                                                    </x-ui.badge>
                                                @endif
                                            </div>

                                            @if ($row['roleSummary'])
                                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                    {{ $row['roleSummary'] }}
                                                </x-ui.text>
                                            @endif

                                            @if ($row['episodeLabel'])
                                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    Episode-specific credits: {{ $row['episodeLabel'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="sb-person-filmography-side">
                                        <x-ui.badge variant="outline" color="neutral" icon="identification">
                                            {{ $row['creditCountLabel'] }}
                                        </x-ui.badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.empty.media>
                        <x-ui.icon name="funnel" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No filmography rows match the current filters.</x-ui.heading>
                </x-ui.empty>
            @endforelse
        </div>
    </div>
</div>
</div>
</div>
