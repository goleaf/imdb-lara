@extends('layouts.admin')

@section('title', 'Manage People')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Manage People</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Manage People</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Curate public-facing biographies, profession labels, and linked credits.
                </x-ui.text>
            </div>

            <x-ui.button as="a" :href="route('admin.people.create')" icon="plus">
                New person
            </x-ui.button>
        </div>

        <x-ui.card class="!max-w-none overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-black/5 text-left text-sm dark:divide-white/10">
                    <thead class="bg-black/5 dark:bg-white/5">
                        <tr class="text-neutral-500 dark:text-neutral-400">
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Department</th>
                            <th class="px-4 py-3 font-medium">Credits</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5 dark:divide-white/10">
                        @forelse ($people as $person)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $person->name }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ '@'.$person->slug }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $person->known_for_department ?: 'Unassigned' }}</td>
                                <td class="px-4 py-3">{{ number_format($person->credits_count) }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$person->is_published ? 'green' : 'yellow'" variant="outline" :icon="$person->is_published ? 'check-circle' : 'pencil-square'">
                                        {{ $person->is_published ? 'Published' : 'Draft' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.link :href="route('admin.people.edit', $person)" variant="ghost" iconAfter="arrow-right">
                                        Edit
                                    </x-ui.link>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8">
                                    <x-ui.empty>
                                        <x-ui.empty.media>
                                            <x-ui.icon name="users" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">No people are available.</x-ui.heading>
                                    </x-ui.empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div>
            {{ $people->links() }}
        </div>
    </section>
@endsection
