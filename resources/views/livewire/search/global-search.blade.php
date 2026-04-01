@php
    $hasSuggestions = $suggestions['titles']->isNotEmpty()
        || $suggestions['people']->isNotEmpty()
        || $suggestions['lists']->isNotEmpty();
@endphp

<div
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    class="relative w-full"
>
    <form wire:submit="submitSearch">
        <x-ui.input
            wire:model.live.debounce.250ms="query"
            name="global_search"
            placeholder="Search titles, people, and public lists"
            left-icon="magnifying-glass"
            clearable
            x-on:focus="open = true"
            x-on:click="open = true"
        />
    </form>

    @if ($hasSearchTerm)
        <div
            x-cloak
            x-show="open"
            x-on:click.outside="open = false"
            class="absolute left-0 right-0 top-full z-50 mt-2"
        >
            <x-ui.card class="!max-w-none">
                <div wire:loading.delay wire:target="query" class="space-y-2">
                    @foreach (range(1, 4) as $index)
                        <x-ui.skeleton.text class="w-full" wire:key="global-search-skeleton-{{ $index }}" />
                    @endforeach
                </div>

                <div wire:loading.remove wire:target="query" class="space-y-4">
                    @if ($hasSuggestions)
                        @if ($suggestions['titles']->isNotEmpty())
                            <div class="space-y-2">
                                <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Titles</div>
                                <div class="space-y-1">
                                    @foreach ($suggestions['titles'] as $titleSuggestion)
                                        <a
                                            href="{{ route('public.titles.show', $titleSuggestion) }}"
                                            class="flex items-center justify-between gap-3 rounded-box px-3 py-2 text-sm transition hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        >
                                            <div class="min-w-0">
                                                <div class="truncate font-medium text-neutral-900 dark:text-neutral-100">
                                                    {{ $titleSuggestion->name }}
                                                </div>
                                                <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                    {{ str($titleSuggestion->title_type->value)->headline() }}
                                                    @if ($titleSuggestion->release_year)
                                                        · {{ $titleSuggestion->release_year }}
                                                    @endif
                                                </div>
                                            </div>
                                            <x-ui.icon name="arrow-right" class="size-4 text-neutral-400" />
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($suggestions['people']->isNotEmpty())
                            <div class="space-y-2">
                                <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">People</div>
                                <div class="space-y-1">
                                    @foreach ($suggestions['people'] as $personSuggestion)
                                        <a
                                            href="{{ route('public.people.show', $personSuggestion) }}"
                                            class="flex items-center justify-between gap-3 rounded-box px-3 py-2 text-sm transition hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        >
                                            <div class="min-w-0">
                                                <div class="truncate font-medium text-neutral-900 dark:text-neutral-100">
                                                    {{ $personSuggestion->name }}
                                                </div>
                                                <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                    {{ $personSuggestion->known_for_department ?: 'Screenbase person profile' }}
                                                </div>
                                            </div>
                                            <x-ui.icon name="arrow-right" class="size-4 text-neutral-400" />
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($suggestions['lists']->isNotEmpty())
                            <div class="space-y-2">
                                <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Lists</div>
                                <div class="space-y-1">
                                    @foreach ($suggestions['lists'] as $listSuggestion)
                                        <a
                                            href="{{ route('public.lists.show', [$listSuggestion->user, $listSuggestion]) }}"
                                            class="flex items-center justify-between gap-3 rounded-box px-3 py-2 text-sm transition hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        >
                                            <div class="min-w-0">
                                                <div class="truncate font-medium text-neutral-900 dark:text-neutral-100">
                                                    {{ $listSuggestion->name }}
                                                </div>
                                                <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                    {{ '@'.$listSuggestion->user->username }} · {{ number_format($listSuggestion->items_count) }} titles
                                                </div>
                                            </div>
                                            <x-ui.icon name="arrow-right" class="size-4 text-neutral-400" />
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.heading level="h3">No quick matches yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                Open the full search page for broader filtering and grouped results.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif

                    <div class="border-t border-black/5 pt-3 dark:border-white/10">
                        <x-ui.link :href="route('public.search', ['q' => trim($query)])" variant="ghost" iconAfter="arrow-right">
                            View all results for "{{ trim($query) }}"
                        </x-ui.link>
                    </div>
                </div>
            </x-ui.card>
        </div>
    @endif
</div>
