@props([
    'reverse' => false
])

<div 
    x-data="{ active: null }"
    data-slot="accordion"
    {{ $attributes->merge([
        'class'=>"w-full flex flex-col"
    ]) }}
>
    {{ $slot }}
</div>
