@extends('layouts.admin')

@section('title', 'Edit Credit')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Edit Credit</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Edit Credit</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Update title, person, profession, episode specificity, and billing metadata.
                </x-ui.text>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($credit->title)
                    <x-ui.button as="a" :href="route('admin.titles.edit', $credit->title)" variant="outline" icon="film">
                        View title
                    </x-ui.button>
                @endif
                @if ($credit->person)
                    <x-ui.button as="a" :href="route('admin.people.edit', $credit->person)" variant="outline" icon="user">
                        View person
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
                heading="Credit Edits Paused"
                description="Credit updates are paused while Screenbase is reading the remote catalog as the system of record."
            >
                Use the remote synchronization pipeline to change departments, jobs, billing, or episode assignments.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form wire:submit="saveCredit" class="space-y-6">
                    @include('admin.credits._form')

                    <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                        Episode and profession options refresh live when you change the selected title or person.
                    </x-ui.text>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="check">
                            Save changes
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="button" wire:click="deleteCredit" variant="outline" color="red" icon="trash">
                    Delete credit
                </x-ui.button>
            </div>
        @endif
    </section>
@endsection
