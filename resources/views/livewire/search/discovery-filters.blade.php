<div class="space-y-4">
    <x-ui.card class="!max-w-none">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_repeat(4,minmax(0,0.5fr))]">
            <x-ui.field>
                <x-ui.label>Keyword</x-ui.label>
                <x-ui.autocomplete
                    wire:model.live.debounce.300ms="search"
                    name="search"
                    placeholder="Search titles, synonyms, or plot notes"
                    left-icon="magnifying-glass"
                    clearable
                >
                    @foreach ($searchSuggestions as $suggestedTitle)
                        <x-ui.autocomplete.item
                            wire:key="discover-suggestion-{{ $suggestedTitle->id }}"
                            :value="$suggestedTitle->name"
                            :label="$suggestedTitle->name"
                        >
                            <div class="flex items-center justify-between gap-3 py-1">
                                <div>
                                    <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                        {{ $suggestedTitle->name }}
                                    </div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ str($suggestedTitle->title_type->value)->headline() }}
                                        @if ($suggestedTitle->release_year)
                                            · {{ $suggestedTitle->release_year }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </x-ui.autocomplete.item>
                    @endforeach
                </x-ui.autocomplete>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Genre</x-ui.label>
                <x-ui.combobox
                    wire:model.live="genre"
                    class="w-full"
                    size="sm"
                    placeholder="All genres"
                    clearable
                >
                    @foreach ($genres as $genreOption)
                        <x-ui.combobox.option
                            wire:key="discover-genre-{{ $genreOption->id }}"
                            value="{{ $genreOption->slug }}"
                        >
                            {{ $genreOption->name }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Type</x-ui.label>
                <x-ui.combobox
                    wire:model.live="type"
                    class="w-full"
                    size="sm"
                    placeholder="All types"
                    clearable
                >
                    @foreach ($titleTypes as $typeOption)
                        <x-ui.combobox.option
                            wire:key="discover-type-{{ $typeOption->value }}"
                            value="{{ $typeOption->value }}"
                        >
                            {{ str($typeOption->value)->headline() }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Minimum rating</x-ui.label>
                <x-ui.combobox
                    wire:model.live="minimumRating"
                    class="w-full"
                    size="sm"
                    placeholder="Any score"
                    clearable
                >
                    @foreach ($minimumRatings as $ratingFloor)
                        <x-ui.combobox.option
                            wire:key="discover-rating-{{ $ratingFloor }}"
                            value="{{ $ratingFloor }}"
                        >
                            {{ $ratingFloor }}+
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
                    placeholder="Sort titles"
                >
                    @foreach ($sortOptions as $sortOption)
                        <x-ui.combobox.option
                            wire:key="discover-sort-{{ $sortOption['value'] }}"
                            value="{{ $sortOption['value'] }}"
                        >
                            {{ $sortOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-70">
        @forelse ($titles as $title)
            <x-catalog.title-card :title="$title" />
        @empty
            <div class="md:col-span-2 xl:col-span-3">
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.heading level="h3">No titles match the current filters.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        Adjust the query, genre, type, or rating floor to widen discovery.
                    </x-ui.text>
                </x-ui.empty>
            </div>
        @endforelse
    </div>

    <div>
        {{ $titles->links() }}
    </div>
</div>
