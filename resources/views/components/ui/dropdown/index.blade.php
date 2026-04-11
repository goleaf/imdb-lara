@props([
    'position' => 'bottom-center',
    'teleport' => 'body',
    'portal' => false,
    'trap' => false,
    'offset' => 6,
    'checkbox' => false,
    'radio' => false,
    'resetFocus' => false
])

@php
    $isDefaultDropdownVariant = $checkbox || $radio;
    $classes = [
        'isolate z-50',
        'grid grid-cols-[auto_1fr_auto]' => !$isDefaultDropdownVariant ,
        'grid grid-cols-[auto_auto_1fr_auto]' => $isDefaultDropdownVariant,
        '[:where(&)]:max-w-96 [:where(&)]:min-w-40 text-start',
        'bg-white dark:bg-neutral-900 border border-black/10 dark:border-white/10',
        '[--dropdown-radius:var(--radius-box)] [--dropdown-padding:--spacing(.75)]
         rounded-(--dropdown-radius) p-(--dropdown-padding) space-y-1',
    ];  
@endphp

<div {{ $attributes }}>
    <div
        x-data="dropdownShell({ resetFocus: @js($resetFocus) })"
        wire:ignore
        x-on:keydown.escape.prevent.stop="close($refs.button)"
        x-on:focusin.window="handleFocusInOut($event)"
        x-id="['dropdown-button']"
        wire:key="dropdown-{{ uniqid() }}"
        class="relative"
    >
        <!-- Button -->
        <div 
            x-ref="button"
            {{ $button->attributes }}
            x-on:keydown.tab.prevent.stop="$focus.focus($focus.within($refs.panel).getFirst())"
            x-on:keydown.down.prevent.stop="$focus.focus($focus.within($refs.panel).getFirst())"
            x-on:keydown.space.stop.prevent="toggle()"
            x-on:keydown.enter.stop.prevent="toggle()"
            x-on:click="toggle()"
            x-bind:aria-expanded="open"
            x-bind:data-open="open"
            x-bind:aria-controls="$id('dropdown-button')"
        >
            {{ $button }}
        </div>
        
        @if($portal)
            <template x-teleport="{{ $teleport }}" wire:key="dropdown-portal-{{ uniqid() }}">
        @endif
        
        <div 
            x-show="open"

            @if ($trap)
                x-trap="open"            
            @endif
            
            x-ref="panel"
            x-anchor.{{ $position }}.offset.{{ $offset }}="$refs.button;"
            x-on:keydown.down.prevent.stop="$focus.next()"
            x-on:keydown.up.prevent.stop="$focus.prev()"
            x-on:keydown.home.prevent.stop="$focus.first()"
            x-on:keydown.page-up.prevent.stop="$focus.first()"
            x-on:keydown.end.prevent.stop="$focus.last()"
            x-on:keydown.page-down.prevent.stop="$focus.last()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-on:click.away="close($refs.button)"
            x-bind:id="$id('dropdown-button')"
            style="display: none;"
            @if($radio)
                role="radiogroup"
            @else
                role="menu"
            @endif
            {{ $menu->attributes->class(Arr::toCssClasses($classes)) }}
        >
            {{ $menu }}
        </div>
        
        @if($portal)
            </template>
        @endif
    </div>
</div>
