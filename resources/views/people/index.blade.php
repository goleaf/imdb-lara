@extends('layouts.public')

@section('title', 'Browse People')
@section('meta_description', 'Browse actors, directors, writers, and other creators in the Screenbase catalog.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Browse People</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <x-ui.card class="!max-w-none">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="outline">Actors</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral">Directors</x-ui.badge>
                    <x-ui.badge variant="outline" color="slate">Writers</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral">Producers</x-ui.badge>
                </div>

                <div>
                    <x-ui.heading level="h1" size="xl">Browse People</x-ui.heading>
                    <x-ui.text class="mt-1 max-w-3xl text-neutral-600 dark:text-neutral-300">
                        Public creator profiles with biography context, profession filters, known-for credits, and linked filmographies across the catalog.
                    </x-ui.text>
                </div>
            </div>
        </x-ui.card>

        <livewire:catalog.people-browser />
    </section>
@endsection
