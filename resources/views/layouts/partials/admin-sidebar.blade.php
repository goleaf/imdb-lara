<x-ui.sidebar
    class="border-r border-black/5 bg-white/80 backdrop-blur-xl dark:border-white/5 dark:bg-neutral-950/85"
    scrollable
>
    <x-slot:brand>
        <div class="px-1">
            <x-ui.brand
                :href="route('admin.dashboard')"
                name="Screenbase"
                description="Catalog control"
                nameClass="text-base font-semibold"
                descriptionClass="text-sm text-neutral-500 dark:text-neutral-400 [:has([data-collapsed]_&)_&]:hidden"
                class="justify-start"
            >
                <x-slot:logo>
                    <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-linear-to-br from-sky-400 via-blue-300 to-indigo-500 text-sm font-bold tracking-[0.16em] text-slate-950 shadow-sm ring-1 ring-black/5">
                        SB
                    </span>
                </x-slot:logo>
            </x-ui.brand>
        </div>
    </x-slot:brand>

    @if ($portalUser)
        <div class="px-3 pb-3 pt-2 [:has([data-collapsed]_&)_&]:hidden">
            <x-ui.card class="!max-w-none border border-black/5 bg-white/70 shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <x-ui.avatar
                            :src="$portalUser->avatar_url"
                            :name="$portalUser->name"
                            color="auto"
                            size="md"
                            circle
                        />

                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-50">{{ $portalUser->name }}</div>
                            <x-ui.text size="sm" class="truncate text-neutral-500 dark:text-neutral-400">Staff workspace</x-ui.text>
                        </div>
                    </div>

                    <x-ui.badge color="sky" variant="outline">Admin area</x-ui.badge>
                </div>
            </x-ui.card>
        </div>
    @endif

    <x-ui.navlist class="px-3 pb-6">
        @foreach ($adminNavigationSections as $section)
            <x-ui.navlist.group :label="$section['label']">
                @foreach ($section['items'] as $item)
                    <x-ui.navlist.item
                        :href="$item['href']"
                        :label="$item['label']"
                        :icon="$item['icon']"
                        :active="$item['active']"
                    />
                @endforeach
            </x-ui.navlist.group>
        @endforeach
    </x-ui.navlist>

    <x-ui.sidebar.push />

    @if ($portalUser)
        <div class="px-3 pb-3">
            <x-ui.dropdown portal position="top-start">
                <x-slot:button>
                    <x-ui.button variant="none" class="!h-auto w-full justify-start rounded-box border border-black/5 bg-white/70 px-3 py-2 text-left shadow-sm transition hover:bg-white dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/[0.05]">
                        <x-ui.avatar
                            :src="$portalUser->avatar_url"
                            :name="$portalUser->name"
                            color="auto"
                            size="sm"
                            circle
                        />

                        <div class="min-w-0 flex-1 [:has([data-collapsed]_&)_&]:hidden">
                            <div class="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-50">{{ $portalUser->name }}</div>
                            <x-ui.text size="sm" class="truncate text-neutral-500 dark:text-neutral-400">Operations menu</x-ui.text>
                        </div>

                        <x-ui.icon name="chevron-up-down" class="size-4 text-neutral-400 [:has([data-collapsed]_&)_&]:hidden" />
                    </x-ui.button>
                </x-slot:button>

                <x-slot:menu class="!w-[15rem]">
                    <x-ui.dropdown.item :href="route('admin.dashboard')" icon="chart-bar-square">Dashboard</x-ui.dropdown.item>
                    <x-ui.dropdown.item :href="route('public.home')" icon="arrow-up-right">Public site</x-ui.dropdown.item>
                    <x-ui.dropdown.separator />
                    <livewire:auth.logout-button presentation="dropdown-item" :key="'admin-sidebar-logout'" />
                </x-slot:menu>
            </x-ui.dropdown>
        </div>
    @endif
</x-ui.sidebar>
