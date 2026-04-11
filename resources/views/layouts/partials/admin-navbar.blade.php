<x-ui.navbar class="hidden flex-1 overflow-x-auto px-0 py-0 lg:flex" aria-label="Admin workspace shortcuts">
    @foreach ($adminNavbarItems as $item)
        <x-ui.navbar.item
            :href="$item['href']"
            :label="$item['label']"
            :icon="$item['icon']"
            :active="$item['active']"
            class="shrink-0"
        />
    @endforeach
</x-ui.navbar>
