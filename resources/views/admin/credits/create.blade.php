@extends('layouts.admin')

@section('title', 'Create Credit')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Create Credit</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Create Credit</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Link a person to a title, role, profession, and optional episode.
            </x-ui.text>
        </div>

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.dashboard')"
                back-label="Back to admin"
                heading="Credit Linking Paused"
                description="Credit creation is disabled while Screenbase is running in catalog-only mode."
            >
                Use the upstream catalog sync to attach people, roles, and episode-specific credits.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form method="POST" action="{{ route('admin.credits.store') }}" class="space-y-6">
                    @csrf

                    @include('admin.credits._form')

                    <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                        Change the selected title or person and resubmit if you need refreshed episode or profession options.
                    </x-ui.text>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="plus">
                            Create credit
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    </section>
@endsection
