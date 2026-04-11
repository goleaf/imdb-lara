@extends('layouts.public')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section('breadcrumbs')
    @foreach ($breadcrumbs as $breadcrumb)
        @if (filled($breadcrumb['href'] ?? null))
            <x-ui.breadcrumbs.item :href="$breadcrumb['href']">{{ $breadcrumb['label'] }}</x-ui.breadcrumbs.item>
        @else
            <x-ui.breadcrumbs.item>{{ $breadcrumb['label'] }}</x-ui.breadcrumbs.item>
        @endif
    @endforeach
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero !max-w-none p-6 sm:p-7" data-slot="{{ $heroSlot }}">
            <div class="space-y-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <div class="sb-page-kicker">
                            @if ($isChartPage && $countryLabel)
                                Local Charts
                            @elseif ($isChartPage)
                                Charts
                            @else
                                Browse
                            @endif
                        </div>
                        <div class="space-y-3">
                            <x-ui.heading level="h1" size="xl" class="sb-page-title">{{ $heading }}</x-ui.heading>
                            <x-ui.text class="sb-page-copy max-w-3xl text-base">
                                {{ $description }}
                            </x-ui.text>
                        </div>

                        @if ($isChartPage && $countryLabel)
                            <div class="sb-chart-location-banner" data-slot="chart-location-banner">
                                <div class="flex items-center gap-3">
                                    <x-ui.flag type="country" :code="$countryCode" class="size-6" />
                                    <div class="space-y-1">
                                        <div class="sb-chart-location-kicker">Local lens</div>
                                        <div class="sb-chart-location-title">{{ $countryLabel }}</div>
                                    </div>
                                </div>

                                <div class="sb-chart-location-copy">
                                    This view is filtered to titles from {{ $countryLabel }}, while each row keeps a global comparison cue.
                                </div>
                            </div>
                        @endif

                        @if ($selectedTheme)
                            <div class="sb-chart-location-banner" data-slot="browse-theme-banner">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-12 items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-[#d8bf8b]">
                                        <x-ui.icon name="squares-2x2" class="size-5" />
                                    </div>
                                    <div class="space-y-1">
                                        <div class="sb-chart-location-kicker">Theme lane</div>
                                        <div class="sb-chart-location-title">{{ $selectedTheme->name }}</div>
                                    </div>
                                </div>

                                <div class="sb-chart-location-copy">
                                    This browse view is narrowed to titles linked through the imported <code>interest_categories</code> graph.
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($badgeItems->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($badgeItems as $badgeItem)
                                <x-ui.badge variant="outline" color="neutral" :icon="$badgeItem['icon']">{{ $badgeItem['label'] }}</x-ui.badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($actions !== [])
                    <div class="flex flex-wrap gap-3">
                        @foreach ($actions as $action)
                            @if (($action['variant'] ?? 'ghost') === 'outline')
                                <x-ui.button.light-outline :href="$action['href']" :icon="$action['icon'] ?? null">
                                    {{ $action['label'] }}
                                </x-ui.button.light-outline>
                            @else
                                <x-ui.button
                                    as="a"
                                    :href="$action['href']"
                                    :variant="$action['variant'] ?? 'ghost'"
                                    :icon="$action['icon'] ?? null"
                                >
                                    {{ $action['label'] }}
                                </x-ui.button>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
            @if ($isChartPage)
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(17rem,0.7fr)] xl:items-start">
                    <x-ui.text class="sb-home-section-copy text-sm">
                        {{ $description }}
                    </x-ui.text>

                    <div class="sb-chart-legend" data-slot="chart-legend">
                        <div class="sb-chart-legend-kicker">Movement</div>
                        <div class="sb-chart-legend-copy">
                            @if ($countryLabel)
                                Up and down markers compare local placement with the broader global chart.
                            @else
                                Up and down markers compare current chart placement with popularity position inside the same chart page.
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <x-ui.text class="sb-home-section-copy text-sm">
                    {{ $description }}
                </x-ui.text>
            @endif
        </x-ui.card>

        @if (! $isChartPage)
            <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5" data-slot="catalog-browse-theme-spotlight">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="sb-page-kicker">Theme lanes</div>
                            <x-ui.heading level="h2" size="lg">Browse through discovery clusters</x-ui.heading>
                            <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                Reuse the imported <code>interest_categories</code> graph as a title lens. Each lane keeps the current browse route and swaps the theme context in place.
                            </x-ui.text>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            @if ($clearThemeHref)
                                <x-ui.button as="a" :href="$clearThemeHref" variant="outline" icon="x-mark">
                                    Clear theme lane
                                </x-ui.button>
                            @endif

                            <x-ui.link :href="$themeDirectoryHref" variant="ghost" iconAfter="arrow-right">
                                Browse all themes
                            </x-ui.link>
                        </div>
                    </div>

                    @if ($themeSpotlightUnavailable)
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                            <x-ui.empty.media>
                                <x-ui.icon name="signal-slash" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Theme lanes are temporarily unavailable.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                {{ $themeSpotlightStatusText }}
                            </x-ui.text>
                        </x-ui.empty>
                    @elseif ($themeSpotlightItems->isNotEmpty())
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($themeSpotlightItems as $themeSpotlightItem)
                                <a
                                    href="{{ $themeSpotlightItem['href'] }}"
                                    class="group rounded-[1.3rem] border border-black/5 bg-white/70 p-4 transition hover:-translate-y-0.5 hover:bg-white dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/[0.06]"
                                >
                                    <div class="space-y-3">
                                        <div class="flex flex-wrap gap-2">
                                            <x-ui.badge variant="outline" icon="squares-2x2">
                                                {{ $themeSpotlightItem['interestCountBadgeLabel'] }}
                                            </x-ui.badge>

                                            @if ($themeSpotlightItem['titleLinkedInterestCount'] > 0)
                                                <x-ui.badge variant="outline" color="neutral" icon="film">
                                                    {{ $themeSpotlightItem['titleLinkedInterestCountBadgeLabel'] }}
                                                </x-ui.badge>
                                            @endif
                                        </div>

                                        <div class="space-y-2">
                                            <x-ui.heading level="h3" size="md" class="font-[family-name:var(--font-editorial)] text-[1.08rem] font-semibold tracking-[-0.03em] text-[#f4eee5] group-hover:text-white">
                                                {{ $themeSpotlightItem['name'] }}
                                            </x-ui.heading>

                                            <x-ui.text class="text-sm text-[#aca293] dark:text-[#aca293]">
                                                {{ $themeSpotlightItem['description'] }}
                                            </x-ui.text>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                            <x-ui.empty.media>
                                <x-ui.icon name="squares-2x2" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No theme lanes are available for this browse route right now.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                The imported interest-category graph does not currently expose more published lanes beyond the active filter state.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>
        @endif

        <livewire:catalog.title-browser
            :types="$browserProps['types'] ?? []"
            :genre="$browserProps['genre'] ?? null"
            :theme="$browserProps['theme'] ?? null"
            :year="$browserProps['year'] ?? null"
            :country="$browserProps['country'] ?? null"
            :sort="$browserProps['sort'] ?? 'popular'"
            :page-name="$browserProps['pageName'] ?? 'titles'"
            :per-page="$browserProps['perPage'] ?? 12"
            :show-all="$browserProps['showAll'] ?? false"
            :display-mode="$displayMode"
            :exclude-episodes="$browserProps['excludeEpisodes'] ?? true"
            :show-summary="$browserProps['showSummary'] ?? true"
            :empty-heading="$browserProps['emptyHeading'] ?? 'No titles match this collection yet.'"
            :empty-text="$browserProps['emptyText'] ?? 'Try another route into the catalog.'"
        />
    </section>
@endsection
