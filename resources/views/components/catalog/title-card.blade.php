@props([
    'title',
    'showSummary' => true,
])

@php
    $poster = $title->relationLoaded('mediaAssets') ? $title->mediaAssets->first() : null;
    $genres = $title->relationLoaded('genres') ? $title->genres->take(3) : collect();
    $statistic = $title->relationLoaded('statistic') ? $title->statistic : null;
@endphp

<x-ui.card class="!max-w-none h-full overflow-hidden">
    <div class="flex h-full flex-col gap-4">
        <a
            href="{{ route('public.titles.show', $title) }}"
            class="group block overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800"
        >
            @if ($poster)
                <img
                    src="{{ $poster->url }}"
                    alt="{{ $poster->alt_text ?: $title->name }}"
                    class="aspect-[2/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
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
                <x-ui.badge variant="outline">{{ str($title->title_type->value)->headline() }}</x-ui.badge>

                @if ($title->release_year)
                    <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}">
                        <x-ui.badge variant="outline" color="slate">{{ $title->release_year }}</x-ui.badge>
                    </a>
                @endif

                @if ($statistic?->average_rating)
                    <x-ui.badge icon="star" color="amber">
                        {{ number_format((float) $statistic->average_rating, 1) }}
                    </x-ui.badge>
                @endif
            </div>

            <div class="space-y-2">
                <x-ui.heading level="h3" size="md">
                    <a href="{{ route('public.titles.show', $title) }}" class="hover:opacity-80">
                        {{ $title->name }}
                    </a>
                </x-ui.heading>

                @if ($showSummary && filled($title->plot_outline))
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        {{ str($title->plot_outline)->limit(140) }}
                    </x-ui.text>
                @endif
            </div>

            @if ($genres->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach ($genres as $genre)
                        <a href="{{ route('public.genres.show', $genre) }}">
                            <x-ui.badge variant="outline" color="neutral">{{ $genre->name }}</x-ui.badge>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="mt-auto flex items-center justify-between gap-3 text-sm text-neutral-500 dark:text-neutral-400">
                <span>{{ number_format((int) ($statistic?->review_count ?? 0)) }} reviews</span>
                <x-ui.link :href="route('public.titles.show', $title)" variant="ghost">
                    View title
                </x-ui.link>
            </div>
        </div>
    </div>
</x-ui.card>
