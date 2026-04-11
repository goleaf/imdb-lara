<div>
@island(name: 'title-browser-page')
<div class="space-y-4" data-slot="title-browser-island">
    @if ($this->viewData['isCatalogUnavailable'])
        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-4 sm:p-5" data-slot="title-browser-status">
            <div class="space-y-2">
                <div class="sb-page-kicker">{{ $this->viewData['isUsingStaleCache'] ? 'Cached catalog snapshot' : 'Catalog unavailable' }}</div>
                <x-ui.heading level="h2" size="md">{{ $this->viewData['statusHeading'] }}</x-ui.heading>
                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                    {{ $this->viewData['statusText'] }}
                </x-ui.text>
            </div>
        </x-ui.card>
    @endif

    <div class="sb-results-shell space-y-4 rounded-[1.6rem] p-4 sm:p-5">
        @if ($this->viewData['isChartMode'])
            <div class="sb-chart-context-shell" data-slot="chart-context-shell">
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(18rem,0.92fr)] xl:items-start">
                    <div class="space-y-3">
                        <div class="sb-chart-context-kicker">
                            {{ $this->viewData['selectedCountryCode'] !== '' ? 'Local vs Global' : 'Global Context' }}
                        </div>

                        @if ($this->viewData['selectedCountryCode'] !== '')
                            <div class="flex flex-wrap items-center gap-3">
                                <x-ui.flag type="country" :code="$this->viewData['selectedCountryCode']" class="size-5" />
                                <x-ui.heading level="h3" size="md" class="sb-chart-context-title">
                                    {{ $this->viewData['selectedCountryLabel'] }} local chart
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

                    @if ($this->viewData['chartCountryOptions'] !== [])
                        <div class="space-y-3">
                            <div class="sb-chart-context-kicker">Country Lenses</div>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ request()->fullUrlWithoutQuery(['country']) }}"
                                    class="sb-chart-country-chip {{ $this->viewData['selectedCountryCode'] === '' ? 'sb-chart-country-chip--active' : '' }}"
                                >
                                    <span>Global</span>
                                </a>

                                @foreach ($this->viewData['chartCountryOptions'] as $chartCountry)
                                    <a
                                        href="{{ request()->fullUrlWithQuery(['country' => $chartCountry['code']]) }}"
                                        class="sb-chart-country-chip {{ $this->viewData['selectedCountryCode'] === $chartCountry['code'] ? 'sb-chart-country-chip--active' : '' }}"
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

        <div class="{{ $this->viewData['isChartMode'] ? 'space-y-3' : 'grid gap-4 md:grid-cols-2 xl:grid-cols-3' }}">
            @forelse ($this->viewData['titles'] as $title)
                @if ($this->viewData['isChartMode'])
                    <div wire:key="title-browser-{{ $title->id }}">
                        <x-catalog.chart-title-card
                            :title="$title"
                            :comparison-label="$this->viewData['chartRows'][$title->id]['comparisonLabel']"
                            :rank="$this->viewData['chartRows'][$title->id]['rank']"
                            :movement-amount="$this->viewData['chartRows'][$title->id]['movementAmount']"
                            :movement-direction="$this->viewData['chartRows'][$title->id]['movementDirection']"
                            :movement-note="$this->viewData['chartRows'][$title->id]['movementNote']"
                        />
                    </div>
                @else
                    <div wire:key="title-browser-{{ $title->id }}">
                        <x-catalog.title-card :title="$title" :show-summary="$this->viewData['showSummary']" />
                    </div>
                @endif
            @empty
                <div class="{{ $this->viewData['isChartMode'] ? '' : 'md:col-span-2 xl:col-span-3' }}">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.empty.media>
                            <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">
                            {{ $this->viewData['isCatalogUnavailable'] ? $this->viewData['statusHeading'] : $this->viewData['emptyHeading'] }}
                        </x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            @if ($this->viewData['isCatalogUnavailable'])
                                {{ $this->viewData['statusText'] }}
                            @elseif ($this->viewData['isChartMode'] && $this->viewData['selectedCountryCode'] !== '')
                                No chart titles are currently available for {{ $this->viewData['selectedCountryLabel'] }}.
                            @else
                                {{ $this->viewData['emptyText'] }}
                            @endif
                        </x-ui.text>

                        @if ($this->viewData['isChartMode'] && $this->viewData['selectedCountryCode'] !== '')
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

        @if ($this->viewData['hasPagination'])
            <div>
                {{ $this->viewData['titles']->links() }}
            </div>
        @endif
    </div>
</div>
@endisland
</div>
