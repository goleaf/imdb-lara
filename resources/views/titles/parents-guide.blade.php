@extends('layouts.public')

@section('title', $title->name.' Parents Guide')
@section('meta_description', 'Read the parents guide for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $title)">{{ $title->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Parents Guide</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero sb-parents-hero !max-w-none overflow-hidden p-0" data-slot="title-parents-hero">
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
                            <x-ui.icon name="shield-check" class="size-9" />
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="sb-page-kicker">Responsible Viewing</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Parents Guide</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl text-base">
                            Structured content concerns, severity context, and spoiler-aware notes for {{ $title->name }}, presented in a calmer archival layout.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" color="neutral" icon="shield-check">{{ number_format($advisoryCount) }} concern sections</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate" icon="chat-bubble-left-ellipsis">{{ number_format($documentedVoteCount) }} recorded votes</x-ui.badge>
                        <x-ui.badge variant="outline" color="amber" icon="eye-slash">{{ number_format($spoilerCount) }} spoiler notes</x-ui.badge>
                    </div>
                </div>

                <div class="sb-parents-hero-panel">
                    <div class="space-y-3">
                        <div>
                            <div class="sb-parents-panel-kicker">Severity Snapshot</div>
                            <div class="sb-parents-panel-copy">
                                Each concern is separated by category so families can scan quickly without wading through the full title dossier.
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            @forelse ($severitySummary as $summary)
                                <div class="sb-parent-summary-card sb-parent-summary-card--{{ $summary['color'] }}">
                                    <div class="sb-parent-summary-label">{{ $summary['label'] }}</div>
                                    <div class="sb-parent-summary-value">{{ number_format($summary['count']) }}</div>
                                    <div class="sb-parent-summary-copy">Documented {{ str('concern')->plural($summary['count']) }} in this severity band.</div>
                                </div>
                            @empty
                                <div class="sb-parent-summary-card sb-parent-summary-card--neutral">
                                    <div class="sb-parent-summary-label">Advisories</div>
                                    <div class="sb-parent-summary-value">0</div>
                                    <div class="sb-parent-summary-copy">No structured guidance is attached to this title yet.</div>
                                </div>
                            @endforelse
                        </div>

                        @if ($title->age_rating)
                            <x-ui.badge color="amber" icon="shield-check">{{ $title->age_rating }}</x-ui.badge>
                        @endif

                        <x-ui.link :href="route('public.titles.show', $title)" variant="ghost" iconAfter="arrow-right">
                            Back to title page
                        </x-ui.link>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.08fr)_minmax(18rem,0.92fr)]">
            <x-ui.card class="sb-detail-section sb-parents-shell !max-w-none p-5 sm:p-6" data-slot="title-parent-advisories">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Content Concerns</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Category-by-category advisory notes with severity context and any recorded vote signal.
                            </x-ui.text>
                        </div>

                        <x-ui.badge variant="outline" color="neutral" icon="exclamation-triangle">
                            {{ number_format($advisoryCount) }} documented sections
                        </x-ui.badge>
                    </div>

                    @if ($advisorySections->isNotEmpty())
                        <div class="space-y-4">
                            @foreach ($advisorySections as $advisorySection)
                                <section class="sb-parent-advisory-card sb-parent-advisory-card--{{ $advisorySection['severityColor'] }}">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="sb-parent-advisory-kicker">Content Concern</div>
                                            <x-ui.heading level="h3" size="md" class="sb-parent-advisory-title">
                                                {{ $advisorySection['category'] }}
                                            </x-ui.heading>
                                        </div>

                                        <x-ui.badge variant="outline" :color="$advisorySection['severityColor']" icon="exclamation-triangle">
                                            {{ $advisorySection['severityLabel'] }}
                                        </x-ui.badge>
                                    </div>

                                    <div class="sb-parent-vote-shell">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="sb-parent-vote-label">Vote Breakdown</div>
                                            <div class="sb-parent-vote-copy">{{ $advisorySection['voteSplitLabel'] }}</div>
                                        </div>

                                        @if ($advisorySection['consensus'] !== null)
                                            <div class="sb-parent-vote-bar" aria-hidden="true">
                                                <span class="sb-parent-vote-bar-fill" style="width: {{ $advisorySection['consensus'] }}%;"></span>
                                            </div>
                                            <div class="sb-parent-vote-footnote">
                                                {{ $advisorySection['consensus'] }}% dominant signal across recorded votes.
                                            </div>
                                        @elseif ($advisorySection['totalVotes'] !== null)
                                            <div class="sb-parent-vote-footnote">
                                                {{ number_format($advisorySection['totalVotes']) }} vote{{ $advisorySection['totalVotes'] === 1 ? '' : 's' }} recorded for this category.
                                            </div>
                                        @else
                                            <div class="sb-parent-vote-footnote">
                                                Vote breakdown is not available in the current payload for this category.
                                            </div>
                                        @endif
                                    </div>

                                    <x-ui.text class="sb-parent-advisory-copy">
                                        {{ $advisorySection['text'] }}
                                    </x-ui.text>

                                    @if ($advisorySection['reviewCount'] > 0)
                                        <div class="sb-parent-advisory-meta">
                                            {{ number_format($advisorySection['reviewCount']) }} supporting note{{ $advisorySection['reviewCount'] === 1 ? '' : 's' }} attached.
                                        </div>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="shield-check" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Parents guide detail is still pending.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                This page is ready for structured advisory imports, but this title does not yet carry content concern records beyond its certification.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="sb-detail-section sb-parents-side-shell !max-w-none p-5 sm:p-6" data-slot="title-parent-certificates">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Certification</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Regional rating signals and attribute notes when a certificate payload exists.
                                </x-ui.text>
                            </div>

                            @if ($title->age_rating)
                                <x-ui.badge color="amber" icon="shield-check">{{ $title->age_rating }}</x-ui.badge>
                            @endif
                        </div>

                        @if ($certificateItems->isNotEmpty())
                            <div class="space-y-3">
                                @foreach ($certificateItems as $certificateItem)
                                    <div class="sb-parent-certificate-row">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="sb-parent-certificate-rating">{{ $certificateItem['rating'] }}</div>
                                            @if ($certificateItem['country'])
                                                <div class="sb-parent-certificate-country">{{ $certificateItem['country'] }}</div>
                                            @endif
                                        </div>

                                        @if ($certificateItem['attributes'])
                                            <div class="sb-parent-certificate-copy">{{ $certificateItem['attributes'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                No certificate breakdown has been published yet for this title.
                            </x-ui.text>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card class="sb-detail-section sb-parents-side-shell !max-w-none p-5 sm:p-6" data-slot="title-parent-spoilers">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Spoiler Notes</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Hidden or late-story concerns separated from the main category sections.
                            </x-ui.text>
                        </div>

                        @if ($spoilerItems->isNotEmpty())
                            <div class="space-y-3">
                                @foreach ($spoilerItems as $spoiler)
                                    <div class="sb-parent-spoiler-row">
                                        {{ $spoiler }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                No spoiler-specific notes are attached to this parents guide yet.
                            </x-ui.text>
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </div>
    </section>
@endsection
