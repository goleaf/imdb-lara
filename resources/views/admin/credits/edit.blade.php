@extends('layouts.admin')

@section('title', 'Edit Credit')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Edit Credit</x-ui.breadcrumbs.item>
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
                heading="Credit Edits Paused"
                description="Credit updates are paused while Screenbase is reading the remote catalog as the system of record."
            >
                Use the remote synchronization pipeline to change departments, jobs, billing, or episode assignments.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form method="POST" action="{{ route('admin.credits.update', $credit) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    @include('admin.credits._form')

                    <div class="flex justify-end gap-3">
                        <x-ui.button as="a" :href="route('admin.titles.edit', $credit->title)" variant="ghost" icon="arrow-left">
                            Back to title
                        </x-ui.button>
                        <x-ui.button type="submit" icon="check-circle">
                            Save changes
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif
    </section>
@endsection
