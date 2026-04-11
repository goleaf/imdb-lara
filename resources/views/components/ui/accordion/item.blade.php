@aware([
    'reverse' => false
])

@props([
    'disabled' => false,
    'trigger' => null,
    'expanded' => false
])

<div role="region" x-data="accordionItem({ expanded: @js($expanded), disabled: @js($disabled) })"
    data-slot="accordion-item"
    {{ $attributes->class(
        Arr::toCssClasses([
            'dark:text-white text-gray-800 not-last:border-b border-black/10 dark:border-white/10 text-start',
            'opacity-50' => $disabled,
        ]),
    ) }}>

    @if ($trigger)
        <x-ui.accordion.trigger>{{ $trigger }}</x-ui.accordion.trigger>
        <x-ui.accordion.content>{{ $slot }}</x-ui.accordion.content>
    @else
        {{ $slot }}
    @endif

</div>
