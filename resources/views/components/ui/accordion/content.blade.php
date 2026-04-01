<div
    style="display: none"
    x-cloak
    x-show="isVisible"
    x-transition.opacity.duration.150ms
    x-bind:aria-labelledby="triggerId"
    x-bind:id="panelId"
    data-slot="accordion-content"
    role="region"
>
    <div {{ $attributes->merge(['class' => 'px-6 pb-4 pt-2']) }}>
        {{ $slot }}
    </div>
</div>
