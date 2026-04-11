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
            :value="old($fieldOldInputKey($fieldPrefix, 'name'), $episode->title?->name ?? $episode->name)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'name')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'slug') }}"
            :name="$fieldName($fieldPrefix, 'slug')"
            :value="old($fieldOldInputKey($fieldPrefix, 'slug'), $episode->title?->slug)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'slug')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'release_year') }}"
            :name="$fieldName($fieldPrefix, 'release_year')"
            type="number"
            min="1888"
            max="2100"
            :value="old($fieldOldInputKey($fieldPrefix, 'release_year'), $episode->title?->release_year)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'release_year')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release date</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'release_date') }}"
            :name="$fieldName($fieldPrefix, 'release_date')"
            type="date"
            :value="old($fieldOldInputKey($fieldPrefix, 'release_date'), $episode->title?->release_date?->toDateString())"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'release_date')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Runtime (minutes)</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'runtime_minutes') }}"
            :name="$fieldName($fieldPrefix, 'runtime_minutes')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey($fieldPrefix, 'runtime_minutes'), $episode->title?->runtime_minutes)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'runtime_minutes')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Certification</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'age_rating') }}"
            :name="$fieldName($fieldPrefix, 'age_rating')"
            :value="old($fieldOldInputKey($fieldPrefix, 'age_rating'), $episode->title?->age_rating)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'age_rating')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Origin country</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'origin_country') }}"
            :name="$fieldName($fieldPrefix, 'origin_country')"
            :value="old($fieldOldInputKey($fieldPrefix, 'origin_country'), $episode->title?->origin_country)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'origin_country')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Original language</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'original_language') }}"
            :name="$fieldName($fieldPrefix, 'original_language')"
            :value="old($fieldOldInputKey($fieldPrefix, 'original_language'), $episode->title?->original_language)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'original_language')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'season_number') }}"
            :name="$fieldName($fieldPrefix, 'season_number')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey($fieldPrefix, 'season_number'), $episode->season_number)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'season_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Episode number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'episode_number') }}"
            :name="$fieldName($fieldPrefix, 'episode_number')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey($fieldPrefix, 'episode_number'), $episode->episode_number)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'episode_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Absolute number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'absolute_number') }}"
            :name="$fieldName($fieldPrefix, 'absolute_number')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey($fieldPrefix, 'absolute_number'), $episode->absolute_number)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'absolute_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Production code</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'production_code') }}"
            :name="$fieldName($fieldPrefix, 'production_code')"
            :value="old($fieldOldInputKey($fieldPrefix, 'production_code'), $episode->production_code)"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'production_code')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Aired at</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'aired_at') }}"
            :name="$fieldName($fieldPrefix, 'aired_at')"
            type="date"
            :value="old($fieldOldInputKey($fieldPrefix, 'aired_at'), $episode->aired_at?->toDateString())"
        />
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'aired_at')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Publish status</x-ui.label>
        <select wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'is_published') }}" name="{{ $fieldName($fieldPrefix, 'is_published') }}" class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
            <option value="1" @selected(old($fieldOldInputKey($fieldPrefix, 'is_published'), $episode->title?->is_published ?? true) == true)>Published</option>
            <option value="0" @selected(old($fieldOldInputKey($fieldPrefix, 'is_published'), $episode->title?->is_published ?? true) == false)>Draft</option>
        </select>
        <x-ui.error :name="$fieldStatePath($fieldPrefix, 'is_published')" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Plot outline</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'plot_outline') }}" :name="$fieldName($fieldPrefix, 'plot_outline')" rows="3">{{ old($fieldOldInputKey($fieldPrefix, 'plot_outline'), $episode->title?->plot_outline) }}</x-ui.textarea>
    <x-ui.error :name="$fieldStatePath($fieldPrefix, 'plot_outline')" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Synopsis</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $fieldStatePath($fieldPrefix, 'synopsis') }}" :name="$fieldName($fieldPrefix, 'synopsis')" rows="6">{{ old($fieldOldInputKey($fieldPrefix, 'synopsis'), $episode->title?->synopsis) }}</x-ui.textarea>
    <x-ui.error :name="$fieldStatePath($fieldPrefix, 'synopsis')" />
</x-ui.field>
