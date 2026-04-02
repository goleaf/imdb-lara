@php
$classes = [
    'flex flex-wrap items-center gap-x-2 gap-y-2',
    'py-1 px-2'
];
@endphp

<div
    {{ $attributes->class($classes) }}
    data-slot="navbar"
>
    {{ $slot }}
</div>
