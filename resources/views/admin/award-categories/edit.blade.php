@extends('layouts.admin')

@section('title', 'Edit '.$awardCategory->resolvedLabel())

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.award-categories.index')">Manage Award Categories</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $awardCategory->resolvedLabel() }}</x-ui.breadcrumbs.item>
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
                <x-ui.heading level="h1" size="xl">Edit Award Category</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Update the lookup row that classifies imported award nominations.
                </x-ui.text>
            </div>

            <x-ui.badge variant="outline" color="slate" icon="trophy">
                {{ number_format($awardCategory->movieAwardNominationUsageCount()) }} linked nominations
            </x-ui.badge>
        </div>

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.award-categories.index')"
                back-label="Back to award categories"
                heading="Award Category Edits Paused"
                description="Award category updates and deletes stay paused while the remote catalog remains the source of truth."
            >
                This page remains routed through Livewire, but award category changes must be applied through the upstream catalog workflow.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form wire:submit="saveAwardCategory" class="space-y-6">
                    @include('admin.award-categories._form')

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="check">
                            Save changes
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="button" wire:click="deleteAwardCategory" variant="outline" color="red" icon="trash">
                    Delete award category
                </x-ui.button>
            </div>
        @endif
    </section>
@endsection
