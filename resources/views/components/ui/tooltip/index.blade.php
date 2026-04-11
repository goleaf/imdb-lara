@props([
    'placement' => 'top',
    'variant' => 'default',
])

<div x-data="tooltipComponent({ placement: @js($placement) })"
    {{ $attributes->merge(['class' => 'relative flex']) }}
>
   <span
        x-ref="tooltipTrigger"
        
        @if($variant === 'button')
            role="button"
            x-on:click="showTooltip()"
        @endif
        
        tabindex="0"
        x-on:mouseenter="showTooltip()"
        x-on:mouseleave="hideTooltip()"
        x-on:focus="showTooltip()"
        x-on:blur="hideTooltip()"
        class="cursor-pointer"
    >
        {{ $trigger }}
    </span>

    {{ $slot }}
</div>
