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

        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5" data-slot="people-directory-snapshot">
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <x-ui.heading level="h2" size="lg">Catalog footprint</x-ui.heading>
                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                            A quick read on the imported people directory currently available from the MySQL catalog.
                        </x-ui.text>
                    </div>

                    <x-ui.badge color="amber" icon="users">
                        {{ number_format($directorySnapshot['publishedPeopleCount']) }} published profiles
                    </x-ui.badge>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Published people</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($directorySnapshot['publishedPeopleCount']) }}</div>
                    </div>
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Award-linked profiles</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($directorySnapshot['awardLinkedPeopleCount']) }}</div>
                    </div>
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Credited profiles</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($directorySnapshot['creditedPeopleCount']) }}</div>
                    </div>
                    <div class="sb-page-stat p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">Browsable professions</div>
                        <div class="mt-2 text-2xl font-semibold text-[#f7f1e8]">{{ number_format($directorySnapshot['professionCount']) }}</div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Top professions</div>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($directorySnapshot['topProfessions'] as $profession)
                            <x-ui.badge variant="outline" color="neutral" icon="briefcase">
                                {{ $profession['name'] }} · {{ number_format($profession['peopleCount']) }}
                            </x-ui.badge>
                        @empty
                            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                Profession rows are not available in the imported sample yet.
                            </x-ui.text>
                        @endforelse
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
