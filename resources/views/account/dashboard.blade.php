@extends('layouts.account')

@section('title', 'Dashboard')
@section('meta_description', 'Manage your Screenbase profile, watchlist, ratings, reviews, and public presence.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Dashboard</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="!max-w-none overflow-hidden">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center gap-4">
                        <x-ui.avatar
                            :src="$user->avatar_url"
                            :name="$user->name"
                            circle
                            size="xl"
                            class="ring-4 ring-white shadow-sm dark:ring-neutral-900"
                        />

                        <div class="space-y-2">
                            <div>
                                <x-ui.heading level="h1" size="xl">Dashboard</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                                    Manage your profile, private tracking, and public curation from one place.
                                </x-ui.text>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge variant="outline" color="neutral" icon="at-symbol">{{ '@'.$user->username }}</x-ui.badge>
                                <x-ui.badge variant="outline" color="neutral" icon="calendar-days">Member since {{ $user->created_at->format('M Y') }}</x-ui.badge>
                                <x-ui.badge variant="outline" color="neutral" :icon="$watchlist->visibility->icon()">
                                    Watchlist {{ str($watchlist->visibility->value)->headline() }}
                                </x-ui.badge>
                            </div>
                        </div>
                    </div>

                    <x-ui.text class="max-w-3xl text-neutral-600 dark:text-neutral-300">
                        {{ $user->bio ?: 'Add a short curator bio and surface the parts of your Screenbase identity you want other people to see.' }}
                    </x-ui.text>

                    <div class="flex flex-wrap gap-3">
                        <x-ui.link :href="route('account.settings')" variant="ghost" iconAfter="arrow-right">
                            Profile settings
                        </x-ui.link>
                        <x-ui.link :href="route('account.watchlist')" variant="ghost" iconAfter="arrow-right">
                            Manage watchlist
                        </x-ui.link>
                        <x-ui.link :href="route('account.lists.index')" variant="ghost" iconAfter="arrow-right">
                            Open your lists
                        </x-ui.link>
                        @if ($publicProfileIsLive)
                            <x-ui.link :href="route('public.users.show', $user)" variant="ghost" iconAfter="arrow-up-right">
                                View public profile
                            </x-ui.link>
                        @endif
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-2">
                    <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                            <x-ui.icon name="bookmark" class="size-4" />
                            <span>Watchlist titles</span>
                        </div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($watchlist->items_count) }}</div>
                        <x-ui.text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ number_format($watchlist->watched_items_count) }} watched, {{ number_format($watchlist->planned_items_count) }} planned.
                        </x-ui.text>
                    </div>

                    <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                            <x-ui.icon name="star" class="size-4" />
                            <span>Ratings</span>
                        </div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($user->ratings_count) }}</div>
                        <x-ui.text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $user->showsRatingsOnProfile() ? 'Visible on your public profile.' : 'Hidden from your public profile.' }}
                        </x-ui.text>
                    </div>

                    <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                            <x-ui.icon name="chat-bubble-left-right" class="size-4" />
                            <span>Published reviews</span>
                        </div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($user->published_reviews_count) }}</div>
                        <x-ui.text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Recent review drafts and published posts are listed below.
                        </x-ui.text>
                    </div>

                    <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                            <x-ui.icon name="queue-list" class="size-4" />
                            <span>Lists</span>
                        </div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($user->custom_lists_count) }}</div>
                        <x-ui.text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ number_format($user->public_lists_count) }} public, plus your private curations.
                        </x-ui.text>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="sparkles" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Recent activity</span>
                            </x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Ratings, reviews, and watchlist changes ordered by your latest actions.
                            </x-ui.text>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @forelse ($recentActivity as $activityItem)
                            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="inline-flex items-center gap-2 text-sm font-medium">
                                            <x-ui.icon :name="$activityItem['icon']" class="size-4 text-neutral-500 dark:text-neutral-400" />
                                            <a href="{{ $activityItem['href'] }}" class="hover:opacity-80">
                                                {{ $activityItem['label'] }}
                                            </a>
                                        </div>
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ $activityItem['meta'] }}
                                        </x-ui.text>
                                    </div>

                                    <x-ui.text class="text-xs uppercase tracking-[0.2em] text-neutral-400 dark:text-neutral-500">
                                        {{ $activityItem['occurred_at']->diffForHumans() }}
                                    </x-ui.text>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="sparkles" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No activity yet.</x-ui.heading>
                                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                    Start rating, reviewing, or saving titles and this feed will populate.
                                </x-ui.text>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <livewire:account.profile-settings-panel lazy />
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="bookmark" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Watchlist summary</span>
                            </x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Track visibility and progress without leaving your account dashboard.
                            </x-ui.text>
                        </div>

                        <x-ui.link :href="route('account.watchlist')" variant="ghost" iconAfter="arrow-right">
                            Open watchlist
                        </x-ui.link>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Total</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format($watchlist->items_count) }}</div>
                        </div>
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Watched</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format($watchlist->watched_items_count) }}</div>
                        </div>
                        <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                            <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Watching now</div>
                            <div class="mt-2 text-2xl font-semibold">{{ number_format($watchlist->watching_items_count) }}</div>
                        </div>
                    </div>

                    @if ($watchlistPreviewItems->isEmpty())
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="bookmark" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Your watchlist is empty.</x-ui.heading>
                        </x-ui.empty>
                    @else
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($watchlistPreviewItems as $item)
                                <x-catalog.title-card :title="$item->title" :show-summary="false" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="queue-list" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Quick links</span>
                            </x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Jump back into the watchlist and your most recently updated collections.
                            </x-ui.text>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @forelse ($quickLinks as $list)
                            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ $list->name }}</div>
                                        <div class="flex flex-wrap gap-2 text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">
                                            <span>{{ $list->is_watchlist ? 'Watchlist' : 'Custom list' }}</span>
                                            <span>·</span>
                                            <span>{{ str($list->visibility->value)->headline() }}</span>
                                            <span>·</span>
                                            <span>{{ number_format($list->items_count) }} titles</span>
                                        </div>
                                    </div>

                                    <x-ui.link
                                        :href="$list->is_watchlist ? route('account.watchlist') : route('account.lists.show', $list)"
                                        variant="ghost"
                                        iconAfter="arrow-right"
                                    >
                                        Open
                                    </x-ui.link>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="queue-list" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No list shortcuts yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="star" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Recent ratings</span>
                            </x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Your latest scores with direct links back to the title page.
                            </x-ui.text>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @forelse ($recentRatings as $rating)
                            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="font-medium">
                                            <a href="{{ route('public.titles.show', $rating->title) }}" class="hover:opacity-80">
                                                {{ $rating->title->name }}
                                            </a>
                                        </div>
                                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ str($rating->title->title_type->value)->headline() }}
                                            @if ($rating->title->release_year)
                                                · {{ $rating->title->release_year }}
                                            @endif
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-2xl font-semibold">{{ $rating->score }}</div>
                                        <x-ui.text class="text-xs uppercase tracking-[0.2em] text-neutral-400 dark:text-neutral-500">
                                            {{ $rating->created_at->diffForHumans() }}
                                        </x-ui.text>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="star" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No ratings yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="chat-bubble-left-right" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Recent reviews</span>
                            </x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Drafts and published reviews stay visible here, with status shown inline.
                            </x-ui.text>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @forelse ($recentReviews as $review)
                            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium">
                                                <a href="{{ route('public.titles.show', $review->title) }}" class="hover:opacity-80">
                                                    {{ $review->headline ?: $review->title->name }}
                                                </a>
                                            </div>
                                            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                                {{ $review->title->name }}
                                            </x-ui.text>
                                        </div>

                                        <x-ui.badge variant="outline" color="neutral" icon="pencil-square">
                                            {{ str($review->status->value)->headline() }}
                                        </x-ui.badge>
                                    </div>

                                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ str($review->body)->limit(180) }}
                                    </x-ui.text>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="chat-bubble-left-right" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No reviews yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>
        </section>
    </section>
@endsection
