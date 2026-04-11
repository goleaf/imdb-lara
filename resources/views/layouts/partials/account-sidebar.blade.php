@php
    $user = auth()->user();
@endphp

<x-ui.sidebar
    class="border-r border-black/5 bg-white/80 backdrop-blur-xl dark:border-white/5 dark:bg-neutral-950/85"
    scrollable
>
    <x-slot:brand>
        <div class="px-1">
            <x-ui.brand :href="route('account.dashboard')" name="Screenbase" class="justify-start">
                <x-slot:logo>
                    <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-linear-to-br from-amber-300 via-amber-200 to-orange-400 text-sm font-bold tracking-[0.16em] text-stone-950 shadow-sm ring-1 ring-black/5">
                        SB
                    </span>
                </x-slot:logo>
            </x-ui.brand>

            <x-ui.text size="sm" class="pl-[3.25rem] text-neutral-500 dark:text-neutral-400 [:has([data-collapsed]_&)_&]:hidden">
                Member workspace
            </x-ui.text>
        </div>
    </x-slot:brand>

    @if ($user)
        <div class="px-3 pb-3 pt-2 [:has([data-collapsed]_&)_&]:hidden">
            <x-ui.card class="!max-w-none border border-black/5 bg-white/70 shadow-sm dark:border-white/10 dark:bg-white/[0.03]">
                <div class="flex items-center gap-3">
                    <x-ui.avatar
                        :src="$user->avatar_url"
                        :name="$user->name"
                        color="auto"
                        size="md"
                        circle
                    />

                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-50">{{ $user->name }}</div>
                        <x-ui.text size="sm" class="truncate text-neutral-500 dark:text-neutral-400">{{ '@'.$user->username }}</x-ui.text>
                    </div>
                </div>
            </x-ui.card>
        </div>
    @endif

    <x-ui.navlist class="px-3 pb-6">
        @foreach ($accountNavigationSections as $section)
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

    @if ($user)
        <div class="px-3 pb-3">
            <x-ui.dropdown portal position="top-start">
                <x-slot:button>
                    <x-ui.button variant="none" class="!h-auto w-full justify-start rounded-box border border-black/5 bg-white/70 px-3 py-2 text-left shadow-sm transition hover:bg-white dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/[0.05]">
                        <x-ui.avatar
                            :src="$user->avatar_url"
                            :name="$user->name"
                            color="auto"
                            size="sm"
                            circle
                        />

                        <div class="min-w-0 flex-1 [:has([data-collapsed]_&)_&]:hidden">
                            <div class="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-50">{{ $user->name }}</div>
                            <x-ui.text size="sm" class="truncate text-neutral-500 dark:text-neutral-400">Account menu</x-ui.text>
                        </div>

                        <x-ui.icon name="chevron-up-down" class="size-4 text-neutral-400 [:has([data-collapsed]_&)_&]:hidden" />
                    </x-ui.button>
                </x-slot:button>

                <x-slot:menu class="!w-[15rem]">
                    <x-ui.dropdown.item :href="route('account.dashboard')" icon="home">Dashboard</x-ui.dropdown.item>
                    <x-ui.dropdown.item :href="route('account.settings')" icon="cog-6-tooth">Settings</x-ui.dropdown.item>
                    <x-ui.dropdown.item :href="route('public.home')" icon="arrow-up-right">Public site</x-ui.dropdown.item>
                    <x-ui.dropdown.separator />
                    <form method="POST" action="{{ route('logout') }}" class="contents">
                        @csrf
                        <x-ui.dropdown.item as="button" type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                            Sign out
                        </x-ui.dropdown.item>
                    </form>
                </x-slot:menu>
            </x-ui.dropdown>
        </div>
    @endif
</x-ui.sidebar>
