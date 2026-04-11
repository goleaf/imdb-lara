<div class="rounded-box border border-black/10 p-4 dark:border-white/10">
    <form wire:submit="save" class="space-y-3">
        <div class="grid gap-3 md:grid-cols-2">
            <x-ui.field>
                <x-ui.label>Department</x-ui.label>
                <x-ui.input wire:model.defer="department" name="department" />
                <x-ui.error name="department" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Profession</x-ui.label>
                <x-ui.input wire:model.defer="profession" name="profession" />
                <x-ui.error name="profession" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Sort order</x-ui.label>
                <x-ui.input wire:model.defer="sort_order" name="sort_order" type="number" min="0" />
                <x-ui.error name="sort_order" />
            </x-ui.field>

            <label class="flex items-center gap-2 text-sm md:self-end">
                <x-ui.checkbox wire:model="is_primary" name="is_primary" value="1" label="Primary profession" />
            </label>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
            <x-ui.button type="submit" size="sm" :icon="$professionRecord ? 'check' : 'plus'">
                {{ $professionRecord ? 'Save profession' : 'Add profession' }}
            </x-ui.button>
        </div>
    </form>

    @if ($professionRecord)
        <div class="mt-3 flex justify-end">
            <x-ui.button type="button" wire:click="delete" variant="outline" color="red" size="sm" icon="trash">
                Delete
            </x-ui.button>
        </div>
    @endif
</div>
