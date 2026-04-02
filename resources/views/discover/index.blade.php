@extends('layouts.public')

@section('title', 'Discovery')
@section('meta_description', 'Use Screenbase advanced discovery filters to explore titles by type, release date, awards, ratings, votes, language, runtime, and country.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Discovery</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero !max-w-none p-6 sm:p-7" data-slot="discover-hero">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(19rem,0.95fr)]">
                <div class="space-y-5">
                    <div class="sb-page-kicker">Advanced Discovery</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Advanced Title Discovery</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl">
                            Explore films and TV with a deeper filter rail built around release windows, awards, ratings, vote volume, runtime, language, and country. The layout is tuned for enthusiasts who want precise control without losing the poster-first feel of the catalog.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" icon="sparkles">Serious discovery</x-ui.badge>
                        <x-ui.badge variant="outline" color="neutral" icon="trophy">Awards-aware filtering</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" icon="globe-alt">Language + country precision</x-ui.badge>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($featuredGenres->take(8) as $genre)
                            <a href="{{ route('public.genres.show', $genre) }}">
                                <x-ui.badge variant="outline" icon="tag">{{ $genre->name }}</x-ui.badge>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="sb-discovery-radar">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="sb-discovery-radar-kicker">Radar Picks</div>
                            <div class="sb-discovery-section-copy max-w-sm">
                                Quick, poster-first entry points into high-signal titles that fit the discovery language of the page.
                            </div>
                        </div>

                        <x-ui.badge variant="outline" color="amber" icon="sparkles">Poster first</x-ui.badge>
                    </div>

                    <div class="space-y-3">
                        @foreach ($featuredTitles as $featuredTitle)
                            <a href="{{ route('public.titles.show', $featuredTitle) }}" class="sb-discovery-radar-item">
                                <div class="sb-discovery-radar-media">
                                    @if ($featuredTitle->preferredPoster())
                                        <img
                                            src="{{ $featuredTitle->preferredPoster()->url }}"
                                            alt="{{ $featuredTitle->preferredPoster()->alt_text ?: $featuredTitle->name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full min-h-[5.4rem] w-full items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                            <x-ui.icon name="film" class="size-6" />
                                        </div>
                                    @endif
                                </div>

                                <div class="sb-discovery-radar-body">
                                    <div class="truncate text-sm font-semibold text-[#f4eee5]">{{ $featuredTitle->name }}</div>
                                    <div class="sb-discovery-radar-meta">
                                        <span>{{ str($featuredTitle->title_type->value)->headline() }}</span>
                                        @if ($featuredTitle->release_year)
                                            <span>{{ $featuredTitle->release_year }}</span>
                                        @endif
                                        @if (filled($featuredTitle->origin_country))
                                            <span>{{ $featuredTitle->origin_country }}</span>
                                        @endif
                                    </div>

                                    @if ($featuredTitle->previewGenres(2)->isNotEmpty())
                                        <div class="truncate text-[0.72rem] uppercase tracking-[0.18em] text-[#9d9284]">
                                            {{ $featuredTitle->previewGenres(2)->pluck('name')->join(' · ') }}
                                        </div>
                                    @endif
                                </div>

                                <div class="justify-self-end self-start">
                                    @if ($featuredTitle->displayAverageRating())
                                        <span class="sb-search-chip sb-search-chip--accent sb-search-chip--tight">
                                            <x-ui.icon name="star" class="size-3" />
                                            {{ number_format((float) $featuredTitle->displayAverageRating(), 1) }}
                                        </span>
                                    @else
                                        <x-ui.icon name="arrow-right" class="size-4 text-[#9b9184]" />
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-ui.card>

        <livewire:search.discovery-filters />
    </section>
@endsection
