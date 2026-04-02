@extends('layouts.admin')

@section('title', 'Edit '.$season->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.titles.edit', $season->series)">{{ $season->series->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $season->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <x-ui.card class="!max-w-none">
            <form method="POST" action="{{ route('admin.seasons.update', $season) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('admin.seasons._form')

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.titles.edit', $season->series)" variant="ghost" icon="arrow-left">
                        Back to title
                    </x-ui.button>
                    <x-ui.button type="submit" icon="check-circle">
                        Save season
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card class="!max-w-none space-y-4">
            <div>
                <x-ui.heading level="h2" size="lg">Episodes</x-ui.heading>
                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                    Build and maintain the episode lineup for this season.
                </x-ui.text>
            </div>

            <form method="POST" action="{{ route('admin.seasons.episodes.store', $season) }}" class="space-y-4">
                @csrf
                @include('admin.episodes._form', [
                    'episode' => new \App\Models\Episode(['season_number' => $season->season_number]),
                    'fieldPrefix' => 'episode',
                ])
                <div>
                    <x-ui.button type="submit" icon="plus">Add episode</x-ui.button>
                </div>
            </form>

            <div class="space-y-3">
                @forelse ($season->episodes as $episode)
                    <div class="rounded-box border border-black/10 p-3 dark:border-white/10">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ $episode->title->name }}</div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    Episode {{ $episode->episode_number ?: 'TBA' }}
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <x-ui.button as="a" :href="route('admin.episodes.edit', $episode)" size="sm" variant="outline" icon="pencil-square">
                                    Edit
                                </x-ui.button>
                                <form method="POST" action="{{ route('admin.episodes.destroy', $episode) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" size="sm" variant="ghost" icon="trash">
                                        Delete
                                    </x-ui.button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="rectangle-stack" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">No episodes added yet.</x-ui.heading>
                    </x-ui.empty>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="flex justify-end">
                <form method="POST" action="{{ route('admin.seasons.destroy', $season) }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="ghost" icon="trash">
                        Delete season
                    </x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </section>
@endsection
