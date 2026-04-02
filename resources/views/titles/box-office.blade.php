@extends('layouts.public')

@section('title', $title->name.' Box Office Report')
@section('meta_description', 'Review opening weekend, lifetime gross, budget, and market coverage for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $title)">{{ $title->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Box Office</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero sb-box-office-hero !max-w-none overflow-hidden p-0" data-slot="title-box-office-hero">
            <div class="sb-metadata-hero-backdrop">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $title->name }}"
                        class="sb-metadata-hero-backdrop-image"
                    >
                @endif
            </div>

            <div class="relative grid gap-6 p-6 sm:p-7 xl:grid-cols-[minmax(0,11rem)_minmax(0,1fr)_minmax(19rem,0.92fr)] xl:items-end">
                <div class="sb-metadata-poster-shell">
                    @if ($poster)
                        <img
                            src="{{ $poster->url }}"
                            alt="{{ $poster->alt_text ?: $title->name }}"
                            class="aspect-[2/3] w-full object-cover"
                            loading="lazy"
                        >
                    @else
                        <div class="sb-metadata-poster-empty">
                            <x-ui.icon name="banknotes" class="size-9" />
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="sb-page-kicker">Commercial Record</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Box Office Report</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl text-base">
                            Opening weekend, lifetime performance, ranked revenue context, and market coverage for {{ $title->name }} in a calmer entertainment-analytics layout.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" color="amber" icon="banknotes">{{ number_format($reportedFigureCount) }} reported figures</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" icon="chart-bar">{{ number_format($rankCards->count()) }} ranked positions</x-ui.badge>
                        <x-ui.badge variant="outline" color="neutral" icon="queue-list">{{ number_format($reportedMarketCount) }} tracked markets</x-ui.badge>
                    </div>
                </div>

                <div class="sb-box-office-hero-panel">
                    <div class="space-y-3">
                        <div>
                            <div class="sb-box-office-panel-kicker">Revenue Spotlight</div>
                            <div class="sb-box-office-panel-copy">
                                Strong headline grosses stay prominent while the supporting comparisons remain quieter and easier to scan.
                            </div>
                        </div>

                        @if ($spotlightMetric)
                            <div class="sb-box-office-spotlight-card">
                                <div class="sb-box-office-spotlight-label">{{ $spotlightMetric['label'] }}</div>
                                <div class="sb-box-office-spotlight-value">{{ $spotlightMetric['value'] }}</div>
                                <div class="sb-box-office-spotlight-copy">{{ $spotlightMetric['copy'] }}</div>
                            </div>
                        @else
                            <div class="sb-box-office-mini-card">
                                <div class="sb-box-office-mini-label">Reporting status</div>
                                <div class="sb-box-office-mini-value">Pending</div>
                                <div class="sb-box-office-mini-copy">Commercial figures will appear here once the title carries box-office payloads.</div>
                            </div>
                        @endif

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            @if ($spotlightRank)
                                <div class="sb-box-office-mini-card">
                                    <div class="sb-box-office-mini-label">{{ $spotlightRank['label'] }} rank</div>
                                    <div class="sb-box-office-mini-value">{{ $spotlightRank['value'] }}</div>
                                    <div class="sb-box-office-mini-copy">{{ $spotlightRank['copy'] }}</div>
                                </div>
                            @elseif ($secondaryMetric)
                                <div class="sb-box-office-mini-card">
                                    <div class="sb-box-office-mini-label">{{ $secondaryMetric['label'] }}</div>
                                    <div class="sb-box-office-mini-value">{{ $secondaryMetric['value'] }}</div>
                                    <div class="sb-box-office-mini-copy">{{ $secondaryMetric['copy'] }}</div>
                                </div>
                            @endif

                            @if ($budgetMultiple)
                                <div class="sb-box-office-mini-card">
                                    <div class="sb-box-office-mini-label">{{ $budgetMultiple['label'] }}</div>
                                    <div class="sb-box-office-mini-value">{{ $budgetMultiple['value'] }}</div>
                                    <div class="sb-box-office-mini-copy">{{ $budgetMultiple['copy'] }}</div>
                                </div>
                            @elseif (! $spotlightMetric)
                                <div class="sb-box-office-mini-card">
                                    <div class="sb-box-office-mini-label">Comparison blocks</div>
                                    <div class="sb-box-office-mini-value">Waiting</div>
                                    <div class="sb-box-office-mini-copy">Comparisons activate automatically when compatible budget and gross records exist.</div>
                                </div>
                            @endif
                        </div>

                        <x-ui.link :href="route('public.titles.show', $title)" variant="ghost" iconAfter="arrow-right">
                            Back to title page
                        </x-ui.link>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.08fr)_minmax(20rem,0.92fr)]">
            <x-ui.card class="sb-detail-section sb-box-office-shell !max-w-none p-5 sm:p-6" data-slot="title-box-office-metrics">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Revenue Dashboard</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Primary theatrical figures kept high-contrast and vertical for fast scanability.
                            </x-ui.text>
                        </div>

                        <x-ui.badge variant="outline" color="neutral" icon="banknotes">
                            {{ number_format($reportedFigureCount) }} reported figures
                        </x-ui.badge>
                    </div>

                    @if ($summaryCards->isNotEmpty())
                        <div class="sb-box-office-metric-grid">
                            @foreach ($summaryCards as $summaryCard)
                                <article class="sb-box-office-metric-card">
                                    <div class="sb-box-office-metric-label">{{ $summaryCard['label'] }}</div>
                                    <div class="sb-box-office-metric-value">{{ $summaryCard['value'] }}</div>
                                    <div class="sb-box-office-metric-copy">{{ $summaryCard['copy'] }}</div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="banknotes" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Commercial reporting is not attached yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                This page is ready for opening weekend, lifetime, and budget imports as soon as the title carries structured box-office data.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="sb-detail-section sb-box-office-side-shell !max-w-none p-5 sm:p-6" data-slot="title-box-office-ranks">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Ranked Positions</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Rank is computed against tracked titles sharing the same gross currency for that metric.
                            </x-ui.text>
                        </div>

                        @if ($rankCards->isNotEmpty())
                            <div class="space-y-3">
                                @foreach ($rankCards as $rankCard)
                                    <article class="sb-box-office-rank-card">
                                        <div>
                                            <div class="sb-box-office-rank-label">{{ $rankCard['label'] }}</div>
                                            <div class="sb-box-office-rank-copy">{{ $rankCard['copy'] }}</div>
                                        </div>

                                        <div class="sb-box-office-rank-value">{{ $rankCard['value'] }}</div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                Ranked positions will appear once this title shares comparable tracked gross figures with the wider catalog.
                            </x-ui.text>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card class="sb-detail-section sb-box-office-side-shell !max-w-none p-5 sm:p-6" data-slot="title-box-office-comparisons">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Comparison Blocks</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Derived comparisons stay quieter so the revenue figures remain the main visual anchor.
                            </x-ui.text>
                        </div>

                        @if ($comparisonCards->isNotEmpty())
                            <div class="sb-box-office-comparison-grid">
                                @foreach ($comparisonCards as $comparisonCard)
                                    <article class="sb-box-office-comparison-card">
                                        <div class="sb-box-office-comparison-label">{{ $comparisonCard['label'] }}</div>
                                        <div class="sb-box-office-comparison-value">{{ $comparisonCard['value'] }}</div>
                                        <div class="sb-box-office-comparison-copy">{{ $comparisonCard['copy'] }}</div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                Comparison blocks activate when the feed includes compatible gross and budget figures for this title.
                            </x-ui.text>
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-ui.card class="sb-detail-section sb-box-office-shell !max-w-none p-5 sm:p-6" data-slot="title-box-office-markets">
            <div class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Market Breakdown</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            The current import feed carries theatrical runway coverage by market, giving the page a clearer regional footprint even when per-market grosses are absent.
                        </x-ui.text>
                    </div>

                    <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                        {{ number_format($reportedMarketCount) }} markets
                    </x-ui.badge>
                </div>

                @if ($marketRows->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($marketRows as $marketRow)
                            <article class="sb-box-office-market-row">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="sb-box-office-market-label">{{ $marketRow['market'] }}</div>
                                        <div class="sb-box-office-market-copy">{{ $marketRow['copy'] }}</div>
                                    </div>

                                    @if ($marketRow['weeksLabel'])
                                        <x-ui.badge variant="outline" color="slate" icon="queue-list">
                                            {{ $marketRow['weeksLabel'] }}
                                        </x-ui.badge>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="queue-list" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">Market coverage has not been attached yet.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Regional runway notes will appear here once the imported box-office payload includes theatrical market coverage.
                        </x-ui.text>
                    </x-ui.empty>
                @endif
            </div>
        </x-ui.card>
    </section>
@endsection
