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
            <div class="grid gap-4 xl:grid-cols-[220px,220px,auto,1fr,auto] xl:items-end">
                <x-ui.field>
                    <x-ui.label>Review status</x-ui.label>
                    <x-ui.native-select wire:model.live="status">
                        <option value="all">All statuses</option>
                        @foreach ($reviewStatuses as $status)
                            <option value="{{ $status->value }}">
                                {{ str($status->value)->headline() }}
                            </option>
                        @endforeach
                    </x-ui.native-select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Sort queue</x-ui.label>
                    <x-ui.native-select wire:model.live="sort">
                        <option value="flagged">Flagged first</option>
                        <option value="helpful">Most helpful</option>
                        <option value="oldest">Oldest first</option>
                    </x-ui.native-select>
                </x-ui.field>

                <div class="flex items-center xl:min-h-10">
                    <x-ui.checkbox
                        wire:model.live="flaggedOnly"
                        label="Flagged only"
                    />
                </div>

                <x-ui.description class="xl:pb-2">
                    Surface open reports first and narrow the queue when moderators need to act on flagged reviews quickly.
                </x-ui.description>

                <div class="flex flex-wrap gap-2 xl:justify-end">
                    <x-ui.button type="button" wire:click="resetFilters" variant="ghost" icon="arrow-path">
                        Reset
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4">
            @forelse ($reviews as $review)
                <livewire:admin.review-moderation-card :review="$review" :key="'admin-review-'.$review->id" />
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
