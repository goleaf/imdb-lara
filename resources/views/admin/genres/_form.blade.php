<x-ui.field>
    <x-ui.label>Name</x-ui.label>
    <x-ui.input name="name" :value="old('name', $genre->name)" />
    <x-ui.error name="name" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Slug</x-ui.label>
    <x-ui.input name="slug" :value="old('slug', $genre->slug)" />
    <x-ui.error name="slug" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Description</x-ui.label>
    <x-ui.textarea name="description" rows="5">{{ old('description', $genre->description) }}</x-ui.textarea>
    <x-ui.error name="description" />
</x-ui.field>
