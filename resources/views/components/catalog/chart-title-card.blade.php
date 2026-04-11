@props([
    'title',
    'card',
])

<x-ui.card class="sb-chart-card !max-w-none overflow-hidden rounded-[1.45rem] p-3.5" data-slot="chart-title-card">
    <div class="grid gap-4 sm:grid-cols-[4.8rem_5.8rem_minmax(0,1fr)] xl:grid-cols-[5.2rem_6.6rem_minmax(0,1fr)] xl:items-start">
        <div class="sb-chart-rank-block">
            <div class="sb-chart-rank-number" data-slot="chart-rank-number">
                {{ str_pad((string) $card['rank'], 2, '0', STR_PAD_LEFT) }}
            </div>
            <div class="sb-chart-rank-label">Rank</div>
            <div class="sb-chart-movement sb-chart-movement--{{ $card['movementDirection'] }}" data-slot="chart-movement">
                <x-ui.icon :name="$card['movementIcon']" class="size-3.5" />
                <span>{{ $card['movementLabel'] }}</span>
            </div>
            @if (filled($card['movementNote']))
                <div class="sb-chart-movement-note">{{ $card['movementNote'] }}</div>
            @endif
        </div>

        <a
            href="{{ $card['titleUrl'] }}"
            class="group sb-chart-poster overflow-hidden rounded-[1rem]"
        >
            @if ($card['poster'])
                <img
                    src="{{ $card['poster']->url }}"
                    alt="{{ $card['poster']->accessibleAltText($title->name) }}"
                    class="aspect-[2/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                    loading="lazy"
                >
            @else
                <div class="flex aspect-[2/3] items-center justify-center bg-white/[0.04] text-[#8f877a]">
                    <x-ui.icon name="film" class="size-8" />
                </div>
            @endif
        </a>

        <div class="min-w-0 space-y-4">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="sb-chart-meta">
                        <span class="sb-chart-meta-token">
                            <x-ui.icon :name="$title->typeIcon()" class="size-3" />
                            {{ $title->typeLabel() }}
                        </span>

                        @if ($card['releaseYear'])
                            <a href="{{ route('public.years.show', ['year' => $card['releaseYear']]) }}" class="sb-chart-meta-token sb-chart-meta-token--interactive">
                                <x-ui.icon name="calendar-days" class="size-3" />
                                {{ $card['releaseYear'] }}
                            </a>
                        @endif

                        @if (filled($card['runtimeLabel']))
                            <span class="sb-chart-meta-token">
                                <x-ui.icon name="clock" class="size-3" />
                                {{ $card['runtimeLabel'] }}
                            </span>
                        @endif

                        @if (filled($card['originCountryCode']) && filled($card['originCountryLabel']))
                            <span class="sb-chart-meta-token">
                                <x-ui.flag type="country" :code="$card['originCountryCode']" class="size-3.5" />
                                {{ $card['originCountryLabel'] }}
                            </span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <x-ui.heading level="h3" size="md" class="sb-chart-title">
                            <a href="{{ $card['titleUrl'] }}" class="hover:opacity-80">
                                {{ $title->name }}
                            </a>
                        </x-ui.heading>

                        @if (filled($card['originalTitle']))
                            <div class="sb-chart-original-title">Original title: {{ $card['originalTitle'] }}</div>
                        @endif

                        @if (filled($card['summaryText']))
                            <x-ui.text class="sb-chart-summary">
                                {{ str($card['summaryText'])->limit(210) }}
                            </x-ui.text>
                        @endif
                    </div>
                </div>

                <div class="sb-chart-metric-grid">
                    @if ($title->displayAverageRating())
                        <div class="sb-chart-metric sb-chart-metric--accent">
                            <div class="sb-chart-metric-label">Rating</div>
                            <div class="sb-chart-metric-value">
                                <x-ui.icon name="star" class="size-3.5" />
                                {{ number_format($title->displayAverageRating(), 1) }}
                            </div>
                        </div>
                    @endif

                    @if (filled($card['voteLabel']))
                        <div class="sb-chart-metric">
                            <div class="sb-chart-metric-label">Audience</div>
                            <div class="sb-chart-metric-value">{{ $card['voteLabel'] }}</div>
                        </div>
                    @endif

                    @if (filled($card['comparisonToken']))
                        <div class="sb-chart-metric">
                            <div class="sb-chart-metric-label">Signal</div>
                            <div class="sb-chart-metric-value">
                                <x-ui.icon name="sparkles" class="size-3.5" />
                                {{ $card['comparisonToken'] }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($card['genres']->isNotEmpty())
                <div class="sb-chart-genre-links">
                    @foreach ($card['genres'] as $genre)
                        <a href="{{ route('public.genres.show', $genre) }}" class="sb-chart-genre-link">
                            {{ $genre->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="sb-chart-footer">
                <div class="flex flex-wrap items-center gap-2.5">
                    @if (filled($card['movementNote']))
                        <span class="sb-chart-footer-note">{{ $card['movementNote'] }}</span>
                    @endif

                    @if ($card['comparisonToken'])
                        <span class="sb-chart-footer-separator">•</span>
                    @endif

                    @if (filled($card['comparisonToken']))
                        <span class="sb-chart-footer-note">{{ $card['comparisonToken'] }}</span>
                    @endif
                </div>

                <x-ui.button.light-action :href="$card['titleUrl']" icon="film">
                    View title
                </x-ui.button.light-action>
            </div>
        </div>
    </div>
</x-ui.card>
