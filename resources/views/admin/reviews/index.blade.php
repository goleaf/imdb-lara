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

        <x-ui.card class="!max-w-none">
            <form method="GET" action="{{ route('admin.reviews.index') }}" class="grid gap-4 xl:grid-cols-[220px,220px,auto,1fr,auto] xl:items-end">
                <x-ui.field>
                    <x-ui.label>Review status</x-ui.label>
                    <x-ui.native-select name="status">
                        <option value="all" @selected(($reviewFilters['status'] ?? 'pending') === 'all')>All statuses</option>
                        @foreach ($reviewStatuses as $status)
                            <option value="{{ $status->value }}" @selected(($reviewFilters['status'] ?? 'pending') === $status->value)>
                                {{ str($status->value)->headline() }}
                            </option>
                        @endforeach
                    </x-ui.native-select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Sort queue</x-ui.label>
                    <x-ui.native-select name="sort">
                        <option value="flagged" @selected(($reviewFilters['sort'] ?? 'flagged') === 'flagged')>Flagged first</option>
                        <option value="helpful" @selected(($reviewFilters['sort'] ?? 'flagged') === 'helpful')>Most helpful</option>
                        <option value="oldest" @selected(($reviewFilters['sort'] ?? 'flagged') === 'oldest')>Oldest first</option>
                    </x-ui.native-select>
                </x-ui.field>

                <div class="flex items-center xl:min-h-10">
                    <x-ui.checkbox
                        name="flaggedOnly"
                        value="1"
                        :checked="$reviewFilters['flaggedOnly'] ?? false"
                        label="Flagged only"
                    />
                </div>

                <x-ui.description class="xl:pb-2">
                    Surface open reports first and narrow the queue when moderators need to act on flagged reviews quickly.
                </x-ui.description>

                <div class="flex flex-wrap gap-2 xl:justify-end">
                    <x-ui.button as="a" :href="route('admin.reviews.index')" variant="ghost" icon="arrow-path">
                        Reset
                    </x-ui.button>
                    <x-ui.button type="submit" icon="funnel">
                        Apply
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="grid gap-4">
            @forelse ($reviews as $review)
                <x-ui.card @class([
                    '!max-w-none',
                    'border-red-200/70 dark:border-red-500/40' => $review->open_reports_count > 0,
                ])>
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
                                @if ($review->open_reports_count > 0)
                                    <x-ui.badge variant="outline" color="red" icon="flag">
                                        {{ number_format($review->open_reports_count) }} open reports
                                    </x-ui.badge>
                                @endif
                                @if ($review->published_at)
                                    <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $review->published_at->format('M j, Y') }}</x-ui.badge>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="grid gap-4 md:grid-cols-[220px,minmax(0,1fr),auto] md:items-end">
                            @csrf
                            @method('PATCH')
                            <x-ui.field>
                                <x-ui.label>Review status</x-ui.label>
                                <x-ui.native-select name="status">
                                    @foreach ($reviewStatuses as $status)
                                        <option value="{{ $status->value }}" @selected($review->status === $status)>
                                            {{ str($status->value)->headline() }}
                                        </option>
                                    @endforeach
                                </x-ui.native-select>
                            </x-ui.field>

                            <x-ui.field>
                                <x-ui.label>Moderation notes</x-ui.label>
                                <x-ui.input name="moderation_notes" placeholder="Moderation notes" />
                            </x-ui.field>

                            <div class="flex items-end">
                                <x-ui.button type="submit" icon="check-circle">
                                    Update
                                </x-ui.button>
                            </div>
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
