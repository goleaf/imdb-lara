@aware([
    'disabled' => false,
    'reverse' => false
])

<button
    type="button"
    data-slot="accordion-trigger"
    @disabled($disabled)
    {{ $attributes->class(Arr::toCssClasses(['flex w-full items-center gap-2 justify-start px-6 py-4 text-xl font-bold dark:text-white', 'cursor-pointer' => !$disabled, 'flex-row-reverse' => $reverse])) }}
    x-on:click="toggle()"
    x-bind:aria-controls="panelId"
    x-bind:aria-expanded="isVisible.toString()"
    x-bind:id="triggerId"
>
    <span class="flex-1 text-start font-normal text-base">{{ $slot }}</span>
    <span style="display: none" x-cloak x-show="isVisible"><x-ui.icon class="size-5" name="chevron-up" /></span>
    <span x-show="!isVisible"> <x-ui.icon class="size-5" name="chevron-down" /></span>
</button>
