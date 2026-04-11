@php
    $fieldPrefix = $fieldPrefix ?? null;
    $fieldStatePath = static fn (?string $prefix, string $field): string => filled($prefix)
        ? sprintf('%s.%s', $prefix, $field)
        : $field;
    $fieldName = static fn (?string $prefix, string $field): string => filled($prefix)
        ? sprintf('%s[%s]', $prefix, $field)
        : $field;
    $fieldOldInputKey = $fieldStatePath;
@endphp

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'name') }}"
            :name="$fieldName($fieldPrefix, 'name')"
            :value="old($fieldOldInputKey($fieldPrefix, 'name'), $season->name)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'name')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'slug') }}"
            :name="$fieldName($fieldPrefix, 'slug')"
            :value="old($fieldOldInputKey($fieldPrefix, 'slug'), $season->slug)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'slug')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'season_number') }}"
            :name="$fieldName($fieldPrefix, 'season_number')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey($fieldPrefix, 'season_number'), $season->season_number)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'season_number')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'release_year') }}"
            :name="$fieldName($fieldPrefix, 'release_year')"
            type="number"
            min="1888"
            max="2100"
            :value="old($fieldOldInputKey($fieldPrefix, 'release_year'), $season->release_year)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'release_year')" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Summary</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'summary') }}" :name="$fieldName($fieldPrefix, 'summary')" rows="4">{{ old($fieldOldInputKey($fieldPrefix, 'summary'), $season->summary) }}</x-ui.textarea>
    <x-ui.error :name="$fieldStatePath($fieldPrefix, 'summary')" />
</x-ui.field>

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>SEO title</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'meta_title') }}"
            :name="$fieldName($fieldPrefix, 'meta_title')"
            :value="old($fieldOldInputKey($fieldPrefix, 'meta_title'), $season->meta_title)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'meta_title')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>SEO description</x-ui.label>
        <x-ui.textarea wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'meta_description') }}" :name="$fieldName($fieldPrefix, 'meta_description')" rows="3">{{ old($fieldOldInputKey($fieldPrefix, 'meta_description'), $season->meta_description) }}</x-ui.textarea>
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'meta_description')" />
    </x-ui.field>
</div>
