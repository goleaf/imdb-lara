@extends('layouts.public')

@section('title', $user->name)
@section('meta_description', $user->bio ?: 'Browse public ratings, reviews, watchlists, and custom lists from '.$user->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $user->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-seo.pagination-links :paginator="$publicLists" />

        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <x-ui.card class="!max-w-none">
                <div class="space-y-5">
                    <div class="flex items-center gap-4">
                        <x-ui.avatar
                            :src="$user->avatar_url"
                            :name="$user->name"
                            circle
                            size="xl"
                            class="ring-4 ring-white shadow-sm dark:ring-neutral-900"
                        />

                        <div class="space-y-2">
                            <div>
                                <x-ui.heading level="h1" size="xl">{{ $user->name }}</x-ui.heading>
                                <div class="inline-flex items-center gap-1.5 text-sm text-neutral-500 dark:text-neutral-400">
                                    <x-ui.icon name="at-symbol" class="size-4" />
                                    <span>{{ '@'.$user->username }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge variant="outline" color="neutral" icon="calendar-days">Member since {{ $user->created_at->format('M Y') }}</x-ui.badge>
                                <x-ui.badge variant="outline" color="neutral" icon="globe-alt">Profile public</x-ui.badge>
                                <x-ui.badge variant="outline" color="neutral" :icon="$publicWatchlist ? 'bookmark' : 'lock-closed'">
                                    {{ $publicWatchlist ? 'Public watchlist' : 'Watchlist private' }}
                                </x-ui.badge>
                                <x-ui.badge variant="outline" color="neutral" :icon="$user->showsRatingsOnProfile() ? 'star' : 'lock-closed'">
                                    {{ $user->showsRatingsOnProfile() ? 'Ratings visible' : 'Ratings private' }}
                                </x-ui.badge>
                            </div>
                        </div>
                    </div>

                    <x-ui.text class="text-base text-neutral-600 dark:text-neutral-300">
                        {{ $user->bio ?: 'Public curator profile powered by visible lists, published reviews, and rating activity.' }}
                    </x-ui.text>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="queue-list" class="size-4" />
                                <span>Public lists</span>
                            </div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format($user->public_lists_count) }}</div>
                        </div>

                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="chat-bubble-left-right" class="size-4" />
                                <span>Published reviews</span>
                            </div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format($user->published_reviews_count) }}</div>
                        </div>

                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="star" class="size-4" />
                                <span>Ratings</span>
                            </div>
                            <div class="mt-2 text-2xl font-semibold">
                                {{ $user->showsRatingsOnProfile() ? number_format($user->ratings_count) : 'Private' }}
                            </div>
                        </div>

                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="bookmark" class="size-4" />
                                <span>Watchlist</span>
                            </div>
                            <div class="mt-2 text-2xl font-semibold">
                                {{ $publicWatchlist ? number_format($publicWatchlist->items_count) : 'Private' }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="chat-bubble-left-right" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Recent Reviews</span>
                            </x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Published commentary from this member’s public profile.
                            </x-ui.text>
                        </div>

                        <x-ui.badge variant="outline" color="neutral" icon="eye">{{ number_format($recentReviews->count()) }} loaded</x-ui.badge>
                    </div>

                    <div class="space-y-3">
                        @forelse ($recentReviews as $review)
                            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-2">
                                        <div class="font-medium">
                                            <a href="{{ route('public.titles.show', $review->title) }}" class="hover:opacity-80">
                                                {{ $review->headline ?: $review->title->name }}
                                            </a>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                            <span class="inline-flex items-center gap-1.5">
                                                <x-ui.icon name="film" class="size-4" />
                                                <span>{{ $review->title->name }}</span>
                                            </span>
                                            <span>·</span>
                                            <span>{{ $review->published_at?->format('M j, Y') }}</span>
                                            @if (isset($review->helpful_votes_count))
                                                <span>·</span>
                                                <span>{{ number_format($review->helpful_votes_count) }} helpful</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <x-ui.text class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ str($review->body)->limit(220) }}
                                </x-ui.text>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="chat-bubble-left-right" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No recent published reviews.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                        <x-ui.icon name="star" class="size-5 text-neutral-500 dark:text-neutral-400" />
                        <span>Recent ratings</span>
                    </x-ui.heading>
                    @if ($user->showsRatingsOnProfile())
                        <x-ui.badge variant="outline" color="neutral" icon="star">{{ number_format($user->ratings_count) }} ratings</x-ui.badge>
                    @else
                        <x-ui.badge variant="outline" color="neutral" icon="lock-closed">Private</x-ui.badge>
                    @endif
                </div>

                @if ($user->showsRatingsOnProfile())
                    @if ($recentRatings->isNotEmpty())
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($recentRatings as $rating)
                                <x-ui.card class="!max-w-none h-full">
                                    <div class="flex h-full flex-col gap-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="space-y-1">
                                                <x-ui.heading level="h3" size="md">
                                                    <a href="{{ route('public.titles.show', $rating->title) }}" class="hover:opacity-80">
                                                        {{ $rating->title->name }}
                                                    </a>
                                                </x-ui.heading>
                                                <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ str($rating->title->title_type->value)->headline() }}
                                                    @if ($rating->title->release_year)
                                                        · {{ $rating->title->release_year }}
                                                    @endif
                                                </x-ui.text>
                                            </div>

                                            <x-ui.badge variant="outline" color="neutral" icon="star">{{ $rating->score }}/10</x-ui.badge>
                                        </div>

                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ str($rating->title->plot_outline)->limit(140) }}
                                        </x-ui.text>

                                        <x-ui.text class="mt-auto text-xs uppercase tracking-[0.2em] text-neutral-400 dark:text-neutral-500">
                                            Rated {{ $rating->created_at->diffForHumans() }}
                                        </x-ui.text>
                                    </div>
                                </x-ui.card>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="star" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No public ratings yet.</x-ui.heading>
                            <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                This member has not surfaced any rating activity on their profile yet.
                            </x-ui.text>
                        </x-ui.empty>
                    @endif
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="lock-closed" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">Ratings are private on this profile.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Lists, reviews, and watchlist sharing continue to follow their own visibility settings.
                        </x-ui.text>
                    </x-ui.empty>
                @endif
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                        <x-ui.icon name="bookmark" class="size-5 text-neutral-500 dark:text-neutral-400" />
                        <span>Watchlist visibility</span>
                    </x-ui.heading>

                    @if ($publicWatchlist)
                        <x-ui.link :href="route('public.lists.show', [$user, $publicWatchlist])" variant="ghost" iconAfter="arrow-right">
                            Open watchlist
                        </x-ui.link>
                    @else
                        <x-ui.badge variant="outline" color="neutral" icon="lock-closed">Private</x-ui.badge>
                    @endif
                </div>

                @if ($publicWatchlist)
                    <x-ui.card class="!max-w-none">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <x-ui.heading level="h3" size="md">
                                        <a href="{{ route('public.lists.show', [$user, $publicWatchlist]) }}" class="hover:opacity-80">
                                            {{ $publicWatchlist->name }}
                                        </a>
                                    </x-ui.heading>
                                    <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                                        {{ $publicWatchlist->description ?: 'A public snapshot of what this member plans to watch next.' }}
                                    </x-ui.text>
                                </div>

                                <x-ui.badge variant="outline" color="neutral" icon="bookmark">
                                    {{ number_format($publicWatchlist->items_count) }} saved
                                </x-ui.badge>
                            </div>

                            @if ($publicWatchlist->items->isNotEmpty())
                                <div class="grid gap-4 md:grid-cols-2">
                                    @foreach ($publicWatchlist->items as $item)
                                        <x-catalog.title-card :title="$item->title" :show-summary="false" />
                                    @endforeach
                                </div>
                            @else
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="bookmark" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">No titles saved yet.</x-ui.heading>
                                </x-ui.empty>
                            @endif
                        </div>
                    </x-ui.card>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="lock-closed" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">Watchlist is private on this profile.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            This member keeps their watchlist sharing turned off.
                        </x-ui.text>
                    </x-ui.empty>
                @endif
            </section>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                    <x-ui.icon name="queue-list" class="size-5 text-neutral-500 dark:text-neutral-400" />
                    <span>Public Lists</span>
                </x-ui.heading>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.badge variant="outline" color="neutral" icon="eye">{{ number_format($publicLists->total()) }} visible</x-ui.badge>
                    <x-ui.link :href="route('public.lists.index')" variant="ghost" iconAfter="arrow-right">
                        Browse all public lists
                    </x-ui.link>
                </div>
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
                                <x-ui.badge variant="outline" color="neutral" icon="queue-list">{{ number_format($list->items_count) }} titles</x-ui.badge>
                                <x-ui.link :href="route('public.lists.show', [$user, $list])" variant="ghost" iconAfter="arrow-right">
                                    View list
                                </x-ui.link>
                            </div>
                        </div>
                    </x-ui.card>
                @empty
                    <div class="md:col-span-2 xl:col-span-3">
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                            <x-ui.empty.media>
                                <x-ui.icon name="queue-list" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No public lists are visible for this profile.</x-ui.heading>
                        </x-ui.empty>
                    </div>
                @endforelse
            </div>

            <div>
                {{ $publicLists->links() }}
            </div>
        </section>
    </section>
@endsection
