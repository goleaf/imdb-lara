@extends('layouts.public')

@section('title', 'Browse Public Lists')
@section('meta_description', 'Browse public member-curated Screenbase lists with curator profiles, title previews, and shareable collection pages.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Public Lists</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-5" data-slot="public-lists-shell">
        <x-seo.pagination-links :paginator="$lists" />

        <x-ui.card class="!max-w-none" data-slot="public-lists-hero">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(22rem,0.95fr)] xl:items-end">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                            {{ number_format($lists->total()) }} public lists
                        </x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" icon="eye">
                            Shareable member curation
                        </x-ui.badge>
                    </div>

                    <div class="space-y-2">
                        <x-ui.heading level="h1" size="xl">Browse Public Lists</x-ui.heading>
                        <x-ui.text class="max-w-3xl text-neutral-600 dark:text-neutral-300">
                            Explore public custom lists from the Screenbase community. Open themed picks, franchise runs, deep cuts, and curator-driven queues without leaving the catalog.
                        </x-ui.text>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                            <x-ui.icon name="sparkles" class="size-4" />
                            <span>Browse mode</span>
                        </div>
                        <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                            Lists are ordered by recent updates, size, or title, and every card links straight into the underlying title pages.
                        </x-ui.text>
                    </div>

                    <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                            <x-ui.icon name="user" class="size-4" />
                            <span>Visibility</span>
                        </div>
                        <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                            This page only shows public custom lists. Private and unlisted collections stay off this index.
                        </x-ui.text>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <x-ui.field>
                    <x-ui.label>Search public lists</x-ui.label>
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        name="q"
                        placeholder="Search list names, descriptions, or curators"
                        left-icon="magnifying-glass"
                    />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Sort</x-ui.label>
                    <x-ui.combobox wire:model.live="sort" class="w-full" placeholder="Choose order">
                        @foreach ($sortOptions as $sortOption)
                            <x-ui.combobox.option
                                wire:key="public-lists-sort-{{ $sortOption['value'] }}"
                                value="{{ $sortOption['value'] }}"
                                :icon="$sortOption['icon']"
                            >
                                {{ $sortOption['label'] }}
                            </x-ui.combobox.option>
                        @endforeach
                    </x-ui.combobox>
                </x-ui.field>
            </div>
        </x-ui.card>

        <div wire:loading.delay.attr="data-loading" wire:target="search,sort" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 not-data-loading:hidden">
                @foreach (range(1, 6) as $index)
                    <x-ui.card class="!max-w-none h-full" wire:key="public-list-placeholder-{{ $index }}">
                        <div class="space-y-4">
                            <div class="flex gap-2">
                                <x-ui.skeleton.text class="w-24" />
                                <x-ui.skeleton.text class="w-24" />
                            </div>
                            <x-ui.skeleton.text class="w-2/3" />
                            <x-ui.skeleton.text class="w-full" />
                            <div class="grid grid-cols-3 gap-2">
                                <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                                <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                                <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="space-y-4 in-data-loading:hidden" data-slot="public-lists-grid">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        @if (filled(trim($search)))
                            Showing {{ number_format($lists->total()) }} matching public lists for <span class="font-medium text-neutral-900 dark:text-neutral-100">“{{ $search }}”</span>.
                        @else
                            Discover curated public lists from across the catalog.
                        @endif
                    </x-ui.text>

                    @if (filled(trim($search)))
                        <x-ui.button type="button" variant="ghost" size="sm" wire:click="$set('search', '')" icon="x-mark">
                            Clear search
                        </x-ui.button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($lists as $list)
                        <x-ui.card class="!max-w-none h-full" wire:key="public-list-card-{{ $list->id }}">
                            <div class="flex h-full flex-col gap-4">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('public.users.show', $list->user) }}">
                                            <x-ui.badge variant="outline" color="neutral" icon="user">{{ $list->user->name }}</x-ui.badge>
                                        </a>
                                        <x-ui.badge variant="outline" color="slate" icon="queue-list">
                                            {{ number_format((int) $list->published_items_count) }} titles
                                        </x-ui.badge>
                                        <x-ui.badge variant="outline" color="neutral" icon="clock">
                                            {{ $list->updated_at?->diffForHumans() }}
                                        </x-ui.badge>
                                    </div>

                                    <div class="space-y-2">
                                        <x-ui.heading level="h2" size="md">
                                            <a href="{{ route('public.lists.show', [$list->user, $list]) }}" class="hover:opacity-80">
                                                {{ $list->name }}
                                            </a>
                                        </x-ui.heading>
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ $list->description ?: 'A public Screenbase list curated around a shared viewing idea.' }}
                                        </x-ui.text>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2">
                                    @forelse ($list->previewItems() as $item)
                                        <a
                                            href="{{ route('public.titles.show', $item->title) }}"
                                            class="group overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800"
                                        >
                                            @if ($item->title->preferredPoster())
                                                <img
                                                    src="{{ $item->title->preferredPoster()->url }}"
                                                    alt="{{ $item->title->preferredPoster()->alt_text ?: $item->title->name }}"
                                                    class="aspect-[2/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                                    <x-ui.icon name="film" class="size-8" />
                                                </div>
                                            @endif
                                        </a>
                                    @empty
                                        @foreach (range(1, 3) as $index)
                                            <div class="flex aspect-[2/3] items-center justify-center rounded-box border border-dashed border-black/10 bg-neutral-50 text-neutral-400 dark:border-white/10 dark:bg-neutral-800/70 dark:text-neutral-500">
                                                <x-ui.icon name="film" class="size-8" />
                                            </div>
                                        @endforeach
                                    @endforelse
                                </div>

                                @if ($list->previewItems()->isNotEmpty())
                                    <div class="flex flex-wrap gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                        @foreach ($list->previewItems() as $item)
                                            <span>{{ $item->title->name }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-auto flex items-center justify-between gap-3">
                                    <x-ui.badge variant="outline" color="neutral" icon="at-symbol">{{ '@'.$list->user->username }}</x-ui.badge>
                                    <x-ui.link :href="route('public.lists.show', [$list->user, $list])" variant="ghost" iconAfter="arrow-right">
                                        View list
                                    </x-ui.link>
                                </div>
                            </div>
                        </x-ui.card>
                    @empty
                        <div class="md:col-span-2 xl:col-span-3">
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                                <x-ui.empty.media>
                                    <x-ui.icon name="queue-list" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No public lists match that search.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    Try a broader list theme, a different curator name, or clear the search to return to the latest public curation.
                                </x-ui.text>
                            </x-ui.empty>
                        </div>
                    @endforelse
                </div>

                <div>
                    {{ $lists->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
