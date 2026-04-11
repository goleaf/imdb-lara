@props([
    'href',
    'label' => 'Back to title page',
])

<x-ui.link
    :href="$href"
    variant="ghost"
    icon="arrow-left"
    class="text-[#f4eee5] decoration-white/20 hover:text-white"
>
    {{ $label }}
</x-ui.link>
