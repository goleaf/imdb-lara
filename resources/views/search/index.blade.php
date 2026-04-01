@extends('layouts.public')

@section('title', 'Search')
@section('meta_description', 'Run advanced title discovery across keywords, genre, title type, and minimum ratings.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Search</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="space-y-3">
                <x-ui.heading level="h1" size="xl">Search</x-ui.heading>
                <x-ui.text class="max-w-2xl text-base text-neutral-600 dark:text-neutral-300">
                    Use full-text discovery filters to narrow titles by keyword, genre, title type, rating floor, or sort strategy.
                </x-ui.text>
            </div>
        </x-ui.card>

        <livewire:search.discovery-filters />
    </section>
@endsection
