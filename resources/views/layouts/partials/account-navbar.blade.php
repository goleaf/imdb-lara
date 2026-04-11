@php
    $navigationItems = collect($accountNavigationSections)
        ->slice(1)
        ->pluck('items')
        ->flatten(1)
        ->values();

    if ($navigationItems->isEmpty()) {
        $navigationItems = collect($accountNavigationSections)
            ->pluck('items')
            ->flatten(1)
            ->values();
    }
@endphp

<x-ui.navbar class="hidden flex-1 overflow-x-auto px-0 py-0 lg:flex" aria-label="Account workspace shortcuts">
    @foreach ($navigationItems as $item)
        <x-ui.navbar.item
            :href="$item['href']"
            :label="$item['label']"
            :icon="$item['icon']"
            :active="$item['active']"
            class="shrink-0"
        />
    @endforeach
</x-ui.navbar>
