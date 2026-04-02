<x-ui.navbar.item
    :href="route('public.discover')"
    label="Discover"
    icon="sparkles"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.discover')"
/>
<x-ui.navbar.item
    :href="route('account.dashboard')"
    label="Dashboard"
    icon="home"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('account.dashboard')"
/>
<x-ui.navbar.item
    :href="route('account.watchlist')"
    label="Watchlist"
    icon="bookmark"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('account.watchlist')"
/>
<x-ui.navbar.item
    :href="route('account.lists.index')"
    label="Lists"
    icon="queue-list"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('account.lists.*')"
/>
<x-ui.navbar.item
    :href="route('account.settings')"
    label="Settings"
    icon="cog-6-tooth"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('account.settings')"
/>
<x-ui.navbar.item
    :href="route('public.titles.index')"
    label="Browse Titles"
    icon="film"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.titles.*') || request()->routeIs('public.genres.*') || request()->routeIs('public.years.*')"
/>
<x-ui.navbar.item
    :href="route('public.people.index')"
    label="Browse People"
    icon="users"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.people.*')"
/>
<x-ui.navbar.item
    :href="route('public.search')"
    label="Search"
    icon="magnifying-glass"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.search')"
/>

@can('access-admin-area')
    <x-ui.navbar.item
        :href="route('admin.dashboard')"
        label="Admin"
        icon="shield-check"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('admin.*')"
    />
@endcan

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <x-ui.button
        type="submit"
        variant="ghost"
        size="sm"
        icon="arrow-right-start-on-rectangle"
    >
        Sign out
    </x-ui.button>
</form>
