@extends('layouts.public')

@section('title', $season->name.' · '.$series->name)
@section('meta_description', $season->summary ?: 'Browse episode records for '.$season->name.' of '.$series->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.series.index')">TV Shows</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $series)">{{ $series->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $season->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="outline">{{ $series->name }}</x-ui.badge>
                    <x-ui.badge variant="outline" color="slate">Season {{ $season->season_number }}</x-ui.badge>
                    @if ($season->release_year)
                        <a href="{{ route('public.years.show', ['year' => $season->release_year]) }}">
                            <x-ui.badge variant="outline" color="neutral">{{ $season->release_year }}</x-ui.badge>
                        </a>
                    @endif
                </div>

                <div class="space-y-2">
                    <x-ui.heading level="h1" size="xl">{{ $season->name }}</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-base text-neutral-600 dark:text-neutral-300">
                        {{ $season->summary ?: 'Episode order, release chronology, and audience signals for this season.' }}
                    </x-ui.text>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-3">
            @forelse ($season->episodes as $episodeMeta)
                @php
                    $episodeTitle = $episodeMeta->title;
                @endphp

                <x-ui.card class="!max-w-none">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge variant="outline" color="slate">Episode {{ $episodeMeta->episode_number }}</x-ui.badge>
                                @if ($episodeMeta->aired_at)
                                    <x-ui.badge variant="outline" color="neutral">
                                        {{ $episodeMeta->aired_at->format('M j, Y') }}
                                    </x-ui.badge>
                                @endif
                                @if ($episodeTitle->statistic?->average_rating)
                                    <x-ui.badge icon="star" color="amber">
                                        {{ number_format((float) $episodeTitle->statistic->average_rating, 1) }}
                                    </x-ui.badge>
                                @endif
                            </div>

                            <div>
                                <x-ui.heading level="h3" size="md">
                                    <a href="{{ route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeTitle]) }}" class="hover:opacity-80">
                                        {{ $episodeTitle->name }}
                                    </a>
                                </x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $episodeTitle->plot_outline ?: 'No public synopsis is available for this episode yet.' }}
                                </x-ui.text>
                            </div>
                        </div>

                        <x-ui.link :href="route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episodeTitle])" variant="ghost">
                            View episode
                        </x-ui.link>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.heading level="h3">No published episodes are attached to this season yet.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        The season shell exists, but episode records have not been published.
                    </x-ui.text>
                </x-ui.empty>
            @endforelse
        </div>
    </section>
@endsection
