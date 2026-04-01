@extends('layouts.admin')

@section('title', 'Create Title')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.titles.index')">Manage Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Create</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Create Title</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Add a new movie, series, documentary, short, or special to the catalog.
                </x-ui.text>
            </div>

            <x-ui.button as="a" :href="route('admin.titles.index')" variant="outline" icon="arrow-left">
                Back to titles
            </x-ui.button>
        </div>

        <x-ui.card class="!max-w-none">
            <form method="POST" action="{{ route('admin.titles.store') }}" class="space-y-6">
                @csrf

                @include('admin.titles._form')

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.titles.index')" variant="ghost" icon="arrow-left">
                        Cancel
                    </x-ui.button>
                    <x-ui.button type="submit" icon="plus">
                        Create title
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </section>
@endsection
