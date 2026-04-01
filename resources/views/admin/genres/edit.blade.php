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

        <x-ui.card class="!max-w-none">
            <form method="POST" action="{{ route('admin.genres.update', $genre) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('admin.genres._form')

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.genres.index')" variant="ghost" icon="arrow-left">
                        Back to genres
                    </x-ui.button>
                    <x-ui.button type="submit" icon="check-circle">
                        Save changes
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <x-ui.heading level="h2" size="lg">Usage</x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        {{ number_format($genre->titles_count) }} titles currently map to this genre.
                    </x-ui.text>
                </div>

                <form method="POST" action="{{ route('admin.genres.destroy', $genre) }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="ghost" icon="trash">
                        Delete genre
                    </x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </section>
@endsection
