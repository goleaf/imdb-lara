@extends('layouts.public')

@section('title', 'Latest Reviews')
@section('meta_description', 'Browse the latest published audience reviews across public Screenbase title pages.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Latest Reviews</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-3">
                    <x-ui.heading level="h1" size="xl">Latest Reviews</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-base text-neutral-600 dark:text-neutral-300">
                        The freshest published audience reviews across the public Screenbase catalog.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline">Community writing</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral">Published only</x-ui.badge>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-2">
            @forelse ($reviews as $review)
                @php
                    $poster = $review->title->mediaAssets->first();
                @endphp

                <x-ui.card class="!max-w-none">
                    <div class="grid gap-4 md:grid-cols-[8rem_minmax(0,1fr)]">
                        <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                            @if ($poster)
                                <img
                                    src="{{ $poster->url }}"
                                    alt="{{ $poster->alt_text ?: $review->title->name }}"
                                    class="aspect-[2/3] w-full object-cover"
                                >
                            @else
                                <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                    <x-ui.icon name="chat-bubble-left-right" class="size-10" />
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge variant="outline">{{ str($review->title->title_type->value)->headline() }}</x-ui.badge>
                                @if ($review->contains_spoilers)
                                    <x-ui.badge variant="outline" color="red">Spoilers</x-ui.badge>
                                @endif
                                @if ($review->published_at)
                                    <x-ui.badge variant="outline" color="slate">{{ $review->published_at->format('M j, Y') }}</x-ui.badge>
                                @endif
                            </div>

                            <div>
                                <x-ui.heading level="h2" size="lg">
                                    <a href="{{ route('public.titles.show', $review->title) }}" class="hover:opacity-80">
                                        {{ $review->headline ?: 'Member review for '.$review->title->name }}
                                    </a>
                                </x-ui.heading>
                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $review->author->name }} on {{ $review->title->name }}
                                </div>
                            </div>

                            <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                {{ str($review->body)->limit(260) }}
                            </x-ui.text>

                            <x-ui.link :href="route('public.titles.show', $review->title)" variant="ghost">
                                View title page
                            </x-ui.link>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.heading level="h3">No published reviews are available yet.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        Reviews will appear here once they pass moderation and become public.
                    </x-ui.text>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $reviews->links() }}
        </div>
    </section>
@endsection
