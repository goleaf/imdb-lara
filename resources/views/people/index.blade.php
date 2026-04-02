@extends('layouts.public')

@section('title', 'Browse People')
@section('meta_description', 'Browse actors, directors, writers, and other creators in the Screenbase catalog.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Browse People</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero !max-w-none p-6 sm:p-7" data-slot="browse-people-hero">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                <div class="space-y-4">
                    <div class="sb-page-kicker">Directory</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Browse People</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl">
                            Public creator profiles with biography context, profession filters, known-for credits, and linked filmographies across the catalog.
                        </x-ui.text>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[22rem]">
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">People pages</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">Cast & crew</div>
                    </div>
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Discovery</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">Profile-first</div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="outline" icon="users">Actors</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral" icon="video-camera">Directors</x-ui.badge>
                    <x-ui.badge variant="outline" color="slate" icon="pencil-square">Writers</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral" icon="briefcase">Producers</x-ui.badge>
                </div>
            </div>
        </x-ui.card>

        <livewire:catalog.people-browser />
    </section>
@endsection
