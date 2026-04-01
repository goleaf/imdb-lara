@extends('layouts.admin')

@section('title', 'Moderate Reviews')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Moderate Reviews</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Moderate Reviews</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Monitor review state, author attribution, and title context across the moderation queue.
            </x-ui.text>
        </div>

        <div class="grid gap-4">
            @forelse ($reviews as $review)
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-2">
                                <x-ui.heading level="h3" size="md">{{ $review->headline ?: 'Untitled review' }}</x-ui.heading>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $review->author->name }} on
                                    <a href="{{ route('public.titles.show', $review->title) }}" class="hover:opacity-80">
                                        {{ $review->title->name }}
                                    </a>
                                </div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ number_format($review->helpful_votes_count) }} helpful votes · {{ number_format($review->open_reports_count) }} open reports
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge variant="outline" color="neutral" icon="chat-bubble-left-right">{{ str($review->status->value)->headline() }}</x-ui.badge>
                                @if ($review->published_at)
                                    <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $review->published_at->format('M j, Y') }}</x-ui.badge>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="grid gap-4 md:grid-cols-[220px,1fr,auto]">
                            @csrf
                            @method('PATCH')
                            <select
                                name="status"
                                class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                            >
                                @foreach ($reviewStatuses as $status)
                                    <option value="{{ $status->value }}" @selected($review->status === $status)>
                                        {{ str($status->value)->headline() }}
                                    </option>
                                @endforeach
                            </select>

                            <x-ui.input name="moderation_notes" placeholder="Moderation notes" />

                            <x-ui.button type="submit" icon="check-circle">
                                Update
                            </x-ui.button>
                        </form>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.empty.media>
                        <x-ui.icon name="chat-bubble-left-right" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No reviews are available for moderation.</x-ui.heading>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $reviews->links() }}
        </div>
    </section>
@endsection
