<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input wire:model.defer="name" name="name" :value="old('name', $title->name)" />
        <x-ui.error name="name" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Original title</x-ui.label>
        <x-ui.input wire:model.defer="original_name" name="original_name" :value="old('original_name', $title->original_name)" />
        <x-ui.error name="original_name" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input wire:model.defer="slug" name="slug" :value="old('slug', $title->slug)" />
        <x-ui.error name="slug" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Type</x-ui.label>
        <x-ui.native-select wire:model.defer="title_type" name="title_type">
            @foreach ($titleTypes as $titleType)
                <option value="{{ $titleType->value }}" @selected(old('title_type', $title->title_type?->value) === $titleType->value)>
                    {{ $titleType->label() }}
                </option>
            @endforeach
        </x-ui.native-select>
        <x-ui.error name="title_type" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input wire:model.defer="release_year" name="release_year" type="number" min="1888" max="2100" :value="old('release_year', $title->release_year)" />
        <x-ui.error name="release_year" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>End year</x-ui.label>
        <x-ui.input wire:model.defer="end_year" name="end_year" type="number" min="1888" max="2100" :value="old('end_year', $title->end_year)" />
        <x-ui.error name="end_year" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Release date</x-ui.label>
        <x-ui.input wire:model.defer="release_date" name="release_date" type="date" :value="old('release_date', $title->release_date?->toDateString())" />
        <x-ui.error name="release_date" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Runtime (minutes)</x-ui.label>
        <x-ui.input wire:model.defer="runtime_minutes" name="runtime_minutes" type="number" min="1" max="1000" :value="old('runtime_minutes', $title->runtime_minutes)" />
        <x-ui.error name="runtime_minutes" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Certification</x-ui.label>
        <x-ui.input wire:model.defer="age_rating" name="age_rating" :value="old('age_rating', $title->age_rating)" />
        <x-ui.error name="age_rating" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Country of origin</x-ui.label>
        <x-ui.input wire:model.defer="origin_country" name="origin_country" :value="old('origin_country', $title->origin_country)" />
        <x-ui.error name="origin_country" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Original language</x-ui.label>
        <x-ui.input wire:model.defer="original_language" name="original_language" :value="old('original_language', $title->original_language)" />
        <x-ui.error name="original_language" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Publish status</x-ui.label>
        <x-ui.native-select wire:model.defer="is_published" name="is_published">
            <option value="1" @selected(old('is_published', $title->is_published) == true)>Published</option>
            <option value="0" @selected(old('is_published', $title->is_published) == false)>Draft</option>
        </x-ui.native-select>
        <x-ui.error name="is_published" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Genres</x-ui.label>
    @php
        $selectedGenreIds = collect(old('genre_ids', $title->genres?->modelKeys() ?? []))
            ->map(fn ($genreId) => (int) $genreId);
    @endphp
    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($genres as $genre)
            <x-ui.checkbox
                wire:model="genre_ids"
                name="genre_ids[]"
                :value="$genre->id"
                :checked="$selectedGenreIds->contains($genre->id)"
                :label="$genre->name"
                class="rounded-box border border-black/10 px-3 py-2 text-sm dark:border-white/10"
            />
        @endforeach
    </div>
    <x-ui.error name="genre_ids" />
    <x-ui.error name="genre_ids.*" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Plot outline</x-ui.label>
    <x-ui.textarea wire:model.defer="plot_outline" name="plot_outline" rows="3">{{ old('plot_outline', $title->plot_outline) }}</x-ui.textarea>
    <x-ui.error name="plot_outline" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Synopsis</x-ui.label>
    <x-ui.textarea wire:model.defer="synopsis" name="synopsis" rows="7">{{ old('synopsis', $title->synopsis) }}</x-ui.textarea>
    <x-ui.error name="synopsis" />
</x-ui.field>

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Tagline</x-ui.label>
        <x-ui.input wire:model.defer="tagline" name="tagline" :value="old('tagline', $title->tagline)" />
        <x-ui.error name="tagline" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>SEO title</x-ui.label>
        <x-ui.input wire:model.defer="meta_title" name="meta_title" :value="old('meta_title', $title->meta_title)" />
        <x-ui.error name="meta_title" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>SEO description</x-ui.label>
    <x-ui.textarea wire:model.defer="meta_description" name="meta_description" rows="3">{{ old('meta_description', $title->meta_description) }}</x-ui.textarea>
    <x-ui.error name="meta_description" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Search keywords</x-ui.label>
    <x-ui.input wire:model.defer="search_keywords" name="search_keywords" :value="old('search_keywords', $title->search_keywords)" />
    <x-ui.error name="search_keywords" />
</x-ui.field>
