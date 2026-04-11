<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'name') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'name')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'name'), $season->name)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'name')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'slug') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'slug')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'slug'), $season->slug)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'slug')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Season number</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'season_number') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'season_number')"
            type="number"
            min="1"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'season_number'), $season->season_number)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'season_number')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Release year</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'release_year') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'release_year')"
            type="number"
            min="1888"
            max="2100"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'release_year'), $season->release_year)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'release_year')" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Summary</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'summary') }}" :name="$this->adminFieldName($fieldPrefix ?? null, 'summary')" rows="4">{{ old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'summary'), $season->summary) }}</x-ui.textarea>
    <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'summary')" />
</x-ui.field>

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>SEO title</x-ui.label>
        <x-ui.input
            wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'meta_title') }}"
            :name="$this->adminFieldName($fieldPrefix ?? null, 'meta_title')"
            :value="old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'meta_title'), $season->meta_title)"
        />
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'meta_title')" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>SEO description</x-ui.label>
        <x-ui.textarea wire:model.defer="{{ $this->adminFieldStatePath($fieldPrefix ?? null, 'meta_description') }}" :name="$this->adminFieldName($fieldPrefix ?? null, 'meta_description')" rows="3">{{ old($this->adminFieldOldInputKey($fieldPrefix ?? null, 'meta_description'), $season->meta_description) }}</x-ui.textarea>
        <x-ui.error :name="$this->adminFieldStatePath($fieldPrefix ?? null, 'meta_description')" />
    </x-ui.field>
</div>
