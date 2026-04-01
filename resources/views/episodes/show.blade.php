@extends('layouts.public')

@section('title', $episode->name)
@section('meta_description', $episode->plot_outline ?: 'Browse credits, reviews, and metadata for '.$episode->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.series.index')">TV Shows</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $series)">{{ $series->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.seasons.show', ['series' => $series, 'season' => $season])">{{ $season->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $episode->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    @php
        $still = $episode->mediaAssets->first();
    @endphp

    <section class="grid gap-6 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
        <x-ui.card class="!max-w-none overflow-hidden">
            @if ($still)
                <img
                    src="{{ $still->url }}"
                    alt="{{ $still->alt_text ?: $episode->name }}"
                    class="aspect-video w-full rounded-box object-cover"
                >
            @else
                <div class="flex aspect-video items-center justify-center rounded-box bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                    <x-ui.icon name="tv" class="size-12" />
                </div>
            @endif
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="outline">{{ $series->name }}</x-ui.badge>
                    <x-ui.badge variant="outline" color="slate">
                        S{{ str_pad((string) $season->season_number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad((string) $episode->episodeMeta->episode_number, 2, '0', STR_PAD_LEFT) }}
                    </x-ui.badge>
                    @if ($episode->episodeMeta->aired_at)
                        <x-ui.badge variant="outline" color="neutral">
                            {{ $episode->episodeMeta->aired_at->format('M j, Y') }}
                        </x-ui.badge>
                    @endif
                    @if ($episode->statistic?->average_rating)
                        <x-ui.badge icon="star" color="amber">
                            {{ number_format((float) $episode->statistic->average_rating, 1) }}
                        </x-ui.badge>
                    @endif
                </div>

                <div class="space-y-2">
                    <x-ui.heading level="h1" size="xl">{{ $episode->name }}</x-ui.heading>
                    <x-ui.text class="text-base text-neutral-600 dark:text-neutral-300">
                        {{ $episode->plot_outline ?: 'No public plot outline has been published for this episode yet.' }}
                    </x-ui.text>
                </div>

                @if ($episode->genres->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($episode->genres as $genre)
                            <a href="{{ route('public.genres.show', $genre) }}">
                                <x-ui.badge variant="outline" color="neutral">{{ $genre->name }}</x-ui.badge>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Ratings</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($episode->statistic?->rating_count ?? 0)) }}</div>
                    </div>
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Reviews</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($episode->statistic?->review_count ?? 0)) }}</div>
                    </div>
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Watchlists</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($episode->statistic?->watchlist_count ?? 0)) }}</div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <x-ui.heading level="h2" size="lg">Episode credits</x-ui.heading>

                <div class="grid gap-3">
                    @forelse ($episode->credits as $credit)
                        <div class="flex items-center justify-between gap-4 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                            <div>
                                <div class="font-medium">
                                    <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                        {{ $credit->person->name }}
                                    </a>
                                </div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $credit->department }} · {{ $credit->job }}
                                </div>
                            </div>

                            @if ($credit->character_name)
                                <x-ui.badge variant="outline" color="neutral">{{ $credit->character_name }}</x-ui.badge>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.heading level="h3">No public credits have been published for this episode.</x-ui.heading>
                        </x-ui.empty>
                    @endforelse
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <x-ui.heading level="h2" size="lg">Latest reviews</x-ui.heading>
                    <x-ui.badge variant="outline" color="neutral">
                        {{ number_format($episode->reviews->count()) }} published
                    </x-ui.badge>
                </div>

                <div class="grid gap-3">
                    @forelse ($episode->reviews->take(4) as $review)
                        <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-medium">{{ $review->headline ?: 'Member review' }}</div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $review->author->name }}
                                </div>
                            </div>
                            <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ str($review->body)->limit(220) }}
                            </x-ui.text>
                        </div>
                    @empty
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.heading level="h3">No published reviews yet.</x-ui.heading>
                        </x-ui.empty>
                    @endforelse
                </div>
            </div>
        </x-ui.card>
    </section>
@endsection
