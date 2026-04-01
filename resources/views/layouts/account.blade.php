@php
    $seo = $seo ?? null;
    $sectionTitle = $sectionTitle ?? null;
    $sectionMetaDescription = $sectionMetaDescription ?? null;
    $sectionBreadcrumbs = $sectionBreadcrumbs ?? null;
@endphp

@extends('layouts.app')

@section('navbar')
    <x-ui.navbar.item
        :href="route('public.discover')"
        label="Discover"
        icon="sparkles"
    />
    <x-ui.navbar.item
        :href="route('account.dashboard')"
        label="Dashboard"
        icon="home"
        :active="request()->routeIs('account.dashboard')"
    />
    <x-ui.navbar.item
        :href="route('account.watchlist')"
        label="Watchlist"
        icon="bookmark"
        :active="request()->routeIs('account.watchlist')"
    />
    <x-ui.navbar.item
        :href="route('account.lists.index')"
        label="Lists"
        icon="queue-list"
        :active="request()->routeIs('account.lists.*')"
    />
    <x-ui.navbar.item
        :href="route('public.search')"
        label="Search"
        icon="magnifying-glass"
        :active="request()->routeIs('public.search')"
    />

    @can('access-admin-area')
        <x-ui.navbar.item
            :href="route('admin.dashboard')"
            label="Admin"
            icon="shield-check"
            :active="request()->routeIs('admin.*')"
        />
    @endcan

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
            :href="route('account.dashboard')"
            label="Dashboard"
            icon="home"
            :active="request()->routeIs('account.dashboard')"
        />
        <x-ui.navlist.item
            :href="route('account.watchlist')"
            label="Watchlist"
            icon="bookmark"
            :active="request()->routeIs('account.watchlist')"
        />
        <x-ui.navlist.item
            :href="route('account.lists.index')"
            label="Custom Lists"
            icon="queue-list"
            :active="request()->routeIs('account.lists.*')"
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
        <x-ui.navlist.item
            :href="route('public.search')"
            label="Search"
            icon="magnifying-glass"
            :active="request()->routeIs('public.search')"
        />
    </x-ui.navlist>
@endsection
