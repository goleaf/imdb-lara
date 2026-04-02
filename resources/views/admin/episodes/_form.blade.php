<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'name') : 'name'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'name') : 'name', $episode->title?->name ?? $episode->name)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'name') : 'name'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'slug') : 'slug'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'slug') : 'slug', $episode->title?->slug)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'slug') : 'slug'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'release_year') : 'release_year'"
            type="number"
            min="1888"
            max="2100"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'release_year') : 'release_year', $episode->title?->release_year)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'release_year') : 'release_year'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release date</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'release_date') : 'release_date'"
            type="date"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'release_date') : 'release_date', $episode->title?->release_date?->toDateString())"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'release_date') : 'release_date'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Runtime (minutes)</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'runtime_minutes') : 'runtime_minutes'"
            type="number"
            min="1"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'runtime_minutes') : 'runtime_minutes', $episode->title?->runtime_minutes)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'runtime_minutes') : 'runtime_minutes'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Certification</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'age_rating') : 'age_rating'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'age_rating') : 'age_rating', $episode->title?->age_rating)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'age_rating') : 'age_rating'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Origin country</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'origin_country') : 'origin_country'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'origin_country') : 'origin_country', $episode->title?->origin_country)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'origin_country') : 'origin_country'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Original language</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'original_language') : 'original_language'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'original_language') : 'original_language', $episode->title?->original_language)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'original_language') : 'original_language'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'season_number') : 'season_number'"
            type="number"
            min="1"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'season_number') : 'season_number', $episode->season_number)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'season_number') : 'season_number'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Episode number</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'episode_number') : 'episode_number'"
            type="number"
            min="1"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'episode_number') : 'episode_number', $episode->episode_number)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'episode_number') : 'episode_number'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Absolute number</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'absolute_number') : 'absolute_number'"
            type="number"
            min="1"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'absolute_number') : 'absolute_number', $episode->absolute_number)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'absolute_number') : 'absolute_number'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Production code</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'production_code') : 'production_code'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'production_code') : 'production_code', $episode->production_code)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'production_code') : 'production_code'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Aired at</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'aired_at') : 'aired_at'"
            type="date"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'aired_at') : 'aired_at', $episode->aired_at?->toDateString())"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'aired_at') : 'aired_at'" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Publish status</x-ui.label>
        <select
            name="{{ filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'is_published') : 'is_published' }}"
            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
        >
            <option value="1" @selected(old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'is_published') : 'is_published', $episode->title?->is_published ?? true) == true)>Published</option>
            <option value="0" @selected(old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'is_published') : 'is_published', $episode->title?->is_published ?? true) == false)>Draft</option>
        </select>
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'is_published') : 'is_published'" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Plot outline</x-ui.label>
    <x-ui.textarea :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'plot_outline') : 'plot_outline'" rows="3">{{ old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'plot_outline') : 'plot_outline', $episode->title?->plot_outline) }}</x-ui.textarea>
    <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'plot_outline') : 'plot_outline'" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Synopsis</x-ui.label>
    <x-ui.textarea :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'synopsis') : 'synopsis'" rows="6">{{ old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'synopsis') : 'synopsis', $episode->title?->synopsis) }}</x-ui.textarea>
    <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'synopsis') : 'synopsis'" />
</x-ui.field>
