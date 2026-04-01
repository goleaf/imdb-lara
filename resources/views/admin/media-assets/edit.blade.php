@extends('layouts.admin')

@section('title', 'Edit Media Asset')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.media-assets.index')">Manage Media Assets</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Edit</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <x-ui.card class="!max-w-none">
            <form method="POST" action="{{ route('admin.media-assets.update', $mediaAsset) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('admin.media-assets._form')

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.media-assets.index')" variant="ghost" icon="arrow-left">
                        Back to media
                    </x-ui.button>
                    <x-ui.button type="submit" icon="check-circle">
                        Save asset
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </section>
@endsection
