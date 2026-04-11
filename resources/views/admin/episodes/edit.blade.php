@extends('layouts.admin')

@section('title', 'Edit Episode')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    @if ($catalogOnly)
        <x-ui.breadcrumbs.item>Episodes</x-ui.breadcrumbs.item>
    @else
        <x-ui.breadcrumbs.item :href="route('admin.seasons.edit', $episode->seasonRecord)">{{ $episode->seasonRecord->name }}</x-ui.breadcrumbs.item>
    @endif
    <x-ui.breadcrumbs.item>{{ $episode->title->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        @if (! $catalogOnly && session('status'))
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
                <form method="POST" action="{{ route('admin.episodes.update', $episode) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    @include('admin.episodes._form')

                    <div class="flex justify-end gap-3">
                        <x-ui.button as="a" :href="route('admin.seasons.edit', $episode->seasonRecord)" variant="ghost" icon="arrow-left">
                            Back to season
                        </x-ui.button>
                        <x-ui.button type="submit" icon="check-circle">
                            Save episode
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    </section>
@endsection
