@extends('layouts.admin')

@section('title', 'Create Credit')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Create Credit</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Create Credit</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Link a person to a title, role, profession, and optional episode.
            </x-ui.text>
        </div>

        <x-ui.card class="!max-w-none">
            <form method="POST" action="{{ route('admin.credits.store') }}" class="space-y-6">
                @csrf

                @include('admin.credits._form')

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.dashboard')" variant="ghost" icon="arrow-left">
                        Cancel
                    </x-ui.button>
                    <x-ui.button type="submit" icon="plus">
                        Create credit
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </section>
@endsection
