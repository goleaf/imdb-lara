@props([
    'as' => null,
    'href' => null,
    'icon' => null,
    'iconAfter' => 'arrow-right',
    'openInNewTab' => false,
    'size' => 'sm',
])

@php
    $resolvedAs = $as ?? (filled($href) ? 'a' : 'button');
    $resolvedIconAfter = $iconAfter;

    if ($openInNewTab && $resolvedIconAfter === 'arrow-right') {
        $resolvedIconAfter = 'arrow-top-right-on-square';
    }

    $lightActionClasses = [
        '[--color-primary:var(--color-neutral-50)]',
        'hover:[--color-primary:var(--color-white)]',
        '[--color-primary-content:var(--color-neutral-50)]',
        'hover:[--color-primary-content:var(--color-white)]',
        '!border-white/18',
        'hover:!border-white/32',
        '!bg-white/[0.03]',
        'hover:!bg-white/[0.08]',
        '[&_[data-text]]:!text-current',
    ];
@endphp

<x-ui.button
    :as="$resolvedAs"
    :href="$href"
    variant="outline"
    :size="$size"
    :icon="$icon"
    :iconAfter="$resolvedIconAfter"
    {{ $attributes
        ->class($lightActionClasses)
        ->merge([
            'target' => $openInNewTab ? '_blank' : null,
            'rel' => $openInNewTab ? 'noopener noreferrer' : null,
        ]) }}
>
    {{ $slot }}
</x-ui.button>
