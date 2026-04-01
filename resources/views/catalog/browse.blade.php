@extends('layouts.public')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section('breadcrumbs')
    @foreach ($breadcrumbs as $breadcrumb)
        @if (filled($breadcrumb['href'] ?? null))
            <x-ui.breadcrumbs.item :href="$breadcrumb['href']">{{ $breadcrumb['label'] }}</x-ui.breadcrumbs.item>
        @else
            <x-ui.breadcrumbs.item>{{ $breadcrumb['label'] }}</x-ui.breadcrumbs.item>
        @endif
    @endforeach
@endsection

@section('content')
    @php
        $badgeIcons = [
            'film' => 'film',
            'movie' => 'film',
            'tv' => 'tv',
            'series' => 'tv',
            'rating' => 'star',
            'review' => 'chat-bubble-left-right',
            'discover' => 'sparkles',
            'genre' => 'tag',
            'year' => 'calendar-days',
            'people' => 'users',
            'search' => 'magnifying-glass',
        ];
    @endphp

    <section class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl">{{ $heading }}</x-ui.heading>
                        <x-ui.text class="max-w-3xl text-base text-neutral-600 dark:text-neutral-300">
                            {{ $description }}
                        </x-ui.text>
                    </div>

                    @if ($badges !== [])
                        <div class="flex flex-wrap gap-2">
                            @foreach ($badges as $badge)
                                @php
                                    $normalizedBadge = str($badge)->lower();
                                    $badgeIcon = collect($badgeIcons)->first(fn ($icon, $keyword) => $normalizedBadge->contains($keyword));
                                @endphp

                                <x-ui.badge variant="outline" color="neutral" :icon="$badgeIcon">{{ $badge }}</x-ui.badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($actions !== [])
                    <div class="flex flex-wrap gap-3">
                        @foreach ($actions as $action)
                            <x-ui.button
                                as="a"
                                :href="$action['href']"
                                :variant="$action['variant'] ?? 'ghost'"
                                :icon="$action['icon'] ?? null"
                            >
                                {{ $action['label'] }}
                            </x-ui.button>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-ui.card>

        <livewire:catalog.title-browser
            :types="$browserProps['types'] ?? []"
            :genre="$browserProps['genre'] ?? null"
            :year="$browserProps['year'] ?? null"
            :sort="$browserProps['sort'] ?? 'popular'"
            :page-name="$browserProps['pageName'] ?? 'titles'"
            :per-page="$browserProps['perPage'] ?? 12"
            :exclude-episodes="$browserProps['excludeEpisodes'] ?? true"
            :show-summary="$browserProps['showSummary'] ?? true"
            :empty-heading="$browserProps['emptyHeading'] ?? 'No titles match this collection yet.'"
            :empty-text="$browserProps['emptyText'] ?? 'Try another route into the catalog.'"
        />
    </section>
@endsection
