@extends('layouts.public')

@section('title', 'Trailers')
@section('meta_description', 'Browse trailer-linked titles, clips, and featurettes from the imported Screenbase catalog.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Trailers</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <x-seo.pagination-links :paginator="$titles" />

        <x-ui.card class="!max-w-none">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-3">
                    <x-ui.heading level="h1" size="xl">Trailers</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-base text-neutral-600 dark:text-neutral-300">
                        Browse titles that currently have trailer, clip, or featurette records attached in the imported catalog.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" icon="play">Trailer archive</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral" icon="film">Title-linked</x-ui.badge>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-2">
            @forelse ($titles as $title)
                <x-ui.card class="!max-w-none">
                    <div class="grid gap-4 md:grid-cols-[9rem_minmax(0,1fr)]">
                        <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                            @if ($title->preferredPoster())
                                <img
                                    src="{{ $title->preferredPoster()->url }}"
                                    alt="{{ $title->preferredPoster()->alt_text ?: $title->name }}"
                                    class="aspect-[2/3] w-full object-cover"
                                >
                            @else
                                <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                    <x-ui.icon name="play" class="size-10" />
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge icon="play" color="amber">Trailer</x-ui.badge>
                                <x-ui.badge variant="outline" icon="film">{{ str($title->title_type->value)->headline() }}</x-ui.badge>
                                @if ($title->release_year)
                                    <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}">
                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $title->release_year }}</x-ui.badge>
                                    </a>
                                @endif
                            </div>

                            <div>
                                <x-ui.heading level="h2" size="lg">
                                    <a href="{{ route('public.titles.show', $title) }}" class="hover:opacity-80">
                                        {{ $title->name }}
                                    </a>
                                </x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $title->preferredVideo()?->caption ?: $title->plot_outline ?: 'No public trailer copy is attached yet.' }}
                                </x-ui.text>
                            </div>

                            <div class="flex flex-wrap gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                @if ($title->preferredVideo()?->provider)
                                    <span>{{ str($title->preferredVideo()->provider)->headline() }}</span>
                                @endif
                                @if ($title->preferredVideo()?->published_at)
                                    <span>{{ $title->preferredVideo()->published_at->format('M j, Y') }}</span>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="outline" icon="film">
                                    View title
                                </x-ui.button>
                                @if (filled($title->preferredVideo()?->url))
                                    <x-ui.link :href="$title->preferredVideo()->url" open-in-new-tab variant="ghost" iconAfter="arrow-top-right-on-square">
                                        Open trailer
                                    </x-ui.link>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.empty.media>
                        <x-ui.icon name="play-circle" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No trailer-linked titles are available yet.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        Titles with imported trailer records will appear here as soon as the source catalog exposes them.
                    </x-ui.text>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $titles->links() }}
        </div>
    </section>
@endsection
