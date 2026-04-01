@extends('layouts.public')

@section('title', $list->name)
@section('meta_description', $list->description ?: 'Browse the curated Screenbase list '.$list->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $owner->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $list->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <x-ui.heading level="h1" size="xl">{{ $list->name }}</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                            {{ $list->description ?: 'A curated collection published on Screenbase.' }}
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="outline" color="neutral">{{ $owner->name }}</x-ui.badge>
                        <x-ui.badge variant="outline" color="slate">{{ number_format($list->items_count) }} titles</x-ui.badge>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($list->items as $item)
                <x-catalog.title-card :title="$item->title" />
            @empty
                <div class="md:col-span-2 xl:col-span-3">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.heading level="h3">This list does not have any published titles yet.</x-ui.heading>
                    </x-ui.empty>
                </div>
            @endforelse
        </div>
    </section>
@endsection
