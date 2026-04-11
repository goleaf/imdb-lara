@extends('layouts.admin')

@section('title', 'Create Person')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.people.index')">Manage People</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Create</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Create Person</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Add a performer, filmmaker, or creator profile to the public database.
                </x-ui.text>
            </div>

            <x-ui.button as="a" :href="route('admin.people.index')" variant="outline" icon="arrow-left">
                Back to people
            </x-ui.button>
        </div>

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.people.index')"
                back-label="Back to people"
                heading="People Writes Paused"
                description="Local person creation is paused while Screenbase is reading people data from the remote catalog."
            >
                New cast and crew profiles should enter the system through the upstream catalog synchronization workflow.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form wire:submit="savePerson" class="space-y-6">
                    @include('admin.people._form')

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="plus">
                            Create person
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    </section>
@endsection
