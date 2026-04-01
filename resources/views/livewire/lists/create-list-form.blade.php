@php
    $visibilityIcons = [
        'private' => 'lock-closed',
        'unlisted' => 'eye-slash',
        'public' => 'globe-alt',
    ];
@endphp

<x-ui.card class="!max-w-none">
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-2">
            <x-ui.heading level="h2" size="lg">Create a list</x-ui.heading>
            <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                Create private, unlisted, or public collections that sit alongside your watchlist.
            </x-ui.text>
        </div>

        @if ($statusMessage)
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Name</x-ui.label>
                <x-ui.input wire:model.live="form.name" name="name" placeholder="Friday Night Picks" left-icon="queue-list" />
                <x-ui.error name="form.name" />
            </x-ui.field>

            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Description</x-ui.label>
                <x-ui.textarea wire:model.live="form.description" name="description" rows="4" placeholder="What ties these titles together?" />
                <x-ui.error name="form.description" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Visibility</x-ui.label>
                <x-ui.combobox
                    wire:model.live="form.visibility"
                    class="w-full"
                    placeholder="Select visibility"
                    :invalid="$errors->has('form.visibility')"
                >
                    @foreach ($visibilityOptions as $visibilityOption)
                        <x-ui.combobox.option
                            wire:key="list-visibility-{{ $visibilityOption['value'] }}"
                            value="{{ $visibilityOption['value'] }}"
                            :icon="$visibilityIcons[$visibilityOption['value']] ?? 'globe-alt'"
                        >
                            {{ $visibilityOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
                <x-ui.error name="form.visibility" />
            </x-ui.field>
        </div>

        <div class="flex justify-end">
            <x-ui.button type="submit" icon="plus">
                Create list
            </x-ui.button>
        </div>
    </form>
</x-ui.card>
