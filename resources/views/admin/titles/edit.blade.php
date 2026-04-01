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

                <div class="grid gap-4 lg:grid-cols-2">
                    <x-ui.field>
                        <x-ui.label>Name</x-ui.label>
                        <x-ui.input name="name" :value="old('name', $title->name)" />
                        <x-ui.error name="name" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Original title</x-ui.label>
                        <x-ui.input name="original_name" :value="old('original_name', $title->original_name)" />
                        <x-ui.error name="original_name" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Release year</x-ui.label>
                        <x-ui.input name="release_year" type="number" min="1888" max="2100" :value="old('release_year', $title->release_year)" />
                        <x-ui.error name="release_year" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>End year</x-ui.label>
                        <x-ui.input name="end_year" type="number" min="1888" max="2100" :value="old('end_year', $title->end_year)" />
                        <x-ui.error name="end_year" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Release date</x-ui.label>
                        <x-ui.input name="release_date" type="date" :value="old('release_date', $title->release_date?->toDateString())" />
                        <x-ui.error name="release_date" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Runtime (minutes)</x-ui.label>
                        <x-ui.input name="runtime_minutes" type="number" min="1" max="1000" :value="old('runtime_minutes', $title->runtime_minutes)" />
                        <x-ui.error name="runtime_minutes" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Certification</x-ui.label>
                        <x-ui.input name="age_rating" :value="old('age_rating', $title->age_rating)" />
                        <x-ui.error name="age_rating" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Country of origin</x-ui.label>
                        <x-ui.input name="origin_country" :value="old('origin_country', $title->origin_country)" />
                        <x-ui.error name="origin_country" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Original language</x-ui.label>
                        <x-ui.input name="original_language" :value="old('original_language', $title->original_language)" />
                        <x-ui.error name="original_language" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Publish status</x-ui.label>
                        <select
                            name="is_published"
                            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                        >
                            <option value="1" @selected(old('is_published', $title->is_published) == true)>Published</option>
                            <option value="0" @selected(old('is_published', $title->is_published) == false)>Draft</option>
                        </select>
                        <x-ui.error name="is_published" />
                    </x-ui.field>
                </div>

                <x-ui.field>
                    <x-ui.label>Plot outline</x-ui.label>
                    <x-ui.textarea name="plot_outline" rows="3">{{ old('plot_outline', $title->plot_outline) }}</x-ui.textarea>
                    <x-ui.error name="plot_outline" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Synopsis</x-ui.label>
                    <x-ui.textarea name="synopsis" rows="7">{{ old('synopsis', $title->synopsis) }}</x-ui.textarea>
                    <x-ui.error name="synopsis" />
                </x-ui.field>

                <div class="grid gap-4 lg:grid-cols-2">
                    <x-ui.field>
                        <x-ui.label>Tagline</x-ui.label>
                        <x-ui.input name="tagline" :value="old('tagline', $title->tagline)" />
                        <x-ui.error name="tagline" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>SEO title</x-ui.label>
                        <x-ui.input name="meta_title" :value="old('meta_title', $title->meta_title)" />
                        <x-ui.error name="meta_title" />
                    </x-ui.field>
                </div>

                <x-ui.field>
                    <x-ui.label>SEO description</x-ui.label>
                    <x-ui.textarea name="meta_description" rows="3">{{ old('meta_description', $title->meta_description) }}</x-ui.textarea>
                    <x-ui.error name="meta_description" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Search keywords</x-ui.label>
                    <x-ui.input name="search_keywords" :value="old('search_keywords', $title->search_keywords)" />
                    <x-ui.error name="search_keywords" />
                </x-ui.field>

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.titles.index')" variant="ghost">
                        Back to titles
                    </x-ui.button>
                    <x-ui.button type="submit" icon="check-circle">
                        Save changes
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </section>
@endsection
