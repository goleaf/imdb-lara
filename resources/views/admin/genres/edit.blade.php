@extends('layouts.admin')

@section('title', 'Edit '.$genre->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.genres.index')">Manage Genres</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $genre->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.genres.index')"
                back-label="Back to genres"
                heading="Genre Edits Paused"
                description="Genre updates and deletes stay paused while the remote catalog remains the source of truth."
            >
                This page remains routed through Livewire, but taxonomy changes must be applied through the upstream catalog workflow.
            </x-admin.catalog-write-disabled-panel>
        @else
            <x-ui.card class="!max-w-none">
                <form method="POST" action="{{ route('admin.genres.update', $genre) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    @include('admin.genres._form')

                    <div class="flex justify-end">
                        <x-ui.button type="submit" icon="check">
                            Save changes
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="flex justify-end">
                <form method="POST" action="{{ route('admin.genres.destroy', $genre) }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="outline" color="red" icon="trash">
                        Delete genre
                    </x-ui.button>
                </form>
            </div>
        @endif
    </section>
@endsection
