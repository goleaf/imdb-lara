@extends('layouts.account')

@section('title', 'Your Lists')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Your Lists</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Your Lists</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Build shareable or private collections for themes, moods, franchises, and rewatches.
            </x-ui.text>
        </div>

        <livewire:lists.create-list-form />

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($lists as $list)
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <x-ui.heading level="h3" size="md">{{ $list->name }}</x-ui.heading>
                                @if ($list->description)
                                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $list->description }}
                                    </x-ui.text>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge variant="outline" color="neutral">{{ str($list->visibility->value)->headline() }}</x-ui.badge>
                                <x-ui.badge variant="outline" color="slate">{{ number_format($list->items_count) }} titles</x-ui.badge>
                            </div>
                        </div>

                        @if ($list->items->isNotEmpty())
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ($list->items as $item)
                                    <x-catalog.title-card :title="$item->title" :show-summary="false" />
                                @endforeach
                            </div>
                        @endif

                        @if ($list->visibility === \App\ListVisibility::Public)
                            <div class="flex justify-end">
                                <x-ui.link :href="route('public.lists.show', [auth()->user(), $list])" variant="ghost">
                                    View public page
                                </x-ui.link>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900 lg:col-span-2">
                    <x-ui.heading level="h3">No custom lists yet.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                        Create a list above, then add titles from any title page.
                    </x-ui.text>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $lists->links() }}
        </div>
    </section>
@endsection
