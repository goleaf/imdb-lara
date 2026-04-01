<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input name="name" :value="old('name', $episode->title->name ?? $episode->name)" />
        <x-ui.error name="name" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input name="slug" :value="old('slug', $episode->title->slug ?? null)" />
        <x-ui.error name="slug" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input name="release_year" type="number" min="1888" max="2100" :value="old('release_year', $episode->title->release_year ?? null)" />
        <x-ui.error name="release_year" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release date</x-ui.label>
        <x-ui.input name="release_date" type="date" :value="old('release_date', $episode->title->release_date?->toDateString() ?? null)" />
        <x-ui.error name="release_date" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Runtime (minutes)</x-ui.label>
        <x-ui.input name="runtime_minutes" type="number" min="1" :value="old('runtime_minutes', $episode->title->runtime_minutes ?? null)" />
        <x-ui.error name="runtime_minutes" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Certification</x-ui.label>
        <x-ui.input name="age_rating" :value="old('age_rating', $episode->title->age_rating ?? null)" />
        <x-ui.error name="age_rating" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Origin country</x-ui.label>
        <x-ui.input name="origin_country" :value="old('origin_country', $episode->title->origin_country ?? null)" />
        <x-ui.error name="origin_country" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Original language</x-ui.label>
        <x-ui.input name="original_language" :value="old('original_language', $episode->title->original_language ?? null)" />
        <x-ui.error name="original_language" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input name="season_number" type="number" min="1" :value="old('season_number', $episode->season_number)" />
        <x-ui.error name="season_number" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Episode number</x-ui.label>
        <x-ui.input name="episode_number" type="number" min="1" :value="old('episode_number', $episode->episode_number)" />
        <x-ui.error name="episode_number" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Absolute number</x-ui.label>
        <x-ui.input name="absolute_number" type="number" min="1" :value="old('absolute_number', $episode->absolute_number)" />
        <x-ui.error name="absolute_number" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Production code</x-ui.label>
        <x-ui.input name="production_code" :value="old('production_code', $episode->production_code)" />
        <x-ui.error name="production_code" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Aired at</x-ui.label>
        <x-ui.input name="aired_at" type="date" :value="old('aired_at', $episode->aired_at?->toDateString())" />
        <x-ui.error name="aired_at" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Publish status</x-ui.label>
        <select
            name="is_published"
            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
        >
            <option value="1" @selected(old('is_published', $episode->title->is_published ?? true) == true)>Published</option>
            <option value="0" @selected(old('is_published', $episode->title->is_published ?? true) == false)>Draft</option>
        </select>
        <x-ui.error name="is_published" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Plot outline</x-ui.label>
    <x-ui.textarea name="plot_outline" rows="3">{{ old('plot_outline', $episode->title->plot_outline ?? null) }}</x-ui.textarea>
    <x-ui.error name="plot_outline" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Synopsis</x-ui.label>
    <x-ui.textarea name="synopsis" rows="6">{{ old('synopsis', $episode->title->synopsis ?? null) }}</x-ui.textarea>
    <x-ui.error name="synopsis" />
</x-ui.field>
