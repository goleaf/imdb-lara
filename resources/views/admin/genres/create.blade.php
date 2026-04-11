@extends('layouts.admin')

@section('title', 'Create Genre')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.genres.index')">Manage Genres</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Create</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Create Genre</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Add a new browse taxonomy entry for titles and landing pages.
                </x-ui.text>
            </div>
        </div>

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.genres.index')"
                back-label="Back to genres"
                heading="Genre Writes Paused"
                description="Genre creation is disabled while Screenbase is running in catalog-only mode."
            >
                Add or reconcile taxonomy entries through the remote catalog sync instead of the retired local admin form.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form method="POST" action="{{ route('admin.genres.store') }}" class="space-y-6">
                    @csrf

                    @include('admin.genres._form')

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="plus">
                            Create genre
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    </section>
@endsection
