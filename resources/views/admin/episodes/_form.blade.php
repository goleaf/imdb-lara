<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('name') }}"
            :name="$fieldName('name')"
            :value="old($fieldOldInputKey('name'), $episode->title?->name ?? $episode->name)"
        />
        <x-ui.error :name="$fieldStatePath('name')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('slug') }}"
            :name="$fieldName('slug')"
            :value="old($fieldOldInputKey('slug'), $episode->title?->slug)"
        />
        <x-ui.error :name="$fieldStatePath('slug')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('release_year') }}"
            :name="$fieldName('release_year')"
            type="number"
            min="1888"
            max="2100"
            :value="old($fieldOldInputKey('release_year'), $episode->title?->release_year)"
        />
        <x-ui.error :name="$fieldStatePath('release_year')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release date</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('release_date') }}"
            :name="$fieldName('release_date')"
            type="date"
            :value="old($fieldOldInputKey('release_date'), $episode->title?->release_date?->toDateString())"
        />
        <x-ui.error :name="$fieldStatePath('release_date')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Runtime (minutes)</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('runtime_minutes') }}"
            :name="$fieldName('runtime_minutes')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey('runtime_minutes'), $episode->title?->runtime_minutes)"
        />
        <x-ui.error :name="$fieldStatePath('runtime_minutes')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Certification</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('age_rating') }}"
            :name="$fieldName('age_rating')"
            :value="old($fieldOldInputKey('age_rating'), $episode->title?->age_rating)"
        />
        <x-ui.error :name="$fieldStatePath('age_rating')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Origin country</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('origin_country') }}"
            :name="$fieldName('origin_country')"
            :value="old($fieldOldInputKey('origin_country'), $episode->title?->origin_country)"
        />
        <x-ui.error :name="$fieldStatePath('origin_country')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Original language</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('original_language') }}"
            :name="$fieldName('original_language')"
            :value="old($fieldOldInputKey('original_language'), $episode->title?->original_language)"
        />
        <x-ui.error :name="$fieldStatePath('original_language')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('season_number') }}"
            :name="$fieldName('season_number')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey('season_number'), $episode->season_number)"
        />
        <x-ui.error :name="$fieldStatePath('season_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Episode number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('episode_number') }}"
            :name="$fieldName('episode_number')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey('episode_number'), $episode->episode_number)"
        />
        <x-ui.error :name="$fieldStatePath('episode_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Absolute number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('absolute_number') }}"
            :name="$fieldName('absolute_number')"
            type="number"
            min="1"
            :value="old($fieldOldInputKey('absolute_number'), $episode->absolute_number)"
        />
        <x-ui.error :name="$fieldStatePath('absolute_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Production code</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('production_code') }}"
            :name="$fieldName('production_code')"
            :value="old($fieldOldInputKey('production_code'), $episode->production_code)"
        />
        <x-ui.error :name="$fieldStatePath('production_code')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Aired at</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $fieldStatePath('aired_at') }}"
            :name="$fieldName('aired_at')"
            type="date"
            :value="old($fieldOldInputKey('aired_at'), $episode->aired_at?->toDateString())"
        />
        <x-ui.error :name="$fieldStatePath('aired_at')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Publish status</x-ui.label>
        <select wire:model.defer="{{ $fieldStatePath('is_published') }}" name="{{ $fieldName('is_published') }}" class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
            <option value="1" @selected(old($fieldOldInputKey('is_published'), $episode->title?->is_published ?? true) == true)>Published</option>
            <option value="0" @selected(old($fieldOldInputKey('is_published'), $episode->title?->is_published ?? true) == false)>Draft</option>
        </select>
        <x-ui.error :name="$fieldStatePath('is_published')" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Plot outline</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $fieldStatePath('plot_outline') }}" :name="$fieldName('plot_outline')" rows="3">{{ old($fieldOldInputKey('plot_outline'), $episode->title?->plot_outline) }}</x-ui.textarea>
    <x-ui.error :name="$fieldStatePath('plot_outline')" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Synopsis</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $fieldStatePath('synopsis') }}" :name="$fieldName('synopsis')" rows="6">{{ old($fieldOldInputKey('synopsis'), $episode->title?->synopsis) }}</x-ui.textarea>
    <x-ui.error :name="$fieldStatePath('synopsis')" />
</x-ui.field>
