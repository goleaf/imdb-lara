{{-- resources/views/components/brand.blade.php --}}
@aware([
    'href' => '#',
    'logo' => null,
    'name' => '',
    'alt' => '',
    'target' => '_self',
    'logoClass' => '',
    'nameClass' => '',
    'description' => null,
    'descriptionClass' => '',
    'contentClass' => '',
])

@props([
    'href' => '#',
    'logo' => null,
    'name' => '',
    'alt' => '',
    'target' => '_self',
    'logoClass' => '',
    'nameClass' => '',
    'description' => null,
    'descriptionClass' => '',
    'contentClass' => '',
])

@php
    $hasLogo = is_string($logo)
        ? filled($logo)
        : ($logo instanceof \Illuminate\View\ComponentSlot ? $logo->isNotEmpty() : filled($logo));
    $hasDescription = is_string($description)
        ? filled($description)
        : ($description instanceof \Illuminate\View\ComponentSlot ? $description->isNotEmpty() : filled($description));
@endphp

<a href="{{ $href }}" target="{{ $target }}"
    {{ $attributes->merge(['class' => 'flex items-center justify-center gap-x-3 text-decoration-none hover:opacity-80 transition-opacity text-black dark:text-white']) }}>
    {{-- Logo Section --}}
    @if ($hasLogo)
        <div class="flex-shrink-0">
            @if (isset($logo) && is_string($logo))
                <img src="{{ $logo }}" alt="{{ $alt }} Logo" class="h-8 w-auto {{ $logoClass }}">
            @elseif($logo instanceof \Illuminate\View\ComponentSlot && $logo->isNotEmpty())
                {{ $logo }}
            @endif
        </div>
    @endif

    @if ($name || $hasDescription)
        <div data-slot="brand-copy" @class(['min-w-0', $contentClass])>
            {{-- Brand Name --}}
            @if ($name)
                <div
                    data-slot="brand-name"
                    @class(['font-semibold text-lg', $nameClass])
                >{{ $name }}</div>
            @endif

            @if ($hasDescription)
                @if (is_string($description))
                    <div data-slot="brand-description" @class([$descriptionClass])>{{ $description }}</div>
                @else
                    {{ $description }}
                @endif
            @endif
        </div>
    @endif
</a>
