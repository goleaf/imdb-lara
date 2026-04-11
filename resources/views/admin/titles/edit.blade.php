@extends('layouts.admin')

@section('title', 'Edit '.$title->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.titles.index')">Manage Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $title->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Edit {{ $title->name }}</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Update core public-facing metadata for this title.
                </x-ui.text>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="outline" icon="arrow-top-right-on-square">
                    View public page
                </x-ui.button>
                <x-ui.button as="a" :href="route('admin.credits.create', ['title' => $title->id])" variant="outline" icon="plus">
                    Add credit
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
                :back-href="route('admin.titles.index')"
                back-label="Back to titles"
                heading="Title Editing Paused"
                description="Core metadata, credits, media, season management, and delete workflows are paused while the remote catalog stays authoritative."
            >
                This title remains viewable through the Livewire admin shell, but edits need to be applied by the upstream catalog synchronization pipeline.
            </x-admin.catalog-write-disabled-panel>
        @else
            <div class="space-y-4">
                <x-ui.card class="!max-w-none">
                    <form method="POST" action="{{ route('admin.titles.update', $title) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        @include('admin.titles._form')

                        <div class="flex justify-end">
                            <x-ui.button type="submit" icon="check">
                                Save changes
                            </x-ui.button>
                        </div>
                    </form>
                </x-ui.card>

                <div class="grid gap-4 xl:grid-cols-2">
                    <x-ui.card class="!max-w-none">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <x-ui.heading level="h2" size="md">Linked credits</x-ui.heading>
                                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                        Cast and crew records stay scoped to this title from the dedicated credit editor.
                                    </x-ui.text>
                                </div>

                                <x-ui.button as="a" :href="route('admin.credits.create', ['title' => $title->id])" size="sm" variant="outline" icon="plus">
                                    Add credit
                                </x-ui.button>
                            </div>

                            <div class="space-y-2">
                                @forelse ($title->credits as $credit)
                                    <a href="{{ route('admin.credits.edit', $credit) }}" class="block rounded-box border border-black/10 p-3 transition hover:border-black/20 dark:border-white/10 dark:hover:border-white/20">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium text-neutral-900 dark:text-white">
                                                    {{ $credit->person?->name ?? 'Unknown person' }}
                                                </div>
                                                <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                                    {{ $credit->department }} · {{ $credit->job }}
                                                    @if ($credit->episode?->title)
                                                        · Episode: {{ $credit->episode->title->name }}
                                                    @endif
                                                </div>
                                            </div>

                                            <x-ui.badge variant="outline" icon="arrow-right">Edit</x-ui.badge>
                                        </div>
                                    </a>
                                @empty
                                    <x-ui.empty-state
                                        title="No credits linked"
                                        description="Add cast or crew records to enrich the public title detail page."
                                        icon="users"
                                    />
                                @endforelse
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card class="!max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="md">Media assets</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Posters, backdrops, stills, and video metadata published here flow to the public title pages.
                                </x-ui.text>
                            </div>

                            <div class="space-y-2">
                                @forelse ($title->mediaAssets as $mediaAsset)
                                    <a href="{{ route('admin.media-assets.edit', $mediaAsset) }}" class="flex items-center justify-between gap-3 rounded-box border border-black/10 p-3 transition hover:border-black/20 dark:border-white/10 dark:hover:border-white/20">
                                        <div>
                                            <div class="font-medium text-neutral-900 dark:text-white">
                                                {{ $mediaAsset->kindLabel() }}
                                            </div>
                                            <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                                {{ $mediaAsset->caption ?: ($mediaAsset->alt_text ?: 'No caption') }}
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if ($mediaAsset->is_primary)
                                                <x-ui.badge color="amber" icon="star">Primary</x-ui.badge>
                                            @endif
                                            <x-ui.badge variant="outline" icon="arrow-right">Edit</x-ui.badge>
                                        </div>
                                    </a>
                                @empty
                                    <x-ui.empty-state
                                        title="No media assets"
                                        description="Add artwork or video metadata so the public title page has editorial media."
                                        icon="photo"
                                    />
                                @endforelse
                            </div>

                            <form method="POST" action="{{ route('admin.titles.media-assets.store', $title) }}" enctype="multipart/form-data" class="space-y-4 rounded-box border border-dashed border-black/10 p-4 dark:border-white/10">
                                @csrf

                                @include('admin.media-assets._form', ['mediaAsset' => $draftMediaAsset])

                                <div class="flex justify-end">
                                    <x-ui.button type="submit" size="sm" icon="plus">
                                        Add media asset
                                    </x-ui.button>
                                </div>
                            </form>
                        </div>
                    </x-ui.card>
                </div>

                @if (in_array($title->title_type, [\App\Enums\TitleType::Series, \App\Enums\TitleType::MiniSeries], true))
                    <x-ui.card class="!max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="md">Seasons</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    TV hierarchy stays anchored here. Add seasons inline and drill into individual season editors for episodes.
                                </x-ui.text>
                            </div>

                            <div class="space-y-2">
                                @forelse ($title->seasons as $season)
                                    <a href="{{ route('admin.seasons.edit', $season) }}" class="flex items-center justify-between gap-3 rounded-box border border-black/10 p-3 transition hover:border-black/20 dark:border-white/10 dark:hover:border-white/20">
                                        <div>
                                            <div class="font-medium text-neutral-900 dark:text-white">
                                                {{ $season->name }}
                                            </div>
                                            <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                                Season {{ $season->season_number }}
                                                · {{ number_format($season->episodes_count) }} episodes
                                            </div>
                                        </div>

                                        <x-ui.badge variant="outline" icon="arrow-right">Edit</x-ui.badge>
                                    </a>
                                @empty
                                    <x-ui.empty-state
                                        title="No seasons yet"
                                        description="Add the first season to unlock episode editing and public TV hierarchy."
                                        icon="tv"
                                    />
                                @endforelse
                            </div>

                            <form method="POST" action="{{ route('admin.titles.seasons.store', $title) }}" class="space-y-6 rounded-box border border-dashed border-black/10 p-4 dark:border-white/10">
                                @csrf

                                @include('admin.seasons._form', ['season' => $draftSeason, 'fieldPrefix' => 'season'])

                                <div class="flex justify-end">
                                    <x-ui.button type="submit" size="sm" icon="plus">
                                        Add season
                                    </x-ui.button>
                                </div>
                            </form>
                        </div>
                    </x-ui.card>
                @endif

                <div class="flex justify-end">
                    <form method="POST" action="{{ route('admin.titles.destroy', $title) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="outline" color="red" icon="trash">
                            Delete title
                        </x-ui.button>
                    </form>
                </div>
            </div>
        @endif
    </section>
@endsection
