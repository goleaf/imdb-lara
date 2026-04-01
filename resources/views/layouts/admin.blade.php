@php
    $seo = $seo ?? null;
    $sectionTitle = $sectionTitle ?? null;
    $sectionMetaDescription = $sectionMetaDescription ?? null;
    $sectionBreadcrumbs = $sectionBreadcrumbs ?? null;
@endphp

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
        @if ($canViewAdminTitles)
            <x-ui.navlist.item
                :href="route('admin.titles.index')"
                label="Titles"
                icon="film"
                :active="request()->routeIs('admin.titles.*')"
            />
        @endif
        @if ($canViewAdminPeople)
            <x-ui.navlist.item
                :href="route('admin.people.index')"
                label="People"
                icon="users"
                :active="request()->routeIs('admin.people.*') || request()->routeIs('admin.professions.*')"
            />
        @endif
        @if ($canViewAdminGenres)
            <x-ui.navlist.item
                :href="route('admin.genres.index')"
                label="Genres"
                icon="tag"
                :active="request()->routeIs('admin.genres.*')"
            />
        @endif
        @if ($canViewAdminMediaAssets)
            <x-ui.navlist.item
                :href="route('admin.media-assets.index')"
                label="Media"
                icon="photo"
                :active="request()->routeIs('admin.media-assets.*')"
            />
        @endif
        @if ($canViewAdminContributions)
            <x-ui.navlist.item
                :href="route('admin.contributions.index')"
                label="Contributions"
                icon="clipboard-document-check"
                :active="request()->routeIs('admin.contributions.*')"
            />
        @endif
        @if ($canViewAdminReviews)
            <x-ui.navlist.item
                :href="route('admin.reviews.index')"
                label="Reviews"
                icon="chat-bubble-left-right"
                :active="request()->routeIs('admin.reviews.*')"
            />
        @endif
        @if ($canViewAdminReports)
            <x-ui.navlist.item
                :href="route('admin.reports.index')"
                label="Reports"
                icon="flag"
                :active="request()->routeIs('admin.reports.*')"
            />
        @endif
    </x-ui.navlist>
@endsection
