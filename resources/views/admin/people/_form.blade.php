<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Name</x-ui.label>
        <x-ui.input wire:model.defer="name" name="name" :value="old('name', $person->name)" />
        <x-ui.error name="name" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Slug</x-ui.label>
        <x-ui.input wire:model.defer="slug" name="slug" :value="old('slug', $person->slug)" />
        <x-ui.error name="slug" />
    </x-ui.field>

    <x-ui.field class="lg:col-span-2">
        <x-ui.label>Alternate names</x-ui.label>
        <x-ui.input wire:model.defer="alternate_names" name="alternate_names" :value="old('alternate_names', $person->alternate_names)" />
        <x-ui.error name="alternate_names" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Known for department</x-ui.label>
        <x-ui.input wire:model.defer="known_for_department" name="known_for_department" :value="old('known_for_department', $person->known_for_department)" />
        <x-ui.error name="known_for_department" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Popularity rank</x-ui.label>
        <x-ui.input wire:model.defer="popularity_rank" name="popularity_rank" type="number" min="1" :value="old('popularity_rank', $person->popularity_rank)" />
        <x-ui.error name="popularity_rank" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Birth date</x-ui.label>
        <x-ui.input wire:model.defer="birth_date" name="birth_date" type="date" :value="old('birth_date', $person->birth_date?->toDateString())" />
        <x-ui.error name="birth_date" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Death date</x-ui.label>
        <x-ui.input wire:model.defer="death_date" name="death_date" type="date" :value="old('death_date', $person->death_date?->toDateString())" />
        <x-ui.error name="death_date" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Birth place</x-ui.label>
        <x-ui.input wire:model.defer="birth_place" name="birth_place" :value="old('birth_place', $person->birth_place)" />
        <x-ui.error name="birth_place" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Death place</x-ui.label>
        <x-ui.input wire:model.defer="death_place" name="death_place" :value="old('death_place', $person->death_place)" />
        <x-ui.error name="death_place" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Nationality</x-ui.label>
        <x-ui.input wire:model.defer="nationality" name="nationality" :value="old('nationality', $person->nationality)" />
        <x-ui.error name="nationality" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Publish status</x-ui.label>
        <x-ui.native-select wire:model.defer="is_published" name="is_published">
            <option value="1" @selected(old('is_published', $person->is_published) == true)>Published</option>
            <option value="0" @selected(old('is_published', $person->is_published) == false)>Draft</option>
        </x-ui.native-select>
        <x-ui.error name="is_published" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>Short biography</x-ui.label>
    <x-ui.textarea wire:model.defer="short_biography" name="short_biography" rows="3">{{ old('short_biography', $person->short_biography) }}</x-ui.textarea>
    <x-ui.error name="short_biography" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Biography</x-ui.label>
    <x-ui.textarea wire:model.defer="biography" name="biography" rows="8">{{ old('biography', $person->biography) }}</x-ui.textarea>
    <x-ui.error name="biography" />
</x-ui.field>

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>SEO title</x-ui.label>
        <x-ui.input wire:model.defer="meta_title" name="meta_title" :value="old('meta_title', $person->meta_title)" />
        <x-ui.error name="meta_title" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Search keywords</x-ui.label>
        <x-ui.input wire:model.defer="search_keywords" name="search_keywords" :value="old('search_keywords', $person->search_keywords)" />
        <x-ui.error name="search_keywords" />
    </x-ui.field>
</div>

<x-ui.field>
    <x-ui.label>SEO description</x-ui.label>
    <x-ui.textarea wire:model.defer="meta_description" name="meta_description" rows="3">{{ old('meta_description', $person->meta_description) }}</x-ui.textarea>
    <x-ui.error name="meta_description" />
</x-ui.field>
