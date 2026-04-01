@extends('layouts.public')

@section('title', $user->name)
@section('meta_description', $user->bio ?: 'Browse public lists and recent reviews from '.$user->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $user->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="grid gap-6 xl:grid-cols-[minmax(0,0.75fr)_minmax(0,1.25fr)]">
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="flex size-16 items-center justify-center rounded-full bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900">
                        <x-ui.icon name="user" class="size-8" />
                    </div>

                    <div>
                        <x-ui.heading level="h1" size="xl">{{ $user->name }}</x-ui.heading>
                        <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ '@'.$user->username }}</div>
                    </div>
                </div>

                <x-ui.text class="text-base text-neutral-600 dark:text-neutral-300">
                    {{ $user->bio ?: 'Public curator profile powered by visible lists and published reviews.' }}
                </x-ui.text>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Public lists</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format($publicLists->total()) }}</div>
                    </div>
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Recent reviews</div>
                        <div class="mt-2 text-2xl font-semibold">{{ number_format($recentReviews->count()) }}</div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <x-ui.heading level="h2" size="lg">Recent Reviews</x-ui.heading>
                    <x-ui.badge variant="outline" color="neutral">Public activity</x-ui.badge>
                </div>

                <div class="grid gap-3">
                    @forelse ($recentReviews as $review)
                        <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium">
                                        <a href="{{ route('public.titles.show', $review->title) }}" class="hover:opacity-80">
                                            {{ $review->headline ?: $review->title->name }}
                                        </a>
                                    </div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $review->title->name }} · {{ $review->published_at?->format('M j, Y') }}
                                    </div>
                                </div>
                            </div>
                            <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ str($review->body)->limit(220) }}
                            </x-ui.text>
                        </div>
                    @empty
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.heading level="h3">No recent published reviews.</x-ui.heading>
                        </x-ui.empty>
                    @endforelse
                </div>
            </div>
        </x-ui.card>
    </section>

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <x-ui.heading level="h2" size="lg">Public Lists</x-ui.heading>
            <x-ui.badge variant="outline" color="neutral">{{ number_format($publicLists->total()) }} visible</x-ui.badge>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($publicLists as $list)
                <x-ui.card class="!max-w-none h-full">
                    <div class="flex h-full flex-col gap-3">
                        <div class="space-y-2">
                            <x-ui.heading level="h3" size="md">
                                <a href="{{ route('public.lists.show', [$user, $list]) }}" class="hover:opacity-80">
                                    {{ $list->name }}
                                </a>
                            </x-ui.heading>
                            <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                {{ $list->description ?: 'A public Screenbase list.' }}
                            </x-ui.text>
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-3">
                            <x-ui.badge variant="outline" color="neutral">{{ number_format($list->items_count) }} titles</x-ui.badge>
                            <x-ui.link :href="route('public.lists.show', [$user, $list])" variant="ghost">
                                View list
                            </x-ui.link>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <div class="md:col-span-2 xl:col-span-3">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.heading level="h3">No public lists are visible for this profile.</x-ui.heading>
                    </x-ui.empty>
                </div>
            @endforelse
        </div>

        <div>
            {{ $publicLists->links() }}
        </div>
    </section>
@endsection
