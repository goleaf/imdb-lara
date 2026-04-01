@extends('layouts.admin')

@section('title', 'Manage Genres')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Manage Genres</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Manage Genres</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Maintain browse taxonomy and genre landing-page copy.
                </x-ui.text>
            </div>

            <x-ui.button as="a" :href="route('admin.genres.create')" icon="plus">
                New genre
            </x-ui.button>
        </div>

        <x-ui.card class="!max-w-none overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-black/5 text-left text-sm dark:divide-white/10">
                    <thead class="bg-black/5 dark:bg-white/5">
                        <tr class="text-neutral-500 dark:text-neutral-400">
                            <th class="px-4 py-3 font-medium">Genre</th>
                            <th class="px-4 py-3 font-medium">Titles</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5 dark:divide-white/10">
                        @forelse ($genres as $genre)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $genre->name }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">{{ $genre->slug }}</div>
                                </td>
                                <td class="px-4 py-3">{{ number_format($genre->titles_count) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.link :href="route('admin.genres.edit', $genre)" variant="ghost" iconAfter="arrow-right">
                                        Edit
                                    </x-ui.link>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8">
                                    <x-ui.empty>
                                        <x-ui.empty.media>
                                            <x-ui.icon name="tag" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">No genres are available.</x-ui.heading>
                                    </x-ui.empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div>
            {{ $genres->links() }}
        </div>
    </section>
@endsection
