@extends('layouts.account')

@section('title', 'Your Watchlist')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Your Watchlist</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Your Watchlist</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Titles saved for later viewing and personal tracking.
                </x-ui.text>
            </div>
            <x-ui.badge variant="outline" color="neutral">
                {{ number_format($watchlist->items->count()) }} saved
            </x-ui.badge>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($watchlist->items as $item)
                <x-catalog.title-card :title="$item->title" />
            @empty
                <div class="md:col-span-2 xl:col-span-3">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.heading level="h3">Your watchlist is empty.</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            Save titles from any title page to build a personal queue.
                        </x-ui.text>
                    </x-ui.empty>
                </div>
            @endforelse
        </div>
    </section>
@endsection
