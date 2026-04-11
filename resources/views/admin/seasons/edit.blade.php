@extends('layouts.admin')

@section('title', 'Edit '.$season->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.titles.edit', $season->series)">{{ $season->series->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $season->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Edit {{ $season->name }}</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Manage season metadata and episode hierarchy for {{ $season->series->name }}.
                </x-ui.text>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button as="a" :href="route('admin.titles.edit', $season->series)" variant="outline" icon="arrow-left">
                    Back to title
                </x-ui.button>
                <x-ui.button as="a" :href="route('public.seasons.show', ['series' => $season->series, 'season' => $season])" variant="outline" icon="arrow-top-right-on-square">
                    View public page
                </x-ui.button>
            </div>
        </div>

        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        @if ($catalogOnly)
            <x-admin.catalog-write-disabled-panel
                :back-href="route('admin.titles.edit', $season->series)"
                back-label="Back to title"
                heading="Season Writes Paused"
                description="Season metadata, episode creation, episode deletes, and season deletes are paused while Screenbase is running in catalog-only mode."
            >
                Keep using the Livewire admin shell to inspect hierarchy, but apply season and episode changes through the upstream catalog synchronization workflow.
            </x-admin.catalog-write-disabled-panel>
        @else
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                <div class="space-y-4">
                    <x-ui.card class="!max-w-none">
                        <form wire:submit="saveSeason" class="space-y-6">
                            @include('admin.seasons._form', ['season' => $season] + $seasonFormData)

                            <div class="flex justify-end">
                                <x-ui.button type="submit" icon="check">
                                    Save changes
                                </x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>

                    <div class="flex justify-end">
                        <x-ui.button type="button" wire:click="deleteSeason" variant="outline" color="red" icon="trash">
                            Delete season
                        </x-ui.button>
                    </div>
                </div>

                <div class="space-y-4">
                    <x-ui.card class="!max-w-none">
                        <div class="space-y-3">
                            <div>
                                <x-ui.heading level="h2" size="md">Episodes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Episodes stay linked to both the season and the parent series title.
                                </x-ui.text>
                            </div>

                            <div class="space-y-2">
                                @forelse ($season->episodes as $episode)
                                    <a href="{{ route('admin.episodes.edit', $episode) }}" class="flex items-center justify-between gap-3 rounded-box border border-black/10 p-3 transition hover:border-black/20 dark:border-white/10 dark:hover:border-white/20">
                                        <div>
                                            <div class="font-medium text-neutral-900 dark:text-white">
                                                S{{ str_pad((string) $episode->season_number, 2, '0', STR_PAD_LEFT) }}E{{ str_pad((string) ($episode->episode_number ?? 0), 2, '0', STR_PAD_LEFT) }}
                                                · {{ $episode->title?->name ?? 'Untitled episode' }}
                                            </div>
                                            <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                                {{ $episode->production_code ?: 'No production code' }}
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if ($episode->title?->is_published)
                                                <x-ui.badge color="green" icon="eye">Published</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="outline" color="neutral" icon="eye-slash">Draft</x-ui.badge>
                                            @endif
                                            <x-ui.badge variant="outline" icon="arrow-right">Edit</x-ui.badge>
                                        </div>
                                    </a>
                                @empty
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="play" class="size-8 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3" size="sm">No episodes yet</x-ui.heading>
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            Add the first episode to establish numbering and public season detail records.
                                        </x-ui.text>
                                    </x-ui.empty>
                                @endforelse
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card class="!max-w-none">
                        <form wire:submit="saveEpisode" class="space-y-6">
                            @include('admin.episodes._form', ['episode' => $draftEpisode] + $draftEpisodeFormData)

                            <div class="flex justify-end">
                                <x-ui.button type="submit" icon="plus">
                                    Add episode
                                </x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>
            </div>
        @endif
    </section>
@endsection
