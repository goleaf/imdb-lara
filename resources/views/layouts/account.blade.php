@extends('layouts.app')

@section('navbar')
    <x-ui.navbar.item
        :href="route('public.discover')"
        label="Discover"
        icon="sparkles"
    />
    <x-ui.navbar.item
        :href="route('account.watchlist')"
        label="Watchlist"
        icon="bookmark"
        :active="request()->routeIs('account.watchlist')"
    />

    @if (auth()->user()?->isAdmin())
        <x-ui.navbar.item
            :href="route('admin.dashboard')"
            label="Admin"
            icon="shield-check"
            :active="request()->routeIs('admin.*')"
        />
    @endif

    <div class="hidden md:block">
        <x-ui.theme-switcher />
    </div>

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
@endsection

@section('sidebar')
    <x-ui.navlist>
        <x-ui.navlist.item
            :href="route('account.watchlist')"
            label="Watchlist"
            icon="bookmark"
            :active="request()->routeIs('account.watchlist')"
        />
        <x-ui.navlist.item
            :href="route('public.discover')"
            label="Discover"
            icon="sparkles"
        />
        <x-ui.navlist.item
            :href="route('public.titles.index')"
            label="Browse Titles"
            icon="film"
        />
        <x-ui.navlist.item
            :href="route('public.people.index')"
            label="Browse People"
            icon="users"
        />
    </x-ui.navlist>
@endsection
