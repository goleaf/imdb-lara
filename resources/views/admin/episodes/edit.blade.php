@extends('layouts.admin')

@section('title', 'Edit Episode')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Episodes</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $episode->title->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Edit {{ $episode->title->name }}</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Update numbering, dates, publishing, and synopsis while preserving the season/series hierarchy.
                </x-ui.text>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($episode->season)
                    <x-ui.button as="a" :href="route('admin.seasons.edit', $episode->season)" variant="outline" icon="arrow-left">
                        Back to season
                    </x-ui.button>
                @endif
                @if (
                    $episode->series?->getRouteKey()
                    && $episode->season?->getRouteKey()
                    && $episode->title?->getRouteKey()
                )
                    <x-ui.button
                        as="a"
                        :href="route('public.episodes.show', [
                            'series' => $episode->series->getRouteKey(),
                            'season' => $episode->season->getRouteKey(),
                            'episode' => $episode->title->getRouteKey(),
                        ])"
                        variant="outline"
                        icon="arrow-top-right-on-square"
                    >
                        View public page
                    </x-ui.button>
                @endif
            </div>
        </div>

        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.dashboard')"
                back-label="Back to admin"
                heading="Episode Writes Paused"
                description="Episode metadata is currently read-only while Screenbase runs in catalog-only mode."
            >
                Episode numbering, air dates, production codes, and related credit edits must be synchronized from the upstream catalog source.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form wire:submit="saveEpisode" class="space-y-6">
                    @include('admin.episodes._form')

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="check">
                            Save changes
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="button" wire:click="deleteEpisode" variant="outline" color="red" icon="trash">
                    Delete episode
                </x-ui.button>
            </div>
        @endif
    </section>
@endsection
