@extends('layouts.admin')

@section('title', 'Edit Episode')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.seasons.edit', $episode->season)">{{ $episode->season->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $episode->title->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <x-ui.card class="!max-w-none">
            <form method="POST" action="{{ route('admin.episodes.update', $episode) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('admin.episodes._form')

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.seasons.edit', $episode->season)" variant="ghost" icon="arrow-left">
                        Back to season
                    </x-ui.button>
                    <x-ui.button type="submit" icon="check-circle">
                        Save episode
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </section>
@endsection
