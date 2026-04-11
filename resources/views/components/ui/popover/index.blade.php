@props([
    'onHover' => false,
])

<div 
    x-data="popoverRoot()"
    x-on:click.away="hide()"
    x-on:keydown.escape="hide()"
    class="relative inline-block [--popup-round:var(--radius-box)] [--popup-padding:--spacing(1)]"
>
    {{ $slot }}
</div>


