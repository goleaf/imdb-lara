<x-ui.field>
    <x-ui.label>Name</x-ui.label>
    <x-ui.input wire:model.defer="name" name="name" :value="old('name', $akaAttribute->name)" />
    <x-ui.error name="name" />
</x-ui.field>
