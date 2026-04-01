@props([
    'openInNewTab' => null,
    'primary' => true,
    'variant' => null,
    'icon' => null,
    'iconAfter' => null,
    'iconVariant' => 'outline',
])

@php
$resolvedIconAfter = $iconAfter;

if (! filled($resolvedIconAfter)) {
    if ($openInNewTab) {
        $resolvedIconAfter = 'arrow-top-right-on-square';
    } elseif ($variant === 'ghost') {
        $resolvedIconAfter = 'arrow-right';
    }
}

$classes = [
    'inline-flex items-center gap-1.5 font-medium text-base text-start',
    'underline-offset-[6px] hover:decoration-current',
    match ($variant) {
        'ghost' => 'no-underline hover:underline',
        'soft' => 'no-underline',
        default => 'underline',
    },
    match ($variant) {
        'soft' => 'text-neutral-500 dark:text-white/70 hover:text-neutral-800 dark:hover:text-white',
        default => match ($primary) {
            true => 'text-[var(--color-primary-content)] decoration-[color-mix(in_oklab,var(--color-primary-content),transparent_80%)]',
            false => 'text-neutral-800 dark:text-white decoration-neutral-800/20 dark:decoration-white/20',
        },
    },
];
@endphp

<a {{ $attributes->class(Arr::toCssClasses($classes)) }} data-slot="link" @if($openInNewTab) target="_blank" @endif>
    @if ($icon)
        <x-ui.icon :name="$icon" :variant="$iconVariant" class="size-4 shrink-0 !text-current" data-slot="link-icon" />
    @endif

    <span>{{ $slot }}</span>

    @if ($resolvedIconAfter)
        <x-ui.icon :name="$resolvedIconAfter" :variant="$iconVariant" class="size-4 shrink-0 !text-current" data-slot="link-icon:after" />
    @endif
</a>
