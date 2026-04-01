@extends('layouts.admin')

@section('title', 'Manage Titles')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Manage Titles</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Manage Titles</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Published-state review and editorial oversight for title records.
            </x-ui.text>
        </div>

        <x-ui.card class="!max-w-none overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-black/5 text-left text-sm dark:divide-white/10">
                    <thead class="bg-black/5 dark:bg-white/5">
                        <tr class="text-neutral-500 dark:text-neutral-400">
                            <th class="px-4 py-3 font-medium">Title</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Year</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5 dark:divide-white/10">
                        @forelse ($titles as $title)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('public.titles.show', $title) }}" class="font-medium hover:opacity-80">
                                        {{ $title->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ str($title->title_type->value)->headline() }}</td>
                                <td class="px-4 py-3">{{ $title->release_year ?: 'TBA' }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$title->is_published ? 'green' : 'yellow'" variant="outline">
                                        {{ $title->is_published ? 'Published' : 'Draft' }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8">
                                    <x-ui.empty>
                                        <x-ui.heading level="h3">No titles are available.</x-ui.heading>
                                    </x-ui.empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div>
            {{ $titles->links() }}
        </div>
    </section>
@endsection
