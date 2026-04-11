@props([
    'sections' => [],
])

<div class="sb-shell-topnav-main" data-slot="top-navigation">
    @foreach ($sections as $section)
        @continue(blank($section['items'] ?? []))

        <section class="sb-shell-topnav-section" aria-label="{{ $section['label'] }}">
            <p class="sb-shell-topnav-label">{{ $section['label'] }}</p>

            <x-ui.navbar class="sb-shell-topnav-track !px-0 !py-0" aria-label="{{ $section['label'] }} navigation">
                @foreach ($section['items'] as $item)
                    <x-ui.navbar.item
                        :href="$item['href']"
                        :label="$item['label']"
                        :icon="$item['icon']"
                        class="sb-shell-topnav-item"
                        :active="$item['active']"
                    />
                @endforeach
            </x-ui.navbar>
        </section>
    @endforeach
</div>
