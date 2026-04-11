@extends('layouts.admin')

@section('title', 'Edit Media Asset')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.media-assets.index')">Manage Media Assets</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Edit</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Edit Media Asset</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Update file metadata, primary ordering, provider details, and publishing state.
                </x-ui.text>
            </div>

            @if ($mediaAsset->adminAttachedEditUrl())
                <x-ui.button as="a" :href="$mediaAsset->adminAttachedEditUrl()" variant="outline" icon="arrow-left">
                    Back to attached record
                </x-ui.button>
            @endif
        </div>

        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.media-assets.index')"
                back-label="Back to media"
                heading="Media Asset Writes Paused"
                description="Media asset edits are disabled while Screenbase is operating in catalog-only mode."
            >
                Use the upstream catalog synchronization flow to replace files, metadata, captions, or provider identifiers.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form wire:submit="saveMediaAsset" enctype="multipart/form-data" class="space-y-6">
                    @include('admin.media-assets._form', ['mediaAsset' => $mediaAsset] + $mediaAssetFormData)

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="check">
                            Save changes
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="button" wire:click="deleteMediaAsset" variant="outline" color="red" icon="trash">
                    Delete media asset
                </x-ui.button>
            </div>
        @endif
    </section>
@endsection
