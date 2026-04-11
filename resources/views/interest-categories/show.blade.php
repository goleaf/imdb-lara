@extends('layouts.public')

@section('title', $interestCategory->name)
@section('meta_description', 'Browse interests and linked titles grouped under '.$interestCategory->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.interest-categories.index')">Interest Categories</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $interestCategory->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero !max-w-none p-6 sm:p-7" data-slot="interest-category-detail-hero">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                <div class="space-y-4">
                    <div class="sb-page-kicker">Interest category</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">{{ $interestCategory->name }}</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl">
                            Category overview for the imported discovery graph, showing the linked interests that feed into this lane and the public titles surfaced from those tags.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" icon="squares-2x2">{{ $interestCategory->interestCountBadgeLabel() }}</x-ui.badge>
                        <x-ui.badge variant="outline" color="neutral" icon="film">{{ number_format($linkedTitleCount) }} linked titles</x-ui.badge>
                        @if ($interestCategory->subgenreInterestCount() > 0)
                            <x-ui.badge variant="outline" color="slate" icon="tag">{{ $interestCategory->subgenreInterestCountBadgeLabel() }}</x-ui.badge>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 xl:justify-end">
                    <x-ui.button as="a" :href="route('public.interest-categories.index')" variant="outline" icon="squares-2x2">
                        Browse all categories
                    </x-ui.button>
                    <x-ui.button as="a" :href="route('public.titles.index', ['theme' => $interestCategory->slug])" variant="outline" icon="film">
                        Browse titles
                    </x-ui.button>
                    <x-ui.button as="a" :href="route('public.discover', ['theme' => $interestCategory->slug])" variant="ghost" icon="sparkles">
                        Open discovery
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <div
            class="grid gap-6 lg:grid-cols-[minmax(0,0.82fr)_minmax(0,1.18fr)] lg:items-start"
            data-slot="interest-category-summary-panels"
        >
            <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
                <div class="space-y-4">
                    <div class="space-y-1">
                        <x-ui.heading level="h2" size="lg">Category overview</x-ui.heading>
                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                            These interests currently map into the <strong>{{ $interestCategory->name }}</strong> lane and can be used to pivot back into title search.
                        </x-ui.text>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="sb-page-stat p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Interests</div>
                            <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($interestCategory->interestCount()) }}</div>
                        </div>
                        <div class="sb-page-stat p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Title-linked interests</div>
                            <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($interestCategory->titleLinkedInterestCount()) }}</div>
                        </div>
                        <div class="sb-page-stat p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Linked titles</div>
                            <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($linkedTitleCount) }}</div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
                <div class="space-y-4">
                    <div class="space-y-1">
                        <x-ui.heading level="h2" size="lg">Related interests</x-ui.heading>
                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                            The strongest interest rows attached to this category, ordered by imported lane position and enriched with linked-title counts.
                        </x-ui.text>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @forelse ($relatedInterests as $relatedInterest)
                            <a href="{{ $relatedInterest['href'] }}" class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge variant="outline" icon="sparkles">{{ $relatedInterest['interest']->name }}</x-ui.badge>
                                    @if ($relatedInterest['interest']->is_subgenre)
                                        <x-ui.badge variant="outline" color="slate" icon="tag">Subgenre</x-ui.badge>
                                    @endif
                                </div>

                                <div class="mt-3 space-y-2">
                                    <x-ui.text class="text-sm font-medium text-neutral-800 dark:text-neutral-100">
                                        {{ $relatedInterest['titleCountLabel'] }}
                                    </x-ui.text>

                                    @if ($relatedInterest['description'])
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ $relatedInterest['description'] }}
                                        </x-ui.text>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900 sm:col-span-2">
                                <x-ui.empty.media>
                                    <x-ui.icon name="sparkles" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No interests are linked to this category yet.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    The imported sample does not currently expose interest rows for this lane.
                                </x-ui.text>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>
        </div>

        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <x-ui.heading level="h2" size="lg">Linked titles</x-ui.heading>
                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                            Public title pages currently connected to the interests inside this category.
                        </x-ui.text>
                    </div>

                    <x-ui.badge color="amber" icon="film">{{ number_format($linkedTitleCount) }} visible titles</x-ui.badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($linkedTitles as $linkedTitle)
                        <x-catalog.title-card :title="$linkedTitle" :show-summary="false">
                            <x-ui.badge variant="outline" color="neutral" icon="sparkles">
                                {{ number_format((int) ($linkedTitle->matched_interest_count ?? 0)) }} matched interests
                            </x-ui.badge>
                        </x-catalog.title-card>
                    @empty
                        <div class="sm:col-span-2 xl:col-span-3">
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                                <x-ui.empty.media>
                                    <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No public titles are linked to this category yet.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    The imported sample currently exposes the interest taxonomy, but no visible title pages map back into this lane.
                                </x-ui.text>
                            </x-ui.empty>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui.card>
    </section>
@endsection
