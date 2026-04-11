@props([
    'as' => null,
    'href' => null,
    'icon' => null,
    'iconAfter' => null,
    'size' => 'md',
])

@php
    $resolvedAs = $as ?? (filled($href) ? 'a' : 'button');

    $lightOutlineClasses = [
        '!border-white/30',
        'hover:!border-white/55',
        '!bg-white/[0.04]',
        'hover:!bg-white/[0.08]',
        '!text-[#f4eee5]',
        'hover:!text-white',
        '[&_svg]:!text-[#f4eee5]',
        'hover:[&_svg]:!text-white',
    ];
@endphp

<x-ui.button
    :as="$resolvedAs"
    :href="$href"
    variant="outline"
    :size="$size"
    :icon="$icon"
    :iconAfter="$iconAfter"
    {{ $attributes->class($lightOutlineClasses) }}
>
    {{ $slot }}
</x-ui.button>
