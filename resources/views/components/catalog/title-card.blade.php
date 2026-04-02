@props([
    'title',
    'showSummary' => true,
    'trackingAddedAt' => null,
    'trackingState' => null,
    'trackingWatchedAt' => null,
])

<x-ui.card class="sb-poster-card !max-w-none h-full overflow-hidden rounded-[1.4rem] p-3">
    <div class="flex h-full flex-col gap-4">
        <a
            href="{{ route('public.titles.show', $title) }}"
            class="group sb-poster-frame block overflow-hidden rounded-[1.1rem]"
        >
            @if ($title->preferredPoster())
                <img
                    src="{{ $title->preferredPoster()->url }}"
                    alt="{{ $title->preferredPoster()->alt_text ?: $title->name }}"
                    class="aspect-[2/3] w-full object-cover transition duration-500 group-hover:scale-[1.035]"
                    loading="lazy"
                >
            @else
                <div class="flex aspect-[2/3] items-center justify-center bg-neutral-200 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                    <x-ui.icon name="film" class="size-10" />
                </div>
            @endif
        </a>

        <div class="flex flex-1 flex-col gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="outline" :icon="$title->typeIcon()">{{ $title->typeLabel() }}</x-ui.badge>

                @if ($title->release_year)
                    <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}">
                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $title->release_year }}</x-ui.badge>
                    </a>
                @endif

                @if ($title->displayAverageRating())
                    <x-ui.badge icon="star" color="amber">
                        {{ number_format($title->displayAverageRating(), 1) }}
                    </x-ui.badge>
                @endif
            </div>

            <div class="space-y-2">
                <x-ui.heading level="h3" size="md" class="font-[family-name:var(--font-editorial)] text-[1.28rem] font-semibold tracking-[-0.03em] text-[#f4eee5]">
                    <a href="{{ route('public.titles.show', $title) }}" class="hover:opacity-80">
                        {{ $title->name }}
                    </a>
                </x-ui.heading>

                @if ($showSummary && filled($title->plot_outline))
                    <x-ui.text class="text-sm leading-6 text-[#aca293] dark:text-[#aca293]">
                        {{ str($title->plot_outline)->limit(140) }}
                    </x-ui.text>
                @endif
            </div>

            @if ($title->previewGenres()->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach ($title->previewGenres() as $genre)
                        <a href="{{ route('public.genres.show', $genre) }}">
                            <x-ui.badge variant="outline" color="neutral" icon="tag">{{ $genre->name }}</x-ui.badge>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($trackingState || $trackingAddedAt || $trackingWatchedAt)
                <div class="flex flex-wrap gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                    @if ($trackingState instanceof \App\Enums\WatchState)
                        <x-ui.badge
                            variant="outline"
                            :color="$trackingState->color()"
                            :icon="$trackingState->icon()"
                        >
                            {{ $trackingState->label() }}
                        </x-ui.badge>
                    @endif

                    @if ($trackingAddedAt)
                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">
                            Added {{ $trackingAddedAt->format('M j, Y') }}
                        </x-ui.badge>
                    @endif

                    @if ($trackingWatchedAt)
                        <x-ui.badge variant="outline" color="green" icon="check-circle">
                            Watched {{ $trackingWatchedAt->format('M j, Y') }}
                        </x-ui.badge>
                    @endif
                </div>
            @endif

            <div class="mt-auto flex items-center justify-between gap-3 text-sm text-[#988f82] dark:text-[#988f82]">
                <span>{{ number_format($title->displayReviewCount()) }} reviews</span>
                <x-ui.link :href="route('public.titles.show', $title)" variant="ghost" iconAfter="arrow-right">
                    View title
                </x-ui.link>
            </div>

            @if ($slot->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</x-ui.card>
