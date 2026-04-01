@extends('layouts.app')

@section('navbar')
    <x-ui.navbar.item
        :href="route('admin.dashboard')"
        label="Dashboard"
        icon="chart-bar-square"
        :active="request()->routeIs('admin.dashboard')"
    />
    <x-ui.navbar.item
        :href="route('public.home')"
        label="Public Site"
        icon="arrow-up-right"
    />

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
            :href="route('admin.dashboard')"
            label="Dashboard"
            icon="chart-bar-square"
            :active="request()->routeIs('admin.dashboard')"
        />
        <x-ui.navlist.item
            :href="route('admin.titles.index')"
            label="Titles"
            icon="film"
            :active="request()->routeIs('admin.titles.*')"
        />
        <x-ui.navlist.item
            :href="route('admin.reviews.index')"
            label="Reviews"
            icon="chat-bubble-left-right"
            :active="request()->routeIs('admin.reviews.*')"
        />
        <x-ui.navlist.item
            :href="route('admin.reports.index')"
            label="Reports"
            icon="flag"
            :active="request()->routeIs('admin.reports.*')"
        />
    </x-ui.navlist>
@endsection
