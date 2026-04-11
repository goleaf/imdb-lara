@extends('layouts.admin')

@section('title', 'Edit '.$akaAttribute->resolvedLabel())

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.aka-attributes.index')">Manage AKA Attributes</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $akaAttribute->resolvedLabel() }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Edit AKA Attribute</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Update the lookup row that powers the public alternate-title archive.
                </x-ui.text>
            </div>

            <x-ui.badge variant="outline" color="slate" icon="queue-list">
                {{ number_format($akaAttribute->movieAkaUsageCount()) }} linked AKA rows
            </x-ui.badge>
        </div>

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.aka-attributes.index')"
                back-label="Back to AKA attributes"
                heading="AKA Attribute Edits Paused"
                description="AKA attribute updates and deletes stay paused while the remote catalog remains the source of truth."
            >
                This page remains routed through Livewire, but alternate-title marker changes must be applied through the upstream catalog workflow.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form wire:submit="saveAkaAttribute" class="space-y-6">
                    @include('admin.aka-attributes._form')

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="check">
                            Save changes
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="button" wire:click="deleteAkaAttribute" variant="outline" color="red" icon="trash">
                    Delete AKA attribute
                </x-ui.button>
            </div>
        @endif
    </section>
@endsection
