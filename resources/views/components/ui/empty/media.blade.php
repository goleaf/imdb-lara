@props([
    'variant' => 'default', // default, icon
])

<div {{ $attributes->class('mb-4 inline-flex items-center justify-center') }} data-slot="empty-media">
    {{ $slot }}
</div>
