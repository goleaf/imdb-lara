@props([
    'label',
    'href' => null,
    'active' => false,
])

@php
    $resolvedTag = filled($href) ? 'a' : 'span';
    $chipClasses = $active
        ? 'border-sky-300/70 bg-sky-50 text-sky-950 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-100'
        : 'border-black/8 bg-white/70 text-neutral-700 hover:bg-white dark:border-white/10 dark:bg-white/[0.03] dark:text-neutral-200 dark:hover:bg-white/[0.08]';
@endphp

<{{ $resolvedTag }}
    @if (filled($href))
        href="{{ $href }}"
    @endif
    data-slot="aka-attribute-chip"
    {{ $attributes->class([
        'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium transition',
        $chipClasses,
    ]) }}
>
    {{ $label }}
</{{ $resolvedTag }}>
