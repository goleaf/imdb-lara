<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'name') : 'name'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'name') : 'name', $season->name)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'name') : 'name'" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'slug') : 'slug'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'slug') : 'slug', $season->slug)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'slug') : 'slug'" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'season_number') : 'season_number'"
            type="number"
            min="1"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'season_number') : 'season_number', $season->season_number)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'season_number') : 'season_number'" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'release_year') : 'release_year'"
            type="number"
            min="1888"
            max="2100"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'release_year') : 'release_year', $season->release_year)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'release_year') : 'release_year'" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Summary</x-ui.label>
    <x-ui.textarea :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'summary') : 'summary'" rows="4">{{ old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'summary') : 'summary', $season->summary) }}</x-ui.textarea>
    <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'summary') : 'summary'" />
</x-ui.field>

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>SEO title</x-ui.label>
        <x-ui.input
            :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'meta_title') : 'meta_title'"
            :value="old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'meta_title') : 'meta_title', $season->meta_title)"
        />
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'meta_title') : 'meta_title'" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>SEO description</x-ui.label>
        <x-ui.textarea :name="filled($fieldPrefix ?? null) ? sprintf('%s[%s]', $fieldPrefix, 'meta_description') : 'meta_description'" rows="3">{{ old(filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'meta_description') : 'meta_description', $season->meta_description) }}</x-ui.textarea>
        <x-ui.error :name="filled($fieldPrefix ?? null) ? sprintf('%s.%s', $fieldPrefix, 'meta_description') : 'meta_description'" />
    </x-ui.field>
</div>
