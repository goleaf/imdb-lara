@extends('layouts.public')

@section('title', $title->name)
@section('meta_description', $title->plot_outline ?: 'Read credits, ratings, and reviews for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $title->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    @php
        $poster = $title->mediaAssets->first();
    @endphp

    <section class="grid gap-6 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
        <x-ui.card class="!max-w-none overflow-hidden">
            @if ($poster)
                <img
                    src="{{ $poster->url }}"
                    alt="{{ $poster->alt_text ?: $title->name }}"
                    class="aspect-[2/3] w-full rounded-box object-cover"
                >
            @else
                <div class="flex aspect-[2/3] items-center justify-center rounded-box bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                    <x-ui.icon name="film" class="size-12" />
                </div>
            @endif
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge variant="outline">{{ str($title->title_type->value)->headline() }}</x-ui.badge>
                        @if ($title->release_year)
                            <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}">
                                <x-ui.badge variant="outline" color="slate">{{ $title->release_year }}</x-ui.badge>
                            </a>
                        @endif
                        @if ($title->age_rating)
                            <x-ui.badge variant="outline" color="neutral">{{ $title->age_rating }}</x-ui.badge>
                        @endif
                        @if ($title->statistic?->average_rating)
                            <x-ui.badge icon="star" color="amber">
                                {{ number_format((float) $title->statistic->average_rating, 1) }}
                            </x-ui.badge>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <x-ui.heading level="h1" size="xl">{{ $title->name }}</x-ui.heading>

                        @if (filled($title->tagline))
                            <x-ui.text class="text-base italic text-neutral-500 dark:text-neutral-400">
                                {{ $title->tagline }}
                            </x-ui.text>
                        @endif

                        <x-ui.text class="text-base text-neutral-600 dark:text-neutral-300">
                            {{ $title->plot_outline ?: 'No plot outline has been published yet.' }}
                        </x-ui.text>
                    </div>

                    @if ($title->genres->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($title->genres as $genre)
                                <a href="{{ route('public.genres.show', $genre) }}">
                                    <x-ui.badge variant="outline" color="neutral">{{ $genre->name }}</x-ui.badge>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Ratings</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($title->statistic?->rating_count ?? 0)) }}</div>
                        </div>
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Reviews</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($title->statistic?->review_count ?? 0)) }}</div>
                        </div>
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Watchlists</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($title->statistic?->watchlist_count ?? 0)) }}</div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <div class="grid gap-4 lg:grid-cols-2">
                <livewire:titles.watchlist-toggle :title="$title" :key="'watchlist-'.$title->id" />
                <livewire:titles.rating-panel :title="$title" :key="'rating-'.$title->id" />
            </div>

            <livewire:titles.custom-list-picker :title="$title" :key="'custom-lists-'.$title->id" />

            @if ($title->seasons->isNotEmpty() || $title->titleVideos->isNotEmpty())
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        @if ($title->seasons->isNotEmpty())
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-4">
                                    <x-ui.heading level="h2" size="lg">Seasons</x-ui.heading>
                                    <x-ui.badge variant="outline" color="neutral">
                                        {{ number_format($title->seasons->count()) }} published
                                    </x-ui.badge>
                                </div>

                                <div class="grid gap-3">
                                    @foreach ($title->seasons as $season)
                                        <div class="flex flex-wrap items-start justify-between gap-3 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                            <div>
                                                <div class="font-medium">
                                                    <a href="{{ route('public.seasons.show', ['series' => $title, 'season' => $season]) }}" class="hover:opacity-80">
                                                        {{ $season->name }}
                                                    </a>
                                                </div>
                                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ number_format($season->episodes_count) }} episodes
                                                </div>
                                            </div>

                                            <x-ui.link :href="route('public.seasons.show', ['series' => $title, 'season' => $season])" variant="ghost">
                                                View season
                                            </x-ui.link>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($title->titleVideos->isNotEmpty())
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-4">
                                    <x-ui.heading level="h2" size="lg">Latest Videos</x-ui.heading>
                                    <x-ui.link :href="route('public.trailers.latest')" variant="ghost">
                                        Browse trailer feed
                                    </x-ui.link>
                                </div>

                                <div class="grid gap-3">
                                    @foreach ($title->titleVideos as $video)
                                        <div class="flex flex-wrap items-start justify-between gap-3 rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                            <div>
                                                <div class="font-medium">{{ $video->caption ?: str($video->kind->value)->headline() }}</div>
                                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ str($video->kind->value)->headline() }}
                                                    @if ($video->published_at)
                                                        · {{ $video->published_at->format('M j, Y') }}
                                                    @endif
                                                </div>
                                            </div>

                                            @if (filled($video->url))
                                                <x-ui.link :href="$video->url" open-in-new-tab variant="ghost">
                                                    Open video
                                                </x-ui.link>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            @endif
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <x-ui.heading level="h2" size="lg">Cast & crew</x-ui.heading>
                <div class="grid gap-3">
                    @forelse ($title->credits as $credit)
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
                                <x-ui.badge variant="outline" color="neutral">
                                    {{ $credit->character_name }}
                                </x-ui.badge>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.heading level="h3">Credits have not been published yet.</x-ui.heading>
                        </x-ui.empty>
                    @endforelse
                </div>
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card class="!max-w-none">
                <div class="space-y-3">
                    <x-ui.heading level="h2" size="lg">Production details</x-ui.heading>

                    @if ($title->companies->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($title->companies as $company)
                                <x-ui.badge variant="outline" color="neutral">
                                    {{ $company->name }}
                                </x-ui.badge>
                            @endforeach
                        </div>
                    @endif

                    @if (filled($title->synopsis))
                        <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                            {{ $title->synopsis }}
                        </x-ui.text>
                    @endif
                </div>
            </x-ui.card>

            <livewire:titles.review-composer :title="$title" :key="'review-'.$title->id" />
        </div>
    </section>

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <x-ui.heading level="h2" size="lg">Audience reviews</x-ui.heading>
            <x-ui.badge variant="outline" color="neutral">
                {{ number_format($title->reviews->count()) }} published
            </x-ui.badge>
        </div>

        <div class="grid gap-4">
            @forelse ($title->reviews as $review)
                <x-ui.card class="!max-w-none">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <x-ui.heading level="h3" size="md">
                                    {{ $review->headline ?: 'Member review' }}
                                </x-ui.heading>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $review->author->name }} · {{ $review->published_at?->format('M j, Y') ?? 'Pending publication date' }}
                                </div>
                            </div>

                            @if ($review->contains_spoilers)
                                <x-ui.badge color="red" variant="outline">Spoilers</x-ui.badge>
                            @endif
                        </div>

                        <x-ui.text class="text-neutral-700 dark:text-neutral-200">
                            {{ $review->body }}
                        </x-ui.text>

                        <div class="pt-1">
                            <livewire:reviews.report-review-form :review="$review" :key="'report-'.$review->id" />
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.heading level="h3">No published reviews yet.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        Be the first to add a review for this title.
                    </x-ui.text>
                </x-ui.empty>
            @endforelse
        </div>
    </section>
@endsection
