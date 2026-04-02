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
                    Update core public-facing metadata for this title without leaving the current editorial surface.
                </x-ui.text>
            </div>

            <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="outline" icon="arrow-top-right-on-square">
                View public page
            </x-ui.button>
        </div>

        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <x-ui.card class="!max-w-none">
            <form method="POST" action="{{ route('admin.titles.update', $title) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('admin.titles._form')

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.titles.index')" variant="ghost" icon="arrow-left">
                        Back to titles
                    </x-ui.button>
                    <x-ui.button type="submit" icon="check-circle">
                        Save changes
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="grid gap-4 xl:grid-cols-2">
            <x-ui.card class="!max-w-none space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <x-ui.heading level="h2" size="lg">Credits</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Manage linked cast and crew for this title.
                        </x-ui.text>
                    </div>

                    <x-ui.button as="a" :href="route('admin.credits.create', ['title' => $title->id])" variant="outline" icon="plus">
                        Add credit
                    </x-ui.button>
                </div>

                <div class="space-y-3">
                    @forelse ($title->credits as $credit)
                        <div class="rounded-box border border-black/10 p-3 dark:border-white/10">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ $credit->person->name }}</div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $credit->department }} · {{ $credit->job }}
                                        @if ($credit->character_name)
                                            · {{ $credit->character_name }}
                                        @endif
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <x-ui.button as="a" :href="route('admin.credits.edit', $credit)" size="sm" variant="outline" icon="pencil-square">
                                        Edit
                                    </x-ui.button>

                                    <form method="POST" action="{{ route('admin.credits.destroy', $credit) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" size="sm" variant="ghost" icon="trash">
                                            Remove
                                        </x-ui.button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="users" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No credits linked yet.</x-ui.heading>
                        </x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none space-y-4">
                <div>
                    <x-ui.heading level="h2" size="lg">Media Assets</x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Attach posters, backdrops, galleries, and trailers to this title.
                    </x-ui.text>
                </div>

                <form method="POST" action="{{ route('admin.titles.media-assets.store', $title) }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
                    @csrf

                    <x-ui.field>
                        <x-ui.label>Kind</x-ui.label>
                        <select
                            name="kind"
                            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                        >
                            @foreach (\App\Enums\MediaKind::allowedForMediable($title) as $mediaKind)
                                <option value="{{ $mediaKind->value }}">{{ str($mediaKind->value)->headline() }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Upload image</x-ui.label>
                        <x-ui.input name="file" type="file" accept="image/*" />
                        <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Upload posters, backdrops, gallery images, or stills directly to local storage.
                        </x-ui.text>
                        <x-ui.error name="file" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>URL</x-ui.label>
                        <x-ui.input name="url" type="url" placeholder="https://..." />
                        <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                            Required for trailers and remote assets. Optional when uploading an image file.
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
                        <x-ui.input name="provider" placeholder="internal" />
                        <x-ui.error name="provider" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Provider key</x-ui.label>
                        <x-ui.input name="provider_key" />
                        <x-ui.error name="provider_key" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Position</x-ui.label>
                        <x-ui.input name="position" type="number" min="0" value="0" />
                        <x-ui.error name="position" />
                    </x-ui.field>

                    <x-ui.field class="md:col-span-2">
                        <x-ui.label>Caption</x-ui.label>
                        <x-ui.textarea name="caption" rows="2"></x-ui.textarea>
                        <x-ui.error name="caption" />
                    </x-ui.field>

                    <label class="flex items-center gap-2 text-sm md:col-span-2">
                        <input type="hidden" name="is_primary" value="0">
                        <input type="checkbox" name="is_primary" value="1" class="rounded border-black/20 dark:border-white/20 dark:bg-neutral-900">
                        <span>Mark as primary asset for this media kind</span>
                    </label>

                    <div class="md:col-span-2">
                        <x-ui.button type="submit" icon="plus">Add media asset</x-ui.button>
                    </div>
                </form>

                <div class="space-y-4">
                    @if ($title->mediaAssets->isEmpty())
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="photo" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No media assets attached yet.</x-ui.heading>
                        </x-ui.empty>
                    @endif

                    @foreach (\App\Enums\MediaKind::allowedForMediable($title) as $mediaKind)
                        @continue(($title->groupedMediaAssetsByKind()->get($mediaKind->value, collect()))->isEmpty())

                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <x-ui.heading level="h3" size="md">{{ str($mediaKind->value)->headline() }}</x-ui.heading>
                                <x-ui.badge variant="outline" color="neutral" icon="{{ $mediaKind->isVideo() ? 'play-circle' : 'photo' }}">
                                    {{ number_format(($title->groupedMediaAssetsByKind()->get($mediaKind->value, collect()))->count()) }} assets
                                </x-ui.badge>
                            </div>

                            @foreach ($title->groupedMediaAssetsByKind()->get($mediaKind->value, collect()) as $mediaAsset)
                                <div class="rounded-box border border-black/10 p-3 dark:border-white/10">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                                @if ($mediaAsset->url && ! $mediaAsset->isVideo())
                                                    <img
                                                        src="{{ $mediaAsset->url }}"
                                                        alt="{{ $mediaAsset->alt_text ?: $title->name }}"
                                                        class="size-14 object-cover"
                                                    >
                                                @else
                                                    <div class="flex size-14 items-center justify-center text-neutral-500 dark:text-neutral-400">
                                                        <x-ui.icon :name="$mediaAsset->isVideo() ? 'play-circle' : 'photo'" class="size-6" />
                                                    </div>
                                                @endif
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
                                                    @if ($mediaAsset->duration_seconds)
                                                        · {{ number_format($mediaAsset->duration_seconds) }} sec
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
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        @if (in_array($title->title_type, [\App\Enums\TitleType::Series, \App\Enums\TitleType::MiniSeries], true))
            <x-ui.card class="!max-w-none space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <x-ui.heading level="h2" size="lg">TV Hierarchy</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Manage seasons and episode entry points for this series.
                        </x-ui.text>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.titles.seasons.store', $title) }}" class="grid gap-4 lg:grid-cols-3">
                    @csrf
                    <x-ui.field>
                        <x-ui.label>Season name</x-ui.label>
                        <x-ui.input name="season[name]" :value="old('season.name')" placeholder="Season 1" />
                        <x-ui.error name="season.name" />
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.label>Slug</x-ui.label>
                        <x-ui.input name="season[slug]" :value="old('season.slug')" placeholder="season-1" />
                        <x-ui.error name="season.slug" />
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.label>Season number</x-ui.label>
                        <x-ui.input name="season[season_number]" type="number" min="1" :value="old('season.season_number')" />
                        <x-ui.error name="season.season_number" />
                    </x-ui.field>
                    <x-ui.field class="lg:col-span-3">
                        <x-ui.label>Summary</x-ui.label>
                        <x-ui.textarea name="season[summary]" rows="2">{{ old('season.summary') }}</x-ui.textarea>
                        <x-ui.error name="season.summary" />
                    </x-ui.field>
                    <div class="lg:col-span-3">
                        <x-ui.button type="submit" icon="plus">Add season</x-ui.button>
                    </div>
                </form>

                <div class="grid gap-3 lg:grid-cols-2">
                    @forelse ($title->seasons as $season)
                        <div class="rounded-box border border-black/10 p-3 dark:border-white/10">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium">{{ $season->name }}</div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        Season {{ $season->season_number }} · {{ $season->episodes_count }} episodes
                                    </div>
                                </div>
                                <x-ui.button as="a" :href="route('admin.seasons.edit', $season)" size="sm" variant="outline" icon="pencil-square">
                                    Manage season
                                </x-ui.button>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10 lg:col-span-2">
                            <x-ui.empty.media>
                                <x-ui.icon name="rectangle-stack" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No seasons have been added yet.</x-ui.heading>
                        </x-ui.empty>
                    @endforelse
                </div>
            </x-ui.card>
        @endif

        <x-ui.card class="!max-w-none">
            <div class="flex justify-end">
                <form method="POST" action="{{ route('admin.titles.destroy', $title) }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="ghost" icon="trash">
                        Delete title
                    </x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </section>
@endsection
