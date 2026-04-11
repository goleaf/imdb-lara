<div class="contents">
    @if ($presentation === 'dropdown-item')
        <x-ui.dropdown.item
            as="button"
            type="button"
            wire:click="logout"
            wire:loading.attr="disabled"
            :icon="$icon"
            :variant="$variant"
        >
            {{ $label }}
        </x-ui.dropdown.item>
    @else
        <x-ui.button
            type="button"
            wire:click="logout"
            wire:loading.attr="disabled"
            :variant="$variant"
            :size="$size"
            :icon="$icon"
            :class="$buttonClass"
        >
            {{ $label }}
        </x-ui.button>
    @endif
</div>
