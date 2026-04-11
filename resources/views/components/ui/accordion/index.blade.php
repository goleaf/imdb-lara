@props([
    'reverse' => false
])

<div 
    x-data="accordionRoot()"
    data-slot="accordion"
    {{ $attributes->merge([
        'class'=>"w-full flex flex-col"
    ]) }}
>
    {{ $slot }}
</div>
