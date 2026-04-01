@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'placeholder' => 'search...',
])

@php
    $modelAttrs = collect($attributes->getAttributes())->keys()->first(fn($key) => str_starts_with($key, 'wire:model'));
    $model = $modelAttrs ? $attributes->get($modelAttrs) : null;
    $isLive = $modelAttrs && str_contains($modelAttrs, '.live');
    $livewireId = isset($__livewire) ? $__livewire->getId() : null;
@endphp

<div
    x-data="autocompleteComponent({
        livewire: @js(isset($livewireId)) ? window.Livewire.find(@js($livewireId)) : null,
        isLive: @js($isLive),
        model: @js($model),
    })"
    x-rover
    data-slot="autocomplete"
    {{ $attributes->whereStartsWith('wire:model') }}
    {{ $attributes->whereStartsWith('x-model') }}
    {{ $attributes->only('class')->class([
        'relative text-start [--popup-round:var(--radius-box)] [--popup-padding:--spacing(1)]',
    ]) }}
>
    <div x-ref="autocompleteControl">
        <x-ui.input
            x-rover:input
            x-on:input.stop
            bindScopeToParent
            :name="$name"
            :placeholder="$placeholder"
            {{ $attributes->whereDoesntStartWith('wire:model')->whereDoesntStartWith('x-model')->except('class') }}
        />
    </div>
    <x-ui.autocomplete.items>
        {{ $slot }}
    </x-ui.autocomplete.items>
</div>
