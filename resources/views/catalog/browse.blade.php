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
                            <x-ui.button
                                as="a"
                                :href="$action['href']"
                                :variant="$action['variant'] ?? 'ghost'"
                                :icon="$action['icon'] ?? null"
                            >
                                {{ $action['label'] }}
                            </x-ui.button>
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

        <livewire:catalog.title-browser
            :types="$browserProps['types'] ?? []"
            :genre="$browserProps['genre'] ?? null"
            :year="$browserProps['year'] ?? null"
            :country="request()->query('country')"
            :sort="$browserProps['sort'] ?? 'popular'"
            :page-name="$browserProps['pageName'] ?? 'titles'"
            :per-page="$browserProps['perPage'] ?? 12"
            :display-mode="$displayMode"
            :exclude-episodes="$browserProps['excludeEpisodes'] ?? true"
            :show-summary="$browserProps['showSummary'] ?? true"
            :empty-heading="$browserProps['emptyHeading'] ?? 'No titles match this collection yet.'"
            :empty-text="$browserProps['emptyText'] ?? 'Try another route into the catalog.'"
        />
    </section>
@endsection
