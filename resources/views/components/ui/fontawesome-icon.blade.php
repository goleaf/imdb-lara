@props([
    'name' => null,
    'style' => 'solid',
    'fixedWidth' => false,
])

@php
    $name = trim((string) $name);
    $styleClass = match ($style) {
        'brands' => 'fa-brands',
        'regular' => 'fa-regular',
        default => 'fa-solid',
    };
@endphp

@if ($name !== '')
    <span
        {{ $attributes->class([
            $styleClass,
            'fa-'.$name,
            'fa-fw' => $fixedWidth,
        ]) }}
        aria-hidden="true"
        data-slot="fontawesome-icon"
    ></span>
@endif
