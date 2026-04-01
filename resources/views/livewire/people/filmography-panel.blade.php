<div class="space-y-4">
    <x-ui.card class="!max-w-none">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <x-ui.heading level="h2" size="lg">Filmography</x-ui.heading>
                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                    Credits grouped by profession, with filters for a tighter view of the public record.
                </x-ui.text>
            </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,0.8fr)]">
            <x-ui.field>
                <x-ui.label>Profession</x-ui.label>
                <x-ui.combobox
                    wire:model.live="profession"
                    class="w-full"
                    size="sm"
                    placeholder="All professions"
                    clearable
                >
                    @foreach ($professionOptions as $professionOption)
                        <x-ui.combobox.option
                            wire:key="filmography-profession-{{ str($professionOption)->slug()->value() }}"
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
                    placeholder="Sort credits"
                >
                    @foreach ($sortOptions as $sortOption)
                        <x-ui.combobox.option
                            wire:key="filmography-sort-{{ $sortOption['value'] }}"
                            value="{{ $sortOption['value'] }}"
                        >
                            {{ $sortOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="space-y-4" wire:loading.class="opacity-70">
        @forelse ($groups as $group)
            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h3" size="md">{{ $group['label'] }}</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral">{{ number_format($group['rows']->count()) }} titles</x-ui.badge>
                    </div>

                    <div class="grid gap-3">
                        @foreach ($group['rows'] as $row)
                            <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-ui.heading level="h4" size="md">
                                                <a href="{{ route('public.titles.show', $row['title']) }}" class="hover:opacity-80">
                                                    {{ $row['title']->name }}
                                                </a>
                                            </x-ui.heading>
                                            <x-ui.badge variant="outline">{{ str($row['title']->title_type->value)->headline() }}</x-ui.badge>
                                            @if ($row['title']->release_year)
                                                <a href="{{ route('public.years.show', ['year' => $row['title']->release_year]) }}">
                                                    <x-ui.badge variant="outline" color="slate">{{ $row['title']->release_year }}</x-ui.badge>
                                                </a>
                                            @endif
                                            @if ($row['title']->statistic?->average_rating)
                                                <x-ui.badge icon="star" color="amber">
                                                    {{ number_format((float) $row['title']->statistic->average_rating, 1) }}
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

                                    <x-ui.badge variant="outline" color="neutral">
                                        {{ number_format($row['creditCount']) }} credit{{ $row['creditCount'] === 1 ? '' : 's' }}
                                    </x-ui.badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.heading level="h3">No filmography rows match the current filters.</x-ui.heading>
            </x-ui.empty>
        @endforelse
    </div>
</div>
