<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input name="name" :value="old('name', $season->name)" />
        <x-ui.error name="name" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input name="slug" :value="old('slug', $season->slug)" />
        <x-ui.error name="slug" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input name="season_number" type="number" min="1" :value="old('season_number', $season->season_number)" />
        <x-ui.error name="season_number" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input name="release_year" type="number" min="1888" max="2100" :value="old('release_year', $season->release_year)" />
        <x-ui.error name="release_year" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Summary</x-ui.label>
    <x-ui.textarea name="summary" rows="4">{{ old('summary', $season->summary) }}</x-ui.textarea>
    <x-ui.error name="summary" />
</x-ui.field>

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>SEO title</x-ui.label>
        <x-ui.input name="meta_title" :value="old('meta_title', $season->meta_title)" />
        <x-ui.error name="meta_title" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>SEO description</x-ui.label>
        <x-ui.textarea name="meta_description" rows="3">{{ old('meta_description', $season->meta_description) }}</x-ui.textarea>
        <x-ui.error name="meta_description" />
    </x-ui.field>
</div>
