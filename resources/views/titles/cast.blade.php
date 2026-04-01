@extends('layouts.public')

@section('title', $title->name.' Full Cast')
@section('meta_description', 'Browse the full cast and crew list for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $title)">{{ $title->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Full Cast</x-ui.breadcrumbs.item>
@endsection

@section('content')
    @php
        $poster = $title->mediaAssets->first();
    @endphp

    <section class="space-y-6">
        <x-ui.card class="!max-w-none">
            <div class="grid gap-6 lg:grid-cols-[11rem_minmax(0,1fr)]">
                <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                    @if ($poster)
                        <img
                            src="{{ $poster->url }}"
                            alt="{{ $poster->alt_text ?: $title->name }}"
                            class="aspect-[2/3] w-full object-cover"
                        >
                    @else
                        <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                            <x-ui.icon name="film" class="size-12" />
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge variant="outline">{{ str($title->title_type->value)->headline() }}</x-ui.badge>
                        @if ($title->release_year)
                            <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}">
                                <x-ui.badge variant="outline" color="slate">{{ $title->release_year }}</x-ui.badge>
                            </a>
                        @endif
                        @if ($title->statistic?->average_rating)
                            <x-ui.badge icon="star" color="amber">
                                {{ number_format((float) $title->statistic->average_rating, 1) }}
                            </x-ui.badge>
                        @endif
                    </div>

                    <div>
                        <x-ui.heading level="h1" size="xl">{{ $title->name }} Full Cast & Crew</x-ui.heading>
                        <x-ui.text class="mt-2 text-base text-neutral-600 dark:text-neutral-300">
                            Complete credit list for the public title page, split between cast billing and crew roles.
                        </x-ui.text>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Cast credits</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format($castCount) }}</div>
                        </div>
                        <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Crew credits</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format($crewCount) }}</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="outline" icon="arrow-left">
                            Back to title
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Cast</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral">{{ number_format($castCount) }} total</x-ui.badge>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($castCredits as $credit)
                            <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium">
                                            <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                {{ $credit->person->name }}
                                            </a>
                                        </div>
                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $credit->character_name ?: ($credit->credited_as ?: 'Cast credit') }}
                                        </div>
                                    </div>

                                    @if ($credit->billing_order)
                                        <x-ui.badge variant="outline" color="neutral">
                                            #{{ $credit->billing_order }}
                                        </x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.heading level="h3">No cast credits are published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>

                    <div>
                        {{ $castCredits->links() }}
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Crew</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral">{{ number_format($crewCount) }} total</x-ui.badge>
                    </div>

                    <div class="grid gap-3">
                        @forelse ($crewCredits as $credit)
                            <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium">
                                            <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                {{ $credit->person->name }}
                                            </a>
                                        </div>
                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $credit->department }} · {{ $credit->job }}
                                        </div>

                                        @if ($credit->episode?->title && $credit->episode?->series && $credit->episode?->season)
                                            <div class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                                                Episode-specific:
                                                <a
                                                    href="{{ route('public.episodes.show', ['series' => $credit->episode->series, 'season' => $credit->episode->season, 'episode' => $credit->episode->title]) }}"
                                                    class="font-medium hover:opacity-80"
                                                >
                                                    {{ $credit->episode->title->name }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($credit->billing_order)
                                        <x-ui.badge variant="outline" color="slate">
                                            #{{ $credit->billing_order }}
                                        </x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.heading level="h3">No crew credits are published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>

                    <div>
                        {{ $crewCredits->links() }}
                    </div>
                </div>
            </x-ui.card>
        </div>
    </section>
@endsection
