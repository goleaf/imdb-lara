@extends('layouts.admin')

@section('title', 'Create AKA Attribute')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.aka-attributes.index')">Manage AKA Attributes</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Create</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Create AKA Attribute</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Add a new imported alternate-title marker used across the catalog archive.
                </x-ui.text>
            </div>
        </div>

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.aka-attributes.index')"
                back-label="Back to AKA attributes"
                heading="AKA Attribute Writes Paused"
                description="AKA attribute creation is disabled while Screenbase is running in catalog-only mode."
            >
                Add or reconcile alternate-title markers through the remote catalog import pipeline instead of the retired local admin form.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form wire:submit="saveAkaAttribute" class="space-y-6">
                    @include('admin.aka-attributes._form')

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="plus">
                            Create AKA attribute
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    </section>
@endsection
