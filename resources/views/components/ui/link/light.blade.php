@props([
    'href' => null,
    'icon' => null,
    'iconAfter' => null,
    'iconVariant' => 'outline',
    'openInNewTab' => false,
    'variant' => 'ghost',
])

@php
    $lightLinkClasses = [
        '[--color-primary-content:var(--color-neutral-50)]',
        'hover:[--color-primary-content:var(--color-white)]',
        '!text-[var(--color-primary-content)]',
        'hover:!text-[var(--color-primary-content)]',
        'decoration-white/20',
        'hover:decoration-white/45',
        '[&_span]:!text-current',
    ];
@endphp

<x-ui.link
    :href="$href"
    :icon="$icon"
    :iconAfter="$iconAfter"
    :iconVariant="$iconVariant"
    :openInNewTab="$openInNewTab"
    :variant="$variant"
    {{ $attributes->class($lightLinkClasses) }}
>
    {{ $slot }}
</x-ui.link>
