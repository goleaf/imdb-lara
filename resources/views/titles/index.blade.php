@extends('layouts.public')

@section('title', 'Browse Titles')
@section('meta_description', 'Browse published titles across films, series, documentaries, and specials on Screenbase.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Browse Titles</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Browse Titles</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Published title records sorted alphabetically with aggregate statistics and genre coverage.
                </x-ui.text>
            </div>
            <x-ui.button as="a" :href="route('public.search')" variant="outline" icon="funnel">
                Refine results
            </x-ui.button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($titles as $title)
                <x-catalog.title-card :title="$title" />
            @empty
                <div class="md:col-span-2 xl:col-span-3">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.heading level="h3">No titles match the current scope.</x-ui.heading>
                    </x-ui.empty>
                </div>
            @endforelse
        </div>

        <div>
            {{ $titles->links() }}
        </div>
    </section>
@endsection
