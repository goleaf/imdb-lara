@props([
    'title',
    'comparisonLabel' => null,
    'rank',
    'movementAmount' => 0,
    'movementDirection' => 'steady',
    'movementNote' => 'vs popularity',
])

@php
    $titleUrl = route('public.titles.show', $title);
    $poster = $title->preferredPoster();
    $summaryText = $title->summaryText();
    $genres = $title->resolvedGenres();
    $releaseYear = $title->release_year;
    $runtimeLabel = $title->runtimeMinutesLabel();
    $originalTitle = filled($title->original_name) && $title->original_name !== $title->name
        ? $title->original_name
        : null;
    $voteLabel = $title->displayRatingCount() > 0 ? number_format($title->displayRatingCount()).' votes' : null;
    $comparisonToken = filled($comparisonLabel) && $comparisonLabel !== $voteLabel
        ? (string) $comparisonLabel
        : null;
    $originCountryCode = $title->originCountryCode();
    $originCountryLabel = $title->originCountryLabel();
@endphp

<x-ui.card class="sb-chart-card !max-w-none overflow-hidden rounded-[1.45rem] p-3.5" data-slot="chart-title-card">
    <div class="grid gap-4 sm:grid-cols-[4.8rem_5.8rem_minmax(0,1fr)] xl:grid-cols-[5.2rem_6.6rem_minmax(0,1fr)] xl:items-start">
        <div class="sb-chart-rank-block">
            <div class="sb-chart-rank-number" data-slot="chart-rank-number">
                {{ str_pad((string) $rank, 2, '0', STR_PAD_LEFT) }}
            </div>
            <div class="sb-chart-rank-label">Rank</div>
            <div class="sb-chart-movement sb-chart-movement--{{ $movementDirection }}" data-slot="chart-movement">
                <x-ui.icon :name="match ($movementDirection) { 'up' => 'arrow-trending-up', 'down' => 'arrow-trending-down', default => 'minus' }" class="size-3.5" />
                <span>{{ match ($movementDirection) { 'up' => 'Up '.$movementAmount, 'down' => 'Down '.$movementAmount, default => 'Steady' } }}</span>
            </div>
            @if (filled($movementNote))
                <div class="sb-chart-movement-note">{{ $movementNote }}</div>
            @endif
        </div>

        <a
            href="{{ $titleUrl }}"
            class="group sb-chart-poster overflow-hidden rounded-[1rem]"
        >
            @if ($poster)
                <img
                    src="{{ $poster->url }}"
                    alt="{{ $poster->accessibleAltText($title->name) }}"
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

                        @if ($releaseYear)
                            <a href="{{ route('public.years.show', ['year' => $releaseYear]) }}" class="sb-chart-meta-token sb-chart-meta-token--interactive">
                                <x-ui.icon name="calendar-days" class="size-3" />
                                {{ $releaseYear }}
                            </a>
                        @endif

                        @if (filled($runtimeLabel))
                            <span class="sb-chart-meta-token">
                                <x-ui.icon name="clock" class="size-3" />
                                {{ $runtimeLabel }}
                            </span>
                        @endif

                        @if (filled($originCountryCode) && filled($originCountryLabel))
                            <span class="sb-chart-meta-token">
                                <x-ui.flag type="country" :code="$originCountryCode" class="size-3.5" />
                                {{ $originCountryLabel }}
                            </span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <x-ui.heading level="h3" size="md" class="sb-chart-title">
                            <a href="{{ $titleUrl }}" class="hover:opacity-80">
                                {{ $title->name }}
                            </a>
                        </x-ui.heading>

                        @if (filled($originalTitle))
                            <div class="sb-chart-original-title">Original title: {{ $originalTitle }}</div>
                        @endif

                        @if (filled($summaryText))
                            <x-ui.text class="sb-chart-summary">
                                {{ str($summaryText)->limit(210) }}
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

                    @if (filled($voteLabel))
                        <div class="sb-chart-metric">
                            <div class="sb-chart-metric-label">Audience</div>
                            <div class="sb-chart-metric-value">{{ $voteLabel }}</div>
                        </div>
                    @endif

                    @if (filled($comparisonToken))
                        <div class="sb-chart-metric">
                            <div class="sb-chart-metric-label">Signal</div>
                            <div class="sb-chart-metric-value">
                                <x-ui.icon name="sparkles" class="size-3.5" />
                                {{ $comparisonToken }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($genres->isNotEmpty())
                <div class="sb-chart-genre-links">
                    @foreach ($genres as $genre)
                        <a href="{{ route('public.genres.show', $genre) }}" class="sb-chart-genre-link">
                            {{ $genre->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="sb-chart-footer">
                <div class="flex flex-wrap items-center gap-2.5">
                    @if (filled($movementNote))
                        <span class="sb-chart-footer-note">{{ $movementNote }}</span>
                    @endif

                    @if ($comparisonToken)
                        <span class="sb-chart-footer-separator">•</span>
                    @endif

                    @if (filled($comparisonToken))
                        <span class="sb-chart-footer-note">{{ $comparisonToken }}</span>
                    @endif
                </div>

                <x-ui.button.light-action :href="$titleUrl" icon="film">
                    View title
                </x-ui.button.light-action>
            </div>
        </div>
    </div>
</x-ui.card>
