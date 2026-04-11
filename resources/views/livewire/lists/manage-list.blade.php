<div>
@island(name: 'manage-list-panel', defer: true)
    @placeholder
        @include('livewire.placeholders.manage-list')
    @endplaceholder

<div class="space-y-4" data-slot="manage-list-island">
    <x-ui.card class="!max-w-none">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <div class="space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <x-ui.heading level="h1" size="xl">{{ $list->name }}</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                            Refine the list details, tune visibility, and keep the order ready for sharing.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" color="neutral" :icon="$list->visibility->icon()">{{ $list->visibility->label() }}</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" icon="queue-list">{{ number_format($list->items_count) }} titles</x-ui.badge>
                    </div>
                </div>

                @if ($statusMessage)
                    <x-ui.alerts variant="success" icon="check-circle">
                        <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                    </x-ui.alerts>
                @endif
            </div>

            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                <div class="space-y-3">
                    <div>
                        <x-ui.heading level="h2" size="md">Share route</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Public lists appear on your profile. Unlisted lists stay off-profile but keep a direct share URL.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @if ($list->isShareable())
                            <x-ui.link :href="route('public.lists.show', [auth()->user(), $list])" variant="ghost" iconAfter="arrow-right">
                                {{ $list->isPublic() ? 'Open public page' : 'Open unlisted page' }}
                            </x-ui.link>
                        @else
                            <x-ui.badge variant="outline" color="neutral" icon="lock-closed">Private only</x-ui.badge>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <x-ui.card class="!max-w-none">
            <form wire:submit="saveList" class="space-y-4">
                <div class="space-y-2">
                    <x-ui.heading level="h2" size="lg">List details</x-ui.heading>
                    <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                        Keep the title, description, and visibility aligned with how you want to share it.
                    </x-ui.text>
                </div>

                <div class="grid gap-4">
                    <x-ui.field>
                        <x-ui.label>Title</x-ui.label>
                        <x-ui.input wire:model.live="name" name="name" placeholder="Friday Night Picks" />
                        <x-ui.error name="name" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Description</x-ui.label>
                        <x-ui.textarea wire:model.live="description" name="description" rows="4" placeholder="What ties these titles together?" />
                        <x-ui.error name="description" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Visibility</x-ui.label>
                        <x-ui.combobox
                            wire:model.live="visibility"
                            class="w-full"
                            placeholder="Select visibility"
                            :invalid="$errors->has('visibility')"
                        >
                            @foreach ($visibilityOptions as $visibilityOption)
                                <x-ui.combobox.option
                                    wire:key="manage-list-visibility-{{ $visibilityOption['value'] }}"
                                    value="{{ $visibilityOption['value'] }}"
                                >
                                    {{ $visibilityOption['label'] }}
                                </x-ui.combobox.option>
                            @endforeach
                        </x-ui.combobox>
                        <x-ui.error name="visibility" />
                    </x-ui.field>
                </div>

                <div class="flex flex-wrap justify-between gap-3">
                    <x-ui.button type="button" variant="ghost" wire:click="deleteList" icon="trash">
                        Delete list
                    </x-ui.button>

                    <x-ui.button type="submit" icon="check-circle" wire:target="saveList">
                        Save changes
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="space-y-2">
                    <x-ui.heading level="h2" size="lg">Add titles</x-ui.heading>
                    <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                        Search the catalog and attach more published titles without leaving the page.
                    </x-ui.text>
                </div>

                <x-ui.field>
                    <x-ui.label>Find titles</x-ui.label>
                    <x-ui.input
                        wire:model.live.debounce.300ms="titleQuery"
                        name="title_query"
                        placeholder="Search titles to add"
                    />
                </x-ui.field>

                <div wire:loading.delay.attr="data-loading" wire:target="titleQuery" class="space-y-2">
                    <div class="space-y-2 not-data-loading:hidden">
                        @foreach (range(1, 3) as $index)
                            <x-ui.skeleton.text class="w-full" wire:key="list-suggestion-skeleton-{{ $index }}" />
                        @endforeach
                    </div>

                    <div class="space-y-2 in-data-loading:hidden">
                        @if (filled($titleQuery) && $titleSuggestions->isEmpty())
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="magnifying-glass" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No published titles match that search.</x-ui.heading>
                            </x-ui.empty>
                        @endif

                        @foreach ($titleSuggestions as $titleSuggestion)
                            <div
                                wire:key="list-suggestion-{{ $titleSuggestion->id }}"
                                class="flex flex-wrap items-center justify-between gap-3 rounded-box border border-black/5 p-3 dark:border-white/10"
                            >
                                <div>
                                    <div class="font-medium">{{ $titleSuggestion->name }}</div>
                                    <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-ui.icon name="film" class="size-4" />
                                            <span>{{ str($titleSuggestion->title_type->value)->headline() }}</span>
                                        </span>
                                        @if ($titleSuggestion->release_year)
                                            <span>·</span>
                                            <span class="inline-flex items-center gap-1.5">
                                                <x-ui.icon name="calendar-days" class="size-4" />
                                                <span>{{ $titleSuggestion->release_year }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    wire:click="addTitle({{ $titleSuggestion->id }})"
                                    wire:target="addTitle({{ $titleSuggestion->id }})"
                                    icon="plus"
                                >
                                    Add
                                </x-ui.button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="space-y-4 has-data-loading:[&_[data-slot=manage-list-items-skeletons]]:grid has-data-loading:[&_[data-slot=manage-list-items-results]]:hidden">
        <div data-slot="manage-list-items-skeletons" class="hidden gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach (range(1, 6) as $index)
                <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="manage-list-skeleton-{{ $index }}">
                    <div class="space-y-4">
                        <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                        <x-ui.skeleton.text class="w-1/3" />
                        <x-ui.skeleton.text class="w-3/4" />
                        <x-ui.skeleton.text class="w-5/6" />
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="space-y-4" data-slot="manage-list-items-results">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-1">
                    <x-ui.heading level="h2" size="lg">List items</x-ui.heading>
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        Drag the reorder handle to reshuffle this page, or use the move buttons for finer adjustments.
                    </x-ui.text>
                </div>
                <x-ui.badge variant="outline" color="slate" icon="bookmark">{{ number_format($list->items_count) }} saved</x-ui.badge>
            </div>

            <div wire:sort="sortItems" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($items as $item)
                    <div
                        wire:key="manage-list-item-{{ $item->id }}"
                        wire:sort:item="{{ $item->id }}"
                        class="h-full"
                    >
                        <x-catalog.title-card :title="$item->title">
                            <x-ui.button
                                type="button"
                                size="sm"
                                variant="outline"
                                wire:sort:handle
                                icon="bars-3"
                                aria-label="Drag to reorder {{ $item->title->name }}"
                                title="Drag to reorder"
                            >
                                Reorder
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                size="sm"
                                variant="outline"
                                wire:click="moveItemUp({{ $item->id }})"
                                wire:target="moveItemUp({{ $item->id }})"
                                icon="arrow-up"
                            >
                                Move up
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                size="sm"
                                variant="outline"
                                wire:click="moveItemDown({{ $item->id }})"
                                wire:target="moveItemDown({{ $item->id }})"
                                icon="arrow-down"
                            >
                                Move down
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                size="sm"
                                variant="ghost"
                                wire:click="removeTitle({{ $item->title_id }})"
                                wire:target="removeTitle({{ $item->title_id }})"
                                icon="trash"
                            >
                                Remove
                            </x-ui.button>
                        </x-catalog.title-card>
                    </div>
                @empty
                    <div class="md:col-span-2 xl:col-span-3">
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                            <x-ui.empty.media>
                                <x-ui.icon name="queue-list" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">This list is empty.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                Search above to start building the collection.
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
