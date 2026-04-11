<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'name') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'name')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'name'), $episode->title?->name ?? $episode->name)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'name')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'slug') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'slug')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'slug'), $episode->title?->slug)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'slug')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'release_year') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'release_year')"
            type="number"
            min="1888"
            max="2100"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'release_year'), $episode->title?->release_year)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'release_year')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Release date</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'release_date') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'release_date')"
            type="date"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'release_date'), $episode->title?->release_date?->toDateString())"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'release_date')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Runtime (minutes)</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'runtime_minutes') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'runtime_minutes')"
            type="number"
            min="1"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'runtime_minutes'), $episode->title?->runtime_minutes)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'runtime_minutes')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Certification</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'age_rating') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'age_rating')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'age_rating'), $episode->title?->age_rating)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'age_rating')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Origin country</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'origin_country') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'origin_country')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'origin_country'), $episode->title?->origin_country)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'origin_country')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Original language</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'original_language') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'original_language')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'original_language'), $episode->title?->original_language)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'original_language')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'season_number') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'season_number')"
            type="number"
            min="1"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'season_number'), $episode->season_number)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'season_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Episode number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'episode_number') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'episode_number')"
            type="number"
            min="1"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'episode_number'), $episode->episode_number)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'episode_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Absolute number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'absolute_number') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'absolute_number')"
            type="number"
            min="1"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'absolute_number'), $episode->absolute_number)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'absolute_number')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Production code</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'production_code') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'production_code')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'production_code'), $episode->production_code)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'production_code')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Aired at</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'aired_at') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'aired_at')"
            type="date"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'aired_at'), $episode->aired_at?->toDateString())"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'aired_at')" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Publish status</x-ui.label>
        <select wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'is_published') }}" name="{{ $this->adminFieldName($fieldPrefix ?? null, 'is_published') }}" class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
            <option value="1" @selected(old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'is_published'), $episode->title?->is_published ?? true) == true)>Published</option>
            <option value="0" @selected(old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'is_published'), $episode->title?->is_published ?? true) == false)>Draft</option>
        </select>
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'is_published')" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Plot outline</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'plot_outline') }}" :name="$this->adminFieldName($fieldPrefix ?? null, 'plot_outline')" rows="3">{{ old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'plot_outline'), $episode->title?->plot_outline) }}</x-ui.textarea>
    <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'plot_outline')" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Synopsis</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'synopsis') }}" :name="$this->adminFieldName($fieldPrefix ?? null, 'synopsis')" rows="6">{{ old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'synopsis'), $episode->title?->synopsis) }}</x-ui.textarea>
    <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'synopsis')" />
</x-ui.field>
