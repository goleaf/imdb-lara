<div
    data-slot="global-search"
    x-data="{
        open: false,
        recent: [],
        storageKey: 'screenbase.recent-searches',
        init() {
            try {
                const storedValue = JSON.parse(localStorage.getItem(this.storageKey) ?? '[]');
                this.recent = Array.isArray(storedValue) ? storedValue.slice(0, 5) : [];
            } catch (error) {
                this.recent = [];
            }
        },
        persistRecent() {
            localStorage.setItem(this.storageKey, JSON.stringify(this.recent.slice(0, 5)));
        },
        storeRecent(value) {
            const query = (value || '').trim();

            if (query.length < 2) {
                return;
            }

            this.recent = [query, ...this.recent.filter((item) => item.toLowerCase() !== query.toLowerCase())].slice(0, 5);
            this.persistRecent();
        },
        removeRecent(value) {
            this.recent = this.recent.filter((item) => item !== value);
            this.persistRecent();
        },
        goToSearch(value) {
            const query = (value || '').trim();

            if (query.length === 0) {
                window.location = @js($searchRoute);

                return;
            }

            this.storeRecent(query);
            window.location = `${@js($searchRoute)}?q=${encodeURIComponent(query)}`;
        },
    }"
    x-on:keydown.escape.window="open = false"
    class="relative w-full"
>
    <form wire:submit="submitSearch" x-on:submit="storeRecent($wire.query)">
        <x-ui.input
            wire:model.live.debounce.180ms="query"
            name="global_search"
            placeholder="Search titles, people, and lists"
            left-icon="magnifying-glass"
            clearable
            kbd="/"
            class="sb-shell-search"
            x-on:focus="open = true"
            x-on:click="open = true"
            x-on:input="open = true"
        />
    </form>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.duration.150ms
        x-on:click.outside="open = false"
        class="absolute left-0 right-0 top-full z-50 mt-3"
    >
        <div class="sb-search-overlay rounded-[1.6rem] p-4 sm:p-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <div class="sb-auth-kicker">Global Search</div>
                    <div class="sb-search-group-title">Find titles, people, and lists fast</div>
                    <div class="sb-search-group-copy">
                        Live grouped suggestions with posters, portraits, and curated list previews.
                    </div>
                </div>

                <button
                    type="button"
                    x-on:click="open = false"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/8 bg-white/[0.03] text-[#c0b5a5] transition hover:bg-white/[0.06] hover:text-[#f4eee5]"
                    aria-label="Close search"
                >
                    <x-ui.icon name="x-mark" class="size-4" />
                </button>
            </div>

            <div wire:loading.delay wire:target="query" class="grid gap-3 xl:grid-cols-3">
                @foreach (['titles', 'people', 'lists'] as $group)
                    <div class="sb-search-panel rounded-[1.35rem] p-4 {{ $group === 'titles' ? 'sb-search-panel--titles' : ($group === 'people' ? 'sb-search-panel--people' : 'sb-search-panel--lists') }}">
                        <div class="space-y-3">
                            <x-ui.skeleton.text class="w-1/3" />
                            @foreach (range(1, 3) as $index)
                                <div class="flex items-center gap-3" wire:key="global-search-skeleton-{{ $group }}-{{ $index }}">
                                    <x-ui.skeleton class="{{ $group === 'titles' ? 'h-20 w-14 rounded-[1rem]' : ($group === 'people' ? 'h-16 w-16 rounded-[1rem]' : 'h-16 w-12 rounded-[1rem]') }}" />
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <x-ui.skeleton.text class="w-2/3" />
                                        <x-ui.skeleton.text class="w-1/2" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div wire:loading.remove wire:target="query" class="space-y-4">
                @if ($hasSearchTerm)
                    @if ($hasSuggestions)
                        <div class="grid gap-3 xl:grid-cols-3">
                            @if ($suggestions['titles']->isNotEmpty())
                                <section class="sb-search-panel sb-search-panel--titles rounded-[1.35rem] p-4">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div>
                                            <div class="sb-search-group-title inline-flex items-center gap-2">
                                                <x-ui.icon name="film" class="size-4 text-[#d6b574]" />
                                                <span>Titles</span>
                                            </div>
                                            <div class="sb-search-group-copy">Poster-led title matches with year and type.</div>
                                        </div>
                                        <span class="sb-search-chip sb-search-chip--accent">
                                            {{ $suggestions['titles']->count() }} shown
                                        </span>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach ($suggestions['titles'] as $titleSuggestion)
                                            <a
                                                href="{{ route('public.titles.show', $titleSuggestion) }}"
                                                x-on:click="storeRecent(@js($trimmedQuery))"
                                                class="sb-search-item sb-search-item--title flex items-center gap-3 border border-white/6 bg-white/[0.025] p-2.5 hover:bg-white/[0.055]"
                                            >
                                                <div class="sb-search-item-media sb-search-item-media--title h-20 w-14 shrink-0 overflow-hidden rounded-[1rem]">
                                                    @if ($titleSuggestion->preferredPoster())
                                                        <img
                                                            src="{{ $titleSuggestion->preferredPoster()->url }}"
                                                            alt="{{ $titleSuggestion->preferredPoster()->alt_text ?: $titleSuggestion->name }}"
                                                            class="h-full w-full object-cover"
                                                            loading="lazy"
                                                        >
                                                    @else
                                                        <div class="flex h-full w-full items-center justify-center bg-white/[0.03] text-[#8f877a]">
                                                            <x-ui.icon name="film" class="size-5" />
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="min-w-0 flex-1 space-y-1">
                                                    <div class="truncate text-sm font-semibold tracking-[-0.01em] text-[#f4eee5]">
                                                        {{ $titleSuggestion->name }}
                                                    </div>
                                                    <div class="sb-search-meta">
                                                        <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                                            {{ $titleSuggestion->typeLabel() }}
                                                        </span>
                                                        @if ($titleSuggestion->release_year)
                                                            <span class="sb-search-chip sb-search-chip--tight">
                                                                {{ $titleSuggestion->release_year }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-[#9e9384]" />
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            @if ($suggestions['people']->isNotEmpty())
                                <section class="sb-search-panel sb-search-panel--people rounded-[1.35rem] p-4">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div>
                                            <div class="sb-search-group-title inline-flex items-center gap-2">
                                                <x-ui.icon name="user" class="size-4 text-[#a8bfd7]" />
                                                <span>People</span>
                                            </div>
                                            <div class="sb-search-group-copy">Portrait-first profiles with profession cues.</div>
                                        </div>
                                        <span class="sb-search-chip sb-search-chip--people">
                                            {{ $suggestions['people']->count() }} shown
                                        </span>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach ($suggestions['people'] as $personSuggestion)
                                            <a
                                                href="{{ route('public.people.show', $personSuggestion) }}"
                                                x-on:click="storeRecent(@js($trimmedQuery))"
                                                class="sb-search-item sb-search-item--person flex items-center gap-3 border border-white/6 bg-white/[0.025] p-2.5 hover:bg-white/[0.055]"
                                            >
                                                <div class="sb-search-item-media sb-search-item-media--person h-16 w-16 shrink-0 overflow-hidden rounded-[1rem]">
                                                    @if ($personSuggestion->preferredHeadshot())
                                                        <img
                                                            src="{{ $personSuggestion->preferredHeadshot()->url }}"
                                                            alt="{{ $personSuggestion->preferredHeadshot()->alt_text ?: $personSuggestion->name }}"
                                                            class="h-full w-full object-cover"
                                                            loading="lazy"
                                                        >
                                                    @else
                                                        <div class="flex h-full w-full items-center justify-center bg-white/[0.03] text-[#8f877a]">
                                                            <x-ui.icon name="user" class="size-5" />
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="min-w-0 flex-1 space-y-1">
                                                    <div class="truncate text-sm font-semibold tracking-[-0.01em] text-[#f4eee5]">
                                                        {{ $personSuggestion->name }}
                                                    </div>
                                                    <div class="sb-search-meta">
                                                        <span class="sb-search-chip sb-search-chip--people sb-search-chip--tight">
                                                            {{ $personSuggestion->primaryProfessionLabel() }}
                                                        </span>
                                                        @if ($personSuggestion->secondaryProfessionLabel() !== '')
                                                            <span class="sb-search-meta-copy truncate">
                                                                {{ $personSuggestion->secondaryProfessionLabel() }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-[#9e9384]" />
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            @if ($suggestions['lists']->isNotEmpty())
                                <section class="sb-search-panel sb-search-panel--lists rounded-[1.35rem] p-4">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div>
                                            <div class="sb-search-group-title inline-flex items-center gap-2">
                                                <x-ui.icon name="queue-list" class="size-4 text-[#c7b7d8]" />
                                                <span>Lists</span>
                                            </div>
                                            <div class="sb-search-group-copy">Public curated lists with owner and title counts.</div>
                                        </div>
                                        <span class="sb-search-chip sb-search-chip--lists">
                                            {{ $suggestions['lists']->count() }} shown
                                        </span>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach ($suggestions['lists'] as $listSuggestion)
                                            <a
                                                href="{{ route('public.lists.show', [$listSuggestion->user, $listSuggestion]) }}"
                                                x-on:click="storeRecent(@js($trimmedQuery))"
                                                class="sb-search-item sb-search-item--list flex items-center gap-3 border border-white/6 bg-white/[0.025] p-2.5 hover:bg-white/[0.055]"
                                            >
                                                <div class="sb-search-item-media sb-search-item-media--list h-16 w-12 shrink-0 overflow-hidden rounded-[1rem]">
                                                    @if ($listSuggestion->previewPoster())
                                                        <img
                                                            src="{{ $listSuggestion->previewPoster()->url }}"
                                                            alt="{{ $listSuggestion->previewPoster()->alt_text ?: $listSuggestion->previewTitle()?->name ?: $listSuggestion->name }}"
                                                            class="h-full w-full object-cover"
                                                            loading="lazy"
                                                        >
                                                    @else
                                                        <div class="flex h-full w-full items-center justify-center bg-white/[0.03] text-[#8f877a]">
                                                            <x-ui.icon name="queue-list" class="size-5" />
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="min-w-0 flex-1 space-y-1">
                                                    <div class="truncate text-sm font-semibold tracking-[-0.01em] text-[#f4eee5]">
                                                        {{ $listSuggestion->name }}
                                                    </div>
                                                    <div class="sb-search-meta">
                                                        <span class="sb-search-chip sb-search-chip--lists sb-search-chip--tight">
                                                            {{ number_format($listSuggestion->published_items_count) }} titles
                                                        </span>
                                                        <span class="sb-search-meta-copy truncate">
                                                            {{ '@'.$listSuggestion->user->username }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-[#9e9384]" />
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        </div>
                    @else
                        <div class="sb-search-panel rounded-[1.35rem] border border-dashed border-white/10 bg-white/[0.02] p-6 text-center">
                            <div class="space-y-2">
                                <div class="sb-search-group-title">No quick matches</div>
                                <div class="sb-search-group-copy mx-auto max-w-md">
                                    Try a broader title, actor, or creator keyword. The full search page will still let you widen the query and filter further.
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="border-t border-white/8 pt-4">
                        <button
                            type="button"
                            x-on:click="goToSearch(@js($trimmedQuery))"
                            class="inline-flex items-center gap-2 text-sm font-medium text-[#dcc38d] transition hover:text-[#f5ddaa]"
                        >
                            <span>View all results for "{{ $trimmedQuery }}"</span>
                            <x-ui.icon name="arrow-right" class="size-4" />
                        </button>
                    </div>
                @else
                    <section class="sb-search-panel rounded-[1.35rem] p-4">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <div class="sb-search-group-title">Recent Searches</div>
                                <div class="sb-search-group-copy">Jump back into titles, people, and lists you looked up recently on this device.</div>
                            </div>
                            <span class="sb-search-chip" x-show="recent.length > 0" x-text="`${recent.length} saved`"></span>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2" x-show="recent.length > 0">
                            <template x-for="item in recent" :key="item">
                                <div class="sb-search-item flex items-center justify-between gap-3 border border-white/6 bg-white/[0.025] px-3 py-3">
                                    <button
                                        type="button"
                                        class="min-w-0 flex-1 truncate text-left text-sm font-medium text-[#f4eee5]"
                                        x-on:click="goToSearch(item)"
                                        x-text="item"
                                    ></button>

                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/8 bg-white/[0.03] text-[#9e9384] transition hover:bg-white/[0.06] hover:text-[#f4eee5]"
                                        x-on:click="removeRecent(item)"
                                        aria-label="Remove recent search"
                                    >
                                        <x-ui.icon name="x-mark" class="size-4" />
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div x-show="recent.length === 0" class="rounded-[1.1rem] border border-dashed border-white/10 bg-white/[0.02] px-4 py-5 text-center">
                            <div class="sb-search-group-copy mx-auto max-w-md">
                                Start typing a title or person and Screenbase will remember your latest searches on this device.
                            </div>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
