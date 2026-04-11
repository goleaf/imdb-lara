@extends('layouts.admin')

@section('title', 'Create Award Category')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.award-categories.index')">Manage Award Categories</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Create</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Create Award Category</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Add a new imported award category used by title nomination records.
                </x-ui.text>
            </div>
        </div>

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.award-categories.index')"
                back-label="Back to award categories"
                heading="Award Category Writes Paused"
                description="Award category creation is disabled while Screenbase is running in catalog-only mode."
            >
                Add or reconcile award categories through the remote catalog import pipeline instead of the retired local admin form.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form wire:submit="saveAwardCategory" class="space-y-6">
                    @include('admin.award-categories._form')

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="plus">
                            Create award category
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    </section>
@endsection
