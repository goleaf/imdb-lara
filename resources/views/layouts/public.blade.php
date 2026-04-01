@extends('layouts.app')

@section('navbar')
    <x-ui.navbar.item
        :href="route('public.discover')"
        label="Discover"
        icon="sparkles"
        :active="request()->routeIs('public.discover')"
    />
    <x-ui.navbar.item
        :href="route('public.titles.index')"
        label="Titles"
        icon="film"
        :active="request()->routeIs('public.titles.*')"
    />
    <x-ui.navbar.item
        :href="route('public.people.index')"
        label="People"
        icon="users"
        :active="request()->routeIs('public.people.*')"
    />
    <x-ui.navbar.item
        :href="route('public.search')"
        label="Search"
        icon="magnifying-glass"
        :active="request()->routeIs('public.search')"
    />

    <div class="hidden md:block">
        <x-ui.theme-switcher />
    </div>

    @auth
        @can('access-admin-area')
            <x-ui.navbar.item
                :href="route('admin.dashboard')"
                label="Admin"
                icon="shield-check"
                :active="request()->routeIs('admin.*')"
            />
        @endcan

        <x-ui.navbar.item
            :href="route('account.watchlist')"
            label="Watchlist"
            icon="bookmark"
            :active="request()->routeIs('account.*')"
        />

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
    @else
        <x-ui.button
            as="a"
            :href="route('login')"
            variant="ghost"
            size="sm"
            icon="arrow-right-end-on-rectangle"
        >
            Sign in
        </x-ui.button>
        <x-ui.button
            as="a"
            :href="route('register')"
            size="sm"
            icon="user-plus"
        >
            Create account
        </x-ui.button>
    @endauth
@endsection

@section('sidebar')
    <x-ui.navlist>
        <x-ui.navlist.item
            :href="route('public.home')"
            label="Home"
            icon="home"
            :active="request()->routeIs('public.home')"
        />
        <x-ui.navlist.item
            :href="route('public.discover')"
            label="Discovery"
            icon="sparkles"
            :active="request()->routeIs('public.discover')"
        />
        <x-ui.navlist.item
            :href="route('public.titles.index')"
            label="Browse Titles"
            icon="film"
            :active="request()->routeIs('public.titles.*')"
        />
        <x-ui.navlist.item
            :href="route('public.people.index')"
            label="Browse People"
            icon="users"
            :active="request()->routeIs('public.people.*')"
        />
        <x-ui.navlist.item
            :href="route('public.search')"
            label="Advanced Search"
            icon="funnel"
            :active="request()->routeIs('public.search')"
        />
        @auth
            <x-ui.navlist.item
                :href="route('account.lists.index')"
                label="Custom Lists"
                icon="queue-list"
                :active="request()->routeIs('account.lists.*') || request()->routeIs('public.lists.*')"
            />
            <x-ui.navlist.item
                :href="route('account.watchlist')"
                label="Your Watchlist"
                icon="bookmark"
                :active="request()->routeIs('account.*')"
            />
        @endauth
    </x-ui.navlist>
@endsection
