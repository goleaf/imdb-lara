@props([
    'name' => null,
    'invalid' => null,
])

@php
    $resolvedName = $name ?? $attributes->get('name');
    $errorBag = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag;
    $invalid ??= $resolvedName && $errorBag->has($resolvedName);

    $classes = [
        'min-h-10 w-full rounded-box border bg-white px-3 text-sm text-neutral-800 shadow-xs transition',
        'dark:bg-neutral-900 dark:text-neutral-200',
        'focus:outline-none focus:ring-2 focus:ring-offset-0',
        'border-black/10 focus:border-black/15 focus:ring-neutral-900/15 dark:border-white/15 dark:focus:border-white/20 dark:focus:ring-neutral-100/15' => ! $invalid,
        'border-red-600/30 focus:border-red-600/30 focus:ring-red-600/20 dark:border-red-400/30 dark:focus:border-red-400/30 dark:focus:ring-red-400/20' => $invalid,
    ];
@endphp

<select
    @if ($resolvedName) name="{{ $resolvedName }}" @endif
    data-slot="control"
    @if ($invalid) aria-invalid="true" @endif
    {{ $attributes->except(['class', 'name'])->class(Arr::toCssClasses($classes)) }}
>
    {{ $slot }}
</select>
