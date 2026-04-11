@extends('layouts.public')

@section('title', $title->name.' Trivia & Goofs')
@section('meta_description', 'Browse trivia notes and goofs for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $title)">{{ $title->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Trivia &amp; Goofs</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero sb-trivia-hero !max-w-none overflow-hidden p-0" data-slot="title-trivia-hero">
            <div class="sb-metadata-hero-backdrop">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $title->name }}"
                        class="sb-metadata-hero-backdrop-image"
                    >
                @endif
            </div>

            <div class="relative grid gap-6 p-6 sm:p-7 xl:grid-cols-[minmax(0,11rem)_minmax(0,1fr)_minmax(18rem,0.84fr)] xl:items-end">
                <div class="sb-metadata-poster-shell">
                    @if ($poster)
                        <img
                            src="{{ $poster->url }}"
                            alt="{{ $poster->alt_text ?: $title->name }}"
                            class="aspect-[2/3] w-full object-cover"
                            loading="lazy"
                        >
                    @else
                        <div class="sb-metadata-poster-empty">
                            <x-ui.icon name="sparkles" class="size-9" />
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="sb-page-kicker">Fan Dossier</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Trivia &amp; Goofs</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl text-base">
                            Behind-the-scenes notes, continuity slips, spoiler labels, and quiet community signal for {{ $title->name }}, separated into a cleaner two-lane archive.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" color="amber" icon="sparkles">{{ number_format($triviaTotalCount) }} trivia notes</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" icon="exclamation-circle">{{ number_format($goofTotalCount) }} goof records</x-ui.badge>
                        <x-ui.badge variant="outline" color="neutral" icon="eye-slash">{{ number_format($spoilerFactCount) }} spoiler labels</x-ui.badge>
                    </div>
                </div>

                <div class="sb-trivia-hero-panel">
                    <div class="space-y-3">
                        <div>
                            <div class="sb-trivia-panel-kicker">Split View</div>
                            <div class="sb-trivia-panel-copy">
                                Trivia and goofs stay separated so film fans can browse production lore without mixing it with continuity mistakes.
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="sb-trivia-stat-card">
                                <div class="sb-trivia-stat-label">Archive notes</div>
                                <div class="sb-trivia-stat-value">{{ number_format($triviaTotalCount + $goofTotalCount) }}</div>
                                <div class="sb-trivia-stat-copy">Structured into two cleaner tabs for vertical scanning.</div>
                            </div>

                            <div class="sb-trivia-stat-card">
                                <div class="sb-trivia-stat-label">Spoiler-tagged</div>
                                <div class="sb-trivia-stat-value">{{ number_format($spoilerFactCount) }}</div>
                                <div class="sb-trivia-stat-copy">Only explicitly marked items carry a spoiler label.</div>
                            </div>
                        </div>

                        <x-catalog.back-link :href="route('public.titles.show', $title)" />
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-detail-section sb-trivia-shell !max-w-none p-5 sm:p-6">
            <div class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Archive Notes</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Two clean tabs keep fan-favorite production facts and cataloged mistakes readable without turning the page into a noisy message board.
                        </x-ui.text>
                    </div>

                    <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                        {{ number_format($triviaTotalCount + $goofTotalCount) }} total entries
                    </x-ui.badge>
                </div>

                <x-ui.tabs class="sb-trivia-tabs-shell" variant="outlined" activeTab="trivia" data-slot="title-trivia-tabs">
                    <x-ui.tab.group class="justify-start sb-trivia-tab-group">
                        <x-ui.tab name="trivia" class="sb-trivia-tab">
                            <span>Trivia</span>
                            <span class="sb-trivia-tab-count">{{ number_format($triviaTotalCount) }}</span>
                        </x-ui.tab>
                        <x-ui.tab name="goofs" class="sb-trivia-tab">
                            <span>Goofs</span>
                            <span class="sb-trivia-tab-count">{{ number_format($goofTotalCount) }}</span>
                        </x-ui.tab>
                    </x-ui.tab.group>

                    <x-ui.tab.panel name="trivia" class="!border-0 !bg-transparent !p-0">
                        <div class="sb-fact-card-stack pt-4" data-slot="title-trivia-cards">
                            @if ($triviaItems->isNotEmpty())
                                @foreach ($triviaItems as $triviaItem)
                                    <article class="sb-fact-card sb-fact-card--trivia" data-slot="trivia-card">
                                        <div class="sb-fact-card-topline">
                                            <div class="sb-fact-card-kicker">Trivia note {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                                            <div class="sb-fact-card-flags">
                                                @if ($triviaItem['scoreLabel'])
                                                    <span class="sb-fact-interest sb-fact-interest--{{ $triviaItem['scoreTone'] }}">{{ $triviaItem['scoreLabel'] }}</span>
                                                @endif
                                                @if ($triviaItem['isSpoiler'])
                                                    <span class="sb-fact-spoiler">Spoiler</span>
                                                @endif
                                            </div>
                                        </div>

                                        <x-ui.text class="sb-fact-card-copy">
                                            {{ $triviaItem['text'] }}
                                        </x-ui.text>
                                    </article>
                                @endforeach
                            @else
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="sparkles" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">Trivia has not been published for this title yet.</x-ui.heading>
                                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                        This tab is ready for production anecdotes and fan notes as soon as the title payload carries them.
                                    </x-ui.text>
                                </x-ui.empty>
                            @endif
                        </div>
                    </x-ui.tab.panel>

                    <x-ui.tab.panel name="goofs" class="!border-0 !bg-transparent !p-0">
                        <div class="sb-fact-card-stack pt-4" data-slot="title-goof-cards">
                            @if ($goofItems->isNotEmpty())
                                @foreach ($goofItems as $goofItem)
                                    <article class="sb-fact-card sb-fact-card--goof" data-slot="goof-card">
                                        <div class="sb-fact-card-topline">
                                            <div class="sb-fact-card-kicker">Goof record {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                                            <div class="sb-fact-card-flags">
                                                @if ($goofItem['scoreLabel'])
                                                    <span class="sb-fact-interest sb-fact-interest--{{ $goofItem['scoreTone'] }}">{{ $goofItem['scoreLabel'] }}</span>
                                                @endif
                                                @if ($goofItem['isSpoiler'])
                                                    <span class="sb-fact-spoiler">Spoiler</span>
                                                @endif
                                            </div>
                                        </div>

                                        <x-ui.text class="sb-fact-card-copy">
                                            {{ $goofItem['text'] }}
                                        </x-ui.text>
                                    </article>
                                @endforeach
                            @else
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="exclamation-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">Goofs are not available for this title yet.</x-ui.heading>
                                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                        Continuity slips and production mistakes will appear here once that feed is attached to the title.
                                    </x-ui.text>
                                </x-ui.empty>
                            @endif
                        </div>
                    </x-ui.tab.panel>
                </x-ui.tabs>
            </div>
        </x-ui.card>
    </section>
@endsection
