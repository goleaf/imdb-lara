@extends('layouts.public')

@section('title', 'Awards')
@section('meta_description', 'Browse Screenbase award events, categories, winners, and nominated titles or people.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Awards</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero sb-awards-hero !max-w-none p-6 sm:p-7" data-slot="awards-archive-hero">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.02fr)_minmax(18rem,0.98fr)] xl:items-start">
                <div class="space-y-5">
                    <div class="sb-page-kicker">Awards Archive</div>

                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Awards</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl text-base">
                            Browse published award events, winner calls, and nominee records in a clean archive built around event, year, category, and linked titles or people.
                        </x-ui.text>
                    </div>

                    <div class="sb-awards-summary-grid">
                        <div class="sb-awards-summary-card">
                            <div class="sb-awards-summary-label">Award events</div>
                            <div class="sb-awards-summary-value">{{ number_format($summary['eventCount']) }}</div>
                            <div class="sb-awards-summary-copy">Published ceremonies in the archive.</div>
                        </div>
                        <div class="sb-awards-summary-card">
                            <div class="sb-awards-summary-label">Named archives</div>
                            <div class="sb-awards-summary-value">{{ number_format($summary['awardCount']) }}</div>
                            <div class="sb-awards-summary-copy">Distinct published event names represented here.</div>
                        </div>
                        <div class="sb-awards-summary-card">
                            <div class="sb-awards-summary-label">Categories</div>
                            <div class="sb-awards-summary-value">{{ number_format($summary['categoryCount']) }}</div>
                            <div class="sb-awards-summary-copy">Tracked category records across events.</div>
                        </div>
                        <div class="sb-awards-summary-card">
                            <div class="sb-awards-summary-label">Honorees</div>
                            <div class="sb-awards-summary-value">{{ number_format($summary['honoreeCount']) }}</div>
                            <div class="sb-awards-summary-copy">Linked titles, people, and episode entries.</div>
                        </div>
                    </div>
                </div>

                <div class="sb-awards-hero-panel">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="sb-awards-panel-kicker">Archive Highlights</div>
                            <div class="sb-awards-panel-copy">A quick view of the award event archives currently published on Screenbase.</div>
                        </div>

                        <x-ui.badge variant="outline" color="amber" icon="trophy">Prestige archive</x-ui.badge>
                    </div>

                    <div class="space-y-3">
                        @forelse ($featuredAwards as $featuredAward)
                            <div class="sb-awards-featured-award">
                                <div class="sb-awards-featured-award-title">{{ $featuredAward['name'] }}</div>
                                <div class="sb-awards-featured-award-copy">{{ $featuredAward['summary'] }}</div>
                            </div>
                        @empty
                            <div class="sb-awards-featured-award">
                                <div class="sb-awards-featured-award-title">Archive pending</div>
                                <div class="sb-awards-featured-award-copy">Published award bodies will appear here as events are linked.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-results-shell sb-awards-archive-shell !max-w-none p-5 sm:p-6" data-slot="awards-archive-shell">
            @if ($events->isEmpty())
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900/60">
                    <x-ui.empty.media>
                        <x-ui.icon name="trophy" class="size-8 text-[#d6b574]" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No awards have been published yet.</x-ui.heading>
                    <x-ui.text>As award events and nominations are added, this archive will become the canonical awards index.</x-ui.text>
                </x-ui.empty>
            @else
                <div class="sb-awards-timeline space-y-4" data-slot="awards-timeline">
                    @foreach ($events as $event)
                        <article class="sb-award-event-shell" data-slot="award-event-shell">
                            <div class="sb-award-event-marker" data-slot="award-event-marker">
                                <div class="sb-award-event-marker-node">
                                    <div class="sb-award-event-marker-label">Ceremony</div>
                                    <div class="sb-award-event-marker-value">{{ $event['year'] ?: 'Archive' }}</div>
                                </div>
                            </div>

                            <div class="sb-award-event-card p-5 sm:p-6" data-slot="award-event-card">
                                <div class="grid gap-5 xl:grid-cols-[minmax(0,0.82fr)_minmax(0,1.18fr)] xl:items-start">
                                <div class="space-y-4">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="space-y-2">
                                            <div class="sb-award-event-kicker">{{ $event['awardName'] }}</div>
                                            <x-ui.heading level="h2" size="lg" class="sb-award-event-title">{{ $event['name'] }}</x-ui.heading>
                                        </div>

                                        @if ($event['dateLabel'] || $event['location'])
                                            <div class="sb-award-event-year">
                                                @if ($event['dateLabel'])
                                                    <div>{{ $event['dateLabel'] }}</div>
                                                @endif
                                                @if ($event['location'])
                                                    <div class="sb-award-event-year-copy">{{ $event['location'] }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.badge variant="outline" color="amber" icon="sparkles">
                                            {{ number_format($event['categoryCount']) }} {{ str('category')->plural($event['categoryCount']) }}
                                        </x-ui.badge>
                                        <x-ui.badge variant="outline" color="neutral" icon="trophy">
                                            {{ number_format($event['winnerCount']) }} {{ str('winner')->plural($event['winnerCount']) }}
                                        </x-ui.badge>
                                    </div>

                                    @if ($event['edition'])
                                        <x-ui.text class="sb-award-event-copy text-sm">
                                            {{ $event['edition'] }} edition archive record.
                                        </x-ui.text>
                                    @else
                                        <x-ui.text class="sb-award-event-copy text-sm">
                                            Winner and nominee records organized by category for fast archive reference.
                                        </x-ui.text>
                                    @endif
                                </div>

                                <div class="space-y-3">
                                    @foreach ($event['categories'] as $category)
                                        <section class="sb-award-category-card">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="space-y-1">
                                                    <div class="sb-award-category-title">{{ $category['name'] }}</div>
                                                    <div class="sb-award-category-meta">
                                                        {{ $category['scopeLabel'] }} ·
                                                        {{ number_format($category['winnerCount']) }} {{ str('winner')->plural($category['winnerCount']) }} ·
                                                        {{ number_format($category['entryCount']) }} {{ str('entry')->plural($category['entryCount']) }}
                                                    </div>
                                                </div>

                                                @if ($category['winnerCount'] > 0)
                                                    <span class="sb-award-status sb-award-status--winner">Winner listed</span>
                                                @endif
                                            </div>

                                            <div class="mt-4 space-y-3">
                                                @foreach ($category['entries'] as $entry)
                                                    <div class="sb-award-entry-row {{ $entry['isWinner'] ? 'sb-award-entry-row--winner' : 'sb-award-entry-row--nominee' }}">
                                                        <div class="flex items-start gap-3">
                                                            <span class="sb-award-status {{ $entry['isWinner'] ? 'sb-award-status--winner' : 'sb-award-status--nominee' }}">
                                                                @if ($entry['isWinner'])
                                                                    <x-ui.icon name="trophy" class="size-3" />
                                                                @endif
                                                                {{ $entry['statusLabel'] }}
                                                            </span>

                                                            <div class="min-w-0 space-y-1">
                                                                @if ($entry['href'])
                                                                    <a href="{{ $entry['href'] }}" class="sb-award-entry-title">
                                                                        {{ $entry['label'] }}
                                                                    </a>
                                                                @else
                                                                    <div class="sb-award-entry-title">{{ $entry['label'] }}</div>
                                                                @endif

                                                                @if ($entry['meta'])
                                                                    <div class="sb-award-entry-meta">{{ $entry['meta'] }}</div>
                                                                @endif

                                                                @if ($entry['creditedAs'])
                                                                    <div class="sb-award-entry-credit">Credited as {{ $entry['creditedAs'] }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </section>
                                    @endforeach
                                </div>
                            </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </section>
@endsection
