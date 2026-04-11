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

            <x-ui.button as="a" :href="route('public.people.show', $person)" variant="outline" icon="arrow-top-right-on-square">
                View public page
            </x-ui.button>
        </div>

        @if (! $catalogOnly && session('status'))
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
            <x-ui.card class="!max-w-none">
                <form method="POST" action="{{ route('admin.people.update', $person) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    @include('admin.people._form')

                    <div class="flex justify-end gap-3">
                        <x-ui.button as="a" :href="route('admin.people.index')" variant="ghost" icon="arrow-left">
                            Back to people
                        </x-ui.button>
                        <x-ui.button type="submit" icon="check-circle">
                            Save changes
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="grid gap-4 xl:grid-cols-2">
                <x-ui.card class="!max-w-none space-y-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Professions</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Shape the public profession chips and career grouping.
                        </x-ui.text>
                    </div>

                    <form method="POST" action="{{ route('admin.people.professions.store', $person) }}" class="grid gap-4 md:grid-cols-2">
                        @csrf
                        <x-ui.field>
                            <x-ui.label>Department</x-ui.label>
                            <x-ui.input name="department" :value="old('department')" placeholder="Cast" />
                            <x-ui.error name="department" />
                        </x-ui.field>
                        <x-ui.field>
                            <x-ui.label>Profession</x-ui.label>
                            <x-ui.input name="profession" :value="old('profession')" placeholder="Actor" />
                            <x-ui.error name="profession" />
                        </x-ui.field>
                        <x-ui.field>
                            <x-ui.label>Sort order</x-ui.label>
                            <x-ui.input name="sort_order" type="number" min="0" :value="old('sort_order', 0)" />
                            <x-ui.error name="sort_order" />
                        </x-ui.field>
                        <label class="flex items-center gap-2 text-sm md:self-end">
                            <input type="hidden" name="is_primary" value="0">
                            <input type="checkbox" name="is_primary" value="1" class="rounded border-black/20 dark:border-white/20 dark:bg-neutral-900" @checked(old('is_primary'))>
                            <span>Primary profession</span>
                        </label>
                        <div class="md:col-span-2">
                            <x-ui.button type="submit" icon="plus">Add profession</x-ui.button>
                        </div>
                    </form>

                    <div class="space-y-3">
                        @forelse ($person->professions as $profession)
                            <div class="rounded-box border border-black/10 p-3 dark:border-white/10">
                                <form method="POST" action="{{ route('admin.professions.update', $profession) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-3 md:grid-cols-4">
                                        <x-ui.input name="department" :value="$profession->department" />
                                        <x-ui.input name="profession" :value="$profession->profession" />
                                        <x-ui.input name="sort_order" type="number" min="0" :value="$profession->sort_order" />
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="hidden" name="is_primary" value="0">
                                            <input type="checkbox" name="is_primary" value="1" class="rounded border-black/20 dark:border-white/20 dark:bg-neutral-900" @checked($profession->is_primary)>
                                            <span>Primary</span>
                                        </label>
                                    </div>
                                    <div class="mt-3 flex justify-end gap-2">
                                        <x-ui.button type="submit" size="sm" variant="outline" icon="check-circle">
                                            Update
                                        </x-ui.button>
                                    </div>
                                </form>

                                <div class="mt-2 flex justify-end">
                                    <form method="POST" action="{{ route('admin.professions.destroy', $profession) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" size="sm" variant="ghost" icon="trash">
                                            Delete
                                        </x-ui.button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="sparkles" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No professions added yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <x-ui.heading level="h2" size="lg">Credit Linking</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Credits connect this person to titles across the catalog.
                            </x-ui.text>
                        </div>
                        <x-ui.button as="a" :href="route('admin.credits.create', ['person' => $person->id])" variant="outline" icon="plus">
                            Add credit
                        </x-ui.button>
                    </div>

                    <div class="space-y-3">
                        @forelse ($person->credits as $credit)
                            <div class="rounded-box border border-black/10 p-3 dark:border-white/10">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div class="font-medium">{{ $credit->title->name }}</div>
                                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $credit->department }} · {{ $credit->job }}
                                            @if ($credit->character_name)
                                                · {{ $credit->character_name }}
                                            @endif
                                        </div>
                                    </div>
                                    <x-ui.button as="a" :href="route('admin.credits.edit', $credit)" size="sm" variant="outline" icon="pencil-square">
                                        Edit credit
                                    </x-ui.button>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No credits linked yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card class="!max-w-none space-y-4">
                <div>
                    <x-ui.heading level="h2" size="lg">Photo and Media</x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Attach headshots, gallery images, and video assets to the person profile.
                    </x-ui.text>
                </div>

                <form method="POST" action="{{ route('admin.people.media-assets.store', $person) }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    <x-ui.field>
                        <x-ui.label>Kind</x-ui.label>
                        <select
                            name="kind"
                            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                        >
                            @foreach (\App\Enums\MediaKind::allowedForMediable($person) as $mediaKind)
                                <option value="{{ $mediaKind->value }}">{{ str($mediaKind->value)->headline() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.label>Upload image</x-ui.label>
                        <x-ui.input name="file" type="file" accept="image/*" />
                        <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Upload headshots and gallery images directly to local storage.
                        </x-ui.text>
                        <x-ui.error name="file" />
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.label>URL</x-ui.label>
                        <x-ui.input name="url" type="url" placeholder="https://..." />
                        <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Use a remote URL for externally hosted portrait or gallery assets.
                        </x-ui.text>
                        <x-ui.error name="url" />
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.label>Alt text</x-ui.label>
                        <x-ui.input name="alt_text" />
                        <x-ui.error name="alt_text" />
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.label>Provider</x-ui.label>
                        <x-ui.input name="provider" />
                        <x-ui.error name="provider" />
                    </x-ui.field>
                    <label class="flex items-center gap-2 text-sm md:col-span-2">
                        <input type="hidden" name="is_primary" value="0">
                        <input type="checkbox" name="is_primary" value="1" class="rounded border-black/20 dark:border-white/20 dark:bg-neutral-900">
                        <span>Primary asset</span>
                    </label>
                    <div class="md:col-span-2">
                        <x-ui.button type="submit" icon="plus">Add media asset</x-ui.button>
                    </div>
                </form>

                <div class="space-y-4">
                    @if ($person->mediaAssets->isEmpty())
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10 lg:col-span-2">
                            <x-ui.empty.media>
                                <x-ui.icon name="photo" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No media assets attached yet.</x-ui.heading>
                        </x-ui.empty>
                    @endif

                    @foreach (\App\Enums\MediaKind::allowedForMediable($person) as $mediaKind)
                        @continue(($person->groupedMediaAssetsByKind()->get($mediaKind->value, collect()))->isEmpty())

                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <x-ui.heading level="h3" size="md">{{ str($mediaKind->value)->headline() }}</x-ui.heading>
                                <x-ui.badge variant="outline" color="neutral" icon="photo">
                                    {{ number_format(($person->groupedMediaAssetsByKind()->get($mediaKind->value, collect()))->count()) }} assets
                                </x-ui.badge>
                            </div>

                            <div class="grid gap-3 lg:grid-cols-2">
                                @foreach ($person->groupedMediaAssetsByKind()->get($mediaKind->value, collect()) as $mediaAsset)
                                    <div class="rounded-box border border-black/10 p-3 dark:border-white/10">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                                    <img
                                                        src="{{ $mediaAsset->url }}"
                                                        alt="{{ $mediaAsset->alt_text ?: $person->name }}"
                                                        class="size-14 object-cover"
                                                    >
                                                </div>

                                                <div>
                                                    <div class="font-medium">{{ $mediaAsset->caption ?: str($mediaAsset->kind->value)->headline() }}</div>
                                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                        {{ $mediaAsset->provider ?: 'Direct URL' }} · Position {{ $mediaAsset->position }}
                                                        @if ($mediaAsset->is_primary)
                                                            · Primary
                                                        @endif
                                                        @if ($mediaAsset->width && $mediaAsset->height)
                                                            · {{ number_format($mediaAsset->width) }} × {{ number_format($mediaAsset->height) }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <x-ui.button as="a" :href="route('admin.media-assets.edit', $mediaAsset)" size="sm" variant="outline" icon="pencil-square">
                                                    Edit
                                                </x-ui.button>
                                                <form method="POST" action="{{ route('admin.media-assets.destroy', $mediaAsset) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-ui.button type="submit" size="sm" variant="ghost" icon="trash">
                                                        Delete
                                                    </x-ui.button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none">
                <div class="flex justify-end">
                    <form method="POST" action="{{ route('admin.people.destroy', $person) }}">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="ghost" icon="trash">
                            Delete person
                        </x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        @endif
    </section>
@endsection
