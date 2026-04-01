<div class="space-y-4">
    <x-ui.card class="!max-w-none">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_repeat(4,minmax(0,0.5fr))]">
            <x-ui.field>
                <x-ui.label>Keyword</x-ui.label>
                <x-ui.input
                    wire:model.live.debounce.300ms="search"
                    name="search"
                    placeholder="Search titles, synonyms, or plot notes"
                    left-icon="magnifying-glass"
                />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Genre</x-ui.label>
                <select
                    wire:model.live="genre"
                    class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                >
                    <option value="">All genres</option>
                    @foreach ($genres as $genreOption)
                        <option value="{{ $genreOption->slug }}">{{ $genreOption->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Type</x-ui.label>
                <select
                    wire:model.live="type"
                    class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                >
                    <option value="">All types</option>
                    @foreach ($titleTypes as $typeOption)
                        <option value="{{ $typeOption->value }}">{{ str($typeOption->value)->headline() }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Minimum rating</x-ui.label>
                <select
                    wire:model.live="minimumRating"
                    class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                >
                    <option value="">Any score</option>
                    @foreach (range(10, 1) as $ratingFloor)
                        <option value="{{ $ratingFloor }}">{{ $ratingFloor }}+</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Sort</x-ui.label>
                <select
                    wire:model.live="sort"
                    class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                >
                    <option value="popular">Popularity</option>
                    <option value="rating">Rating</option>
                    <option value="year">Year</option>
                    <option value="name">Name</option>
                </select>
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
