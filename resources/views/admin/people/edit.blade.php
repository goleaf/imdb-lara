@extends('layouts.admin')

@section('title', 'Edit '.$person->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('admin.people.index')">Manage People</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $person->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Edit {{ $person->name }}</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Update biography, identity fields, professions, media, and linked credits.
                </x-ui.text>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button as="a" :href="route('public.people.show', $person)" variant="outline" icon="arrow-top-right-on-square">
                    View public page
                </x-ui.button>
                <x-ui.button as="a" :href="route('admin.credits.create', ['person' => $person->id])" variant="outline" icon="plus">
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
                :back-href="route('admin.people.index')"
                back-label="Back to people"
                heading="Person Edits Paused"
                description="Biography, profession, credit-linking, media, and delete workflows are paused while the remote catalog remains the source of truth."
            >
                This route stays on the Livewire shell, but changes to people records now need to come from the upstream catalog synchronization workflow.
            </x-admin.catalog-write-disabled-panel>
        @else
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                <div class="space-y-4">
                    <x-ui.card class="!max-w-none">
                        <form wire:submit="savePerson" class="space-y-6">
                            @include('admin.people._form')

                            <div class="flex justify-end">
                                <x-ui.button type="submit" icon="check">
                                    Save changes
                                </x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>

                    <x-ui.card class="!max-w-none">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <x-ui.heading level="h2" size="md">Professions</x-ui.heading>
                                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                        Keep department and primary-role metadata aligned with credit attribution.
                                    </x-ui.text>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @forelse ($person->professions as $profession)
                                    <livewire:admin.person-profession-editor :person="$person" :profession-record="$profession" :key="'person-profession-'.$profession->id" />
                                @empty
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="briefcase" class="size-8 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3" size="sm">No professions yet</x-ui.heading>
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            Add a profession so credits can validate against this person’s role taxonomy.
                                        </x-ui.text>
                                    </x-ui.empty>
                                @endforelse
                            </div>

                            <div class="rounded-box border border-dashed border-black/10 p-4 dark:border-white/10">
                                <livewire:admin.person-profession-editor :person="$person" :default-sort-order="$defaultProfessionSortOrder" :key="'person-profession-create-'.$person->id" />
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div class="space-y-4">
                    <x-ui.card class="!max-w-none">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <x-ui.heading level="h2" size="md">Linked credits</x-ui.heading>
                                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                        Credits for this person stay editable from the dedicated credit editor.
                                    </x-ui.text>
                                </div>
                            </div>

                            <div class="space-y-2">
                                @forelse ($person->credits as $credit)
                                    <a href="{{ route('admin.credits.edit', $credit) }}" class="block rounded-box border border-black/10 p-3 transition hover:border-black/20 dark:border-white/10 dark:hover:border-white/20">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium text-neutral-900 dark:text-white">
                                                    {{ $credit->title?->name ?? 'Untitled title' }}
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
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="film" class="size-8 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3" size="sm">No credits linked</x-ui.heading>
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            Use the credit editor to link this person to titles, episodes, and profession records.
                                        </x-ui.text>
                                    </x-ui.empty>
                                @endforelse
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card class="!max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="md">Media assets</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Headshots, stills, and gallery assets published from here flow directly to the public person page.
                                </x-ui.text>
                            </div>

                            <div class="space-y-2">
                                @forelse ($person->mediaAssets as $mediaAsset)
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
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="photo" class="size-8 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3" size="sm">No media assets</x-ui.heading>
                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                            Add a headshot or gallery asset to populate the public person presentation.
                                        </x-ui.text>
                                    </x-ui.empty>
                                @endforelse
                            </div>

                            <form wire:submit="saveDraftMediaAsset" enctype="multipart/form-data" class="space-y-4 rounded-box border border-dashed border-black/10 p-4 dark:border-white/10">
                                @include('admin.media-assets._form', ['mediaAsset' => $draftMediaAsset, 'statePath' => 'draftMediaAsset'])

                                <div class="flex justify-end">
                                    <x-ui.button type="submit" size="sm" icon="plus">
                                        Add media asset
                                    </x-ui.button>
                                </div>
                            </form>
                        </div>
                    </x-ui.card>

                    <div class="flex justify-end">
                        <x-ui.button type="button" wire:click="deletePerson" variant="outline" color="red" icon="trash">
                            Delete person
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
