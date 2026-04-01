@extends('layouts.public')

@section('title', 'Discovery')
@section('meta_description', 'Use Screenbase discovery filters to browse titles by genre, type, popularity, and rating.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Discovery</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.75fr)]">
                <div class="space-y-3">
                    <x-ui.heading level="h1" size="xl">Discovery</x-ui.heading>
                    <x-ui.text class="max-w-2xl text-base text-neutral-600 dark:text-neutral-300">
                        Explore the catalog with richer filters, popularity ranking, and rating thresholds. The index is optimized for public browse traffic and updated aggregate statistics.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($featuredGenres->take(8) as $genre)
                        <a href="{{ route('public.genres.show', $genre) }}">
                            <x-ui.badge variant="outline">{{ $genre->name }}</x-ui.badge>
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <livewire:search.discovery-filters />
    </section>
@endsection
