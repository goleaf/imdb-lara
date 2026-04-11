@props([
    'href' => null,
    'current' => false,
    'disabled' => false,
    'size' => 'sm',
    'icon' => null,
    'iconAfter' => null,
    'color' => 'amber',
])

@php
    $as = match (true) {
        $current, $disabled => 'div',
        filled($href) => 'a',
        default => 'button',
    };

    $variant = $current ? 'primary' : ($disabled ? 'none' : 'outline');

    $classes = [
        'min-w-9 justify-center !rounded-full !text-sm !font-semibold shadow-none',
        '!px-3' => blank($icon) && blank($iconAfter),
        '!border-black/8 !bg-black/[0.03] !text-neutral-400 dark:!border-white/10 dark:!bg-white/[0.03] dark:!text-neutral-500' => $disabled,
        '!border-amber-300/85 !bg-amber-300 !text-amber-950 shadow-[0_14px_30px_rgba(217,119,6,0.18)] dark:!border-amber-400 dark:!bg-amber-400 dark:!text-amber-950' => $current,
    ];
@endphp

<x-ui.button
    :as="$as"
    :href="$href"
    :variant="$variant"
    :color="$disabled ? null : $color"
    :size="$size"
    :icon="$icon"
    :iconAfter="$iconAfter"
    {{ $attributes->class($classes) }}
>
    {{ $slot }}
</x-ui.button>
