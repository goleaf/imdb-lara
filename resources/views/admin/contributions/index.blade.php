@extends('layouts.admin')

@section('title', 'Contributions Queue')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Contributions Queue</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Contributions Queue</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Review structured catalog contributions before they are accepted into the database.
            </x-ui.text>
        </div>

        <div class="grid gap-4">
            @forelse ($contributions as $contribution)
                <livewire:admin.contribution-moderation-card :contribution="$contribution" :key="'admin-contribution-'.$contribution->id" />
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.empty.media>
                        <x-ui.icon name="clipboard-document-check" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No contributions are queued.</x-ui.heading>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $contributions->links() }}
        </div>
    </section>
@endsection
