@props([
    'title',
    'comparisonLabel' => null,
    'rank',
    'movementAmount' => 0,
    'movementDirection' => 'steady',
    'movementNote' => 'vs popularity',
])

<x-ui.card class="sb-chart-card !max-w-none overflow-hidden rounded-[1.45rem] p-3.5" data-slot="chart-title-card">
    <div class="grid gap-4 sm:grid-cols-[4.8rem_5.8rem_minmax(0,1fr)] xl:grid-cols-[5.4rem_6.4rem_minmax(0,1fr)_auto] xl:items-start">
        <div class="sb-chart-rank-block">
            <div class="sb-chart-rank-number" data-slot="chart-rank-number">
                {{ str_pad((string) $rank, 2, '0', STR_PAD_LEFT) }}
            </div>
            <div class="sb-chart-rank-label">Rank</div>
            <div class="sb-chart-movement sb-chart-movement--{{ $movementDirection }}" data-slot="chart-movement">
                <x-ui.icon :name="match ($movementDirection) { 'up' => 'arrow-trending-up', 'down' => 'arrow-trending-down', default => 'minus' }" class="size-3.5" />
                <span>{{ match ($movementDirection) { 'up' => 'Up '.$movementAmount, 'down' => 'Down '.$movementAmount, default => 'Steady' } }}</span>
            </div>
            <div class="sb-chart-movement-note">{{ $movementNote }}</div>
        </div>

        <a
            href="{{ route('public.titles.show', $title) }}"
            class="group sb-chart-poster overflow-hidden rounded-[1rem]"
        >
            @if ($title->preferredPoster())
                <img
                    src="{{ $title->preferredPoster()->url }}"
                    alt="{{ $title->preferredPoster()->alt_text ?: $title->name }}"
                    class="aspect-[2/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                    loading="lazy"
                >
            @else
                <div class="flex aspect-[2/3] items-center justify-center bg-white/[0.04] text-[#8f877a]">
                    <x-ui.icon name="film" class="size-8" />
                </div>
            @endif
        </a>

        <div class="min-w-0 space-y-3">
            <div class="space-y-2">
                <div class="sb-chart-meta">
                    <span class="sb-chart-meta-token">
                        <x-ui.icon :name="$title->typeIcon()" class="size-3" />
                        {{ $title->typeLabel() }}
                    </span>

                    @if ($title->release_year)
                        <span class="sb-chart-meta-token">
                            <x-ui.icon name="calendar-days" class="size-3" />
                            {{ $title->release_year }}
                        </span>
                    @endif
                </div>

                <x-ui.heading level="h3" size="md" class="sb-chart-title">
                    <a href="{{ route('public.titles.show', $title) }}" class="hover:opacity-80">
                        {{ $title->name }}
                    </a>
                </x-ui.heading>
            </div>

            <div class="sb-chart-stat-row">
                @if ($title->displayAverageRating())
                    <span class="sb-chart-stat sb-chart-stat--accent">
                        <x-ui.icon name="star" class="size-3.5" />
                        {{ number_format($title->displayAverageRating(), 1) }}
                    </span>
                @endif

                @if ($title->displayRatingCount())
                    <span class="sb-chart-stat">{{ number_format($title->displayRatingCount()) }} votes</span>
                @endif

                @if ($title->displayReviewCount() > 0)
                    <span class="sb-chart-stat">{{ number_format($title->displayReviewCount()) }} reviews</span>
                @endif

                @if (filled($comparisonLabel))
                    <span class="sb-chart-stat">
                        <x-ui.icon name="globe-alt" class="size-3.5" />
                        {{ $comparisonLabel }}
                    </span>
                @endif
            </div>

            <div class="sb-chart-secondary-meta">
                @if ($title->previewGenres(2)->isNotEmpty())
                    <span>{{ $title->previewGenres(2)->pluck('name')->join(' · ') }}</span>
                @endif
                @if ($title->runtime_minutes)
                    <span>{{ $title->runtime_minutes }} min</span>
                @endif
                @if ($title->originCountryCode())
                    <span class="inline-flex items-center gap-2">
                        <x-ui.flag type="country" :code="$title->originCountryCode()" class="size-4" />
                        <span>{{ $title->originCountryCode() }}</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="hidden xl:flex xl:justify-end">
            <x-ui.link :href="route('public.titles.show', $title)" variant="ghost" iconAfter="arrow-right">
                View
            </x-ui.link>
        </div>
    </div>
</x-ui.card>
