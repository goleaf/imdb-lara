<div>
@island(name: 'title-browser-page')
    @php
        $view = $this->viewData;
    @endphp

<div class="space-y-4" data-slot="title-browser-island">
    <div wire:loading.delay class="{{ $view['isChartMode'] ? 'space-y-3' : 'grid gap-4 md:grid-cols-2 xl:grid-cols-3' }}">
        @foreach (range(1, 6) as $index)
            @if ($view['isChartMode'])
                <x-ui.card class="sb-chart-card !max-w-none overflow-hidden rounded-[1.45rem] p-3.5" wire:key="title-browser-skeleton-{{ $index }}">
                    <div class="grid gap-4 sm:grid-cols-[4.8rem_5.8rem_minmax(0,1fr)] xl:grid-cols-[5.4rem_6.4rem_minmax(0,1fr)_auto]">
                        <div class="space-y-2">
                            <x-ui.skeleton.text class="w-10" />
                            <x-ui.skeleton.text class="w-14" />
                            <x-ui.skeleton.text class="w-16" />
                        </div>
                        <x-ui.skeleton class="aspect-[2/3] w-full rounded-[1rem]" />
                        <div class="space-y-3">
                            <x-ui.skeleton.text class="w-1/3" />
                            <x-ui.skeleton.text class="w-3/4" />
                            <x-ui.skeleton.text class="w-2/3" />
                        </div>
                    </div>
                </x-ui.card>
            @else
                <x-ui.card class="sb-poster-card !max-w-none h-full overflow-hidden rounded-[1.4rem]" wire:key="title-browser-skeleton-{{ $index }}">
                    <div class="space-y-4">
                        <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                        <x-ui.skeleton.text class="w-1/3" />
                        <x-ui.skeleton.text class="w-3/4" />
                        <x-ui.skeleton.text class="w-5/6" />
                    </div>
                </x-ui.card>
            @endif
        @endforeach
    </div>

    <div wire:loading.remove class="sb-results-shell space-y-4 rounded-[1.6rem] p-4 sm:p-5">
        @if ($view['isChartMode'])
            <div class="sb-chart-context-shell" data-slot="chart-context-shell">
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(18rem,0.92fr)] xl:items-start">
                    <div class="space-y-3">
                        <div class="sb-chart-context-kicker">
                            {{ $view['selectedCountryCode'] !== '' ? 'Local vs Global' : 'Global Context' }}
                        </div>

                        @if ($view['selectedCountryCode'] !== '')
                            <div class="flex flex-wrap items-center gap-3">
                                <x-ui.flag type="country" :code="$view['selectedCountryCode']" class="size-5" />
                                <x-ui.heading level="h3" size="md" class="sb-chart-context-title">
                                    {{ $view['selectedCountryLabel'] }} local chart
                                </x-ui.heading>
                                <span class="sb-chart-context-pill">Compared with global</span>
                            </div>

                            <x-ui.text class="sb-chart-context-copy">
                                Ranking numbers show local placement first. Movement markers and comparison tokens show how each title sits against the broader global chart.
                            </x-ui.text>
                        @else
                            <x-ui.heading level="h3" size="md" class="sb-chart-context-title">
                                Global chart view
                            </x-ui.heading>

                            <x-ui.text class="sb-chart-context-copy">
                                Switch to a country lens to read the same chart locally, then compare each title against its global chart position.
                            </x-ui.text>
                        @endif
                    </div>

                    @if ($view['chartCountryOptions'] !== [])
                        <div class="space-y-3">
                            <div class="sb-chart-context-kicker">Country Lenses</div>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ request()->fullUrlWithoutQuery(['country']) }}"
                                    class="sb-chart-country-chip {{ $view['selectedCountryCode'] === '' ? 'sb-chart-country-chip--active' : '' }}"
                                >
                                    <span>Global</span>
                                </a>

                                @foreach ($view['chartCountryOptions'] as $chartCountry)
                                    <a
                                        href="{{ request()->fullUrlWithQuery(['country' => $chartCountry['code']]) }}"
                                        class="sb-chart-country-chip {{ $view['selectedCountryCode'] === $chartCountry['code'] ? 'sb-chart-country-chip--active' : '' }}"
                                    >
                                        <x-ui.flag type="country" :code="$chartCountry['code']" class="size-4" />
                                        <span>{{ $chartCountry['label'] }}</span>
                                        <span class="sb-chart-country-chip-count">{{ $chartCountry['count'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="{{ $view['isChartMode'] ? 'space-y-3' : 'grid gap-4 md:grid-cols-2 xl:grid-cols-3' }}">
            @forelse ($view['titles'] as $title)
                @if ($view['isChartMode'])
                    <div wire:key="title-browser-{{ $title->id }}">
                        <x-catalog.chart-title-card
                            :title="$title"
                            :comparison-label="$view['chartRows'][$title->id]['comparisonLabel']"
                            :rank="$view['chartRows'][$title->id]['rank']"
                            :movement-amount="$view['chartRows'][$title->id]['movementAmount']"
                            :movement-direction="$view['chartRows'][$title->id]['movementDirection']"
                            :movement-note="$view['chartRows'][$title->id]['movementNote']"
                        />
                    </div>
                @else
                    <div wire:key="title-browser-{{ $title->id }}">
                        <x-catalog.title-card :title="$title" :show-summary="$view['showSummary']" />
                    </div>
                @endif
            @empty
                <div class="{{ $view['isChartMode'] ? '' : 'md:col-span-2 xl:col-span-3' }}">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.empty.media>
                            <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">{{ $view['emptyHeading'] }}</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            @if ($view['isChartMode'] && $view['selectedCountryCode'] !== '')
                                No chart titles are currently available for {{ $view['selectedCountryLabel'] }}.
                            @else
                                {{ $view['emptyText'] }}
                            @endif
                        </x-ui.text>

                        @if ($view['isChartMode'] && $view['selectedCountryCode'] !== '')
                            <div class="mt-4">
                                <x-ui.button as="a" :href="request()->fullUrlWithoutQuery(['country'])" variant="outline" icon="globe-alt">
                                    Return to global chart
                                </x-ui.button>
                            </div>
                        @endif
                    </x-ui.empty>
                </div>
            @endforelse
        </div>

        <div>
            {{ $view['titles']->links() }}
        </div>
    </div>
</div>
@endisland
</div>
