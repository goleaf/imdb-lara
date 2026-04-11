@extends('layouts.public')

@section('title', 'Interest Categories')
@section('meta_description', 'Browse interest-category lanes, grouped interests, and linked titles from the imported MySQL catalog.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Interest Categories</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero !max-w-none p-6 sm:p-7" data-slot="interest-category-directory-hero">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                <div class="space-y-4">
                    <div class="sb-page-kicker">Metadata graph</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Interest Categories</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl">
                            Browse the imported interest-category taxonomy that groups discovery interests into reusable catalog lanes and connects them back to visible title pages.
                        </x-ui.text>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[22rem]">
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Grouping model</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">Catalog clusters</div>
                    </div>
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Navigation</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">Theme-first</div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5" data-slot="interest-category-directory-snapshot">
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <x-ui.heading level="h2" size="lg">Catalog clusters</x-ui.heading>
                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                            A quick read on how the imported interest graph is currently grouped inside the remote MySQL catalog.
                        </x-ui.text>
                    </div>

                    <x-ui.badge color="amber" icon="squares-2x2">
                        {{ number_format($directorySnapshot['categoryCount']) }} categories
                    </x-ui.badge>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Categories</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($directorySnapshot['categoryCount']) }}</div>
                    </div>
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Interests</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($directorySnapshot['interestCount']) }}</div>
                    </div>
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Title-linked interests</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($directorySnapshot['titleLinkedInterestCount']) }}</div>
                    </div>
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Subgenres</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($directorySnapshot['subgenreInterestCount']) }}</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Top category lanes</div>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($directorySnapshot['topCategories'] as $topCategory)
                            <a href="{{ $topCategory['href'] }}">
                                <x-ui.badge variant="outline" color="neutral" icon="sparkles">
                                    {{ $topCategory['name'] }} · {{ number_format($topCategory['titleLinkedInterestsCount']) }}
                                </x-ui.badge>
                            </a>
                        @empty
                            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                Interest-category rows are not available in the imported sample yet.
                            </x-ui.text>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="outline" icon="sparkles">Discovery lanes</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral" icon="tag">Subgenre groupings</x-ui.badge>
                    <x-ui.badge variant="outline" color="slate" icon="film">Linked title clusters</x-ui.badge>
                </div>
            </div>
        </x-ui.card>

        <livewire:catalog.interest-category-browser />
    </section>
@endsection
