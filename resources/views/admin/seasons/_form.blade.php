<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('name') }}"
            :name="$fieldName('name')"
            :value="old($fieldOldInputKey('name'), $season->name)"
        />
        <x-ui.error :name="$fieldStatePath('name')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('slug') }}"
            :name="$fieldName('slug')"
            :value="old($fieldOldInputKey('slug'), $season->slug)"
        />
        <x-ui.error :name="$fieldStatePath('slug')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('season_number') }}"
            :name="$fieldName('season_number')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey('season_number'), $season->season_number)"
        />
        <x-ui.error :name="$fieldStatePath('season_number')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('release_year') }}"
            :name="$fieldName('release_year')"
            type="number"
            min="1888"
            max="2100"
            :value="old($fieldOldInputKey('release_year'), $season->release_year)"
        />
        <x-ui.error :name="$fieldStatePath('release_year')" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Summary</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $fieldStatePath('summary') }}" :name="$fieldName('summary')" rows="4">{{ old($fieldOldInputKey('summary'), $season->summary) }}</x-ui.textarea>
    <x-ui.error :name="$fieldStatePath('summary')" />
</x-ui.field>

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>SEO title</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('meta_title') }}"
            :name="$fieldName('meta_title')"
            :value="old($fieldOldInputKey('meta_title'), $season->meta_title)"
        />
        <x-ui.error :name="$fieldStatePath('meta_title')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>SEO description</x-ui.label>
        <x-ui.textarea wire:model.defer="{{ $fieldStatePath('meta_description') }}" :name="$fieldName('meta_description')" rows="3">{{ old($fieldOldInputKey('meta_description'), $season->meta_description) }}</x-ui.textarea>
        <x-ui.error :name="$fieldStatePath('meta_description')" />
    </x-ui.field>
</div>
