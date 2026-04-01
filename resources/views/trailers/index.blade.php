@extends('layouts.public')

@section('title', 'Latest Trailers')
@section('meta_description', 'Watch the freshest public trailers, clips, and featurettes added to Screenbase titles.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Latest Trailers</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-3">
                    <x-ui.heading level="h1" size="xl">Latest Trailers</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-base text-neutral-600 dark:text-neutral-300">
                        A chronological feed of the most recently published trailers, clips, and featurettes attached to public title pages.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline">Trailer feed</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral">Title-linked</x-ui.badge>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-2">
            @forelse ($titles as $title)
                @php
                    $poster = $title->mediaAssets->first();
                    $trailer = $title->titleVideos->first();
                @endphp

                <x-ui.card class="!max-w-none">
                    <div class="grid gap-4 md:grid-cols-[9rem_minmax(0,1fr)]">
                        <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                            @if ($poster)
                                <img
                                    src="{{ $poster->url }}"
                                    alt="{{ $poster->alt_text ?: $title->name }}"
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
                                <x-ui.badge variant="outline">{{ str($title->title_type->value)->headline() }}</x-ui.badge>
                                @if ($title->release_year)
                                    <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}">
                                        <x-ui.badge variant="outline" color="slate">{{ $title->release_year }}</x-ui.badge>
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
                                    {{ $trailer?->caption ?: $title->plot_outline ?: 'No public trailer copy is attached yet.' }}
                                </x-ui.text>
                            </div>

                            <div class="flex flex-wrap gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                @if ($trailer?->provider)
                                    <span>{{ str($trailer->provider)->headline() }}</span>
                                @endif
                                @if ($trailer?->published_at)
                                    <span>{{ $trailer->published_at->format('M j, Y') }}</span>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="outline" icon="film">
                                    View title
                                </x-ui.button>
                                @if (filled($trailer?->url))
                                    <x-ui.link :href="$trailer->url" open-in-new-tab variant="ghost">
                                        Open trailer
                                    </x-ui.link>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.heading level="h3">No public trailers are available yet.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        As trailer records are attached to titles, they will appear here in reverse chronological order.
                    </x-ui.text>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $titles->links() }}
        </div>
    </section>
@endsection
