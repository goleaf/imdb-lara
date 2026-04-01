@php
    $screenbaseTitle = trim($__env->yieldContent('title'));
    $pageTitle = $screenbaseTitle !== ''
        ? $screenbaseTitle.' · Screenbase'
        : 'Screenbase';

    $metaDescription = trim($__env->yieldContent('meta_description'));
    $pageDescription = $metaDescription !== ''
        ? $metaDescription
        : 'Screenbase is a Livewire-driven IMDb-style platform for discovery, ratings, reviews, and curation.';

    $hasBreadcrumbs = trim($__env->yieldContent('breadcrumbs')) !== '';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="index,follow">
        <link rel="canonical" href="{{ url()->current() }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('head')
    </head>
    <body class="min-h-full bg-neutral-50 text-neutral-950 antialiased dark:bg-neutral-950 dark:text-neutral-50">
        <x-ui.layout variant="header-sidebar">
            <x-ui.layout.header>
                <x-slot:brand>
                    <x-ui.brand
                        :href="route('public.home')"
                        name="Screenbase"
                        class="font-semibold"
                    />
                </x-slot:brand>

                <x-ui.navbar class="ml-auto">
                    @yield('navbar')
                </x-ui.navbar>
            </x-ui.layout.header>

            <x-ui.sidebar>
                <x-slot:brand>
                    <x-ui.brand
                        :href="route('public.home')"
                        name="Screenbase"
                        class="font-semibold"
                    />
                </x-slot:brand>

                @yield('sidebar')

                <x-ui.sidebar.push />

                <div class="px-3 pb-4 pt-2 text-xs text-neutral-500 dark:text-neutral-400">
                    Built with Laravel 12 and Livewire 4.
                </div>
            </x-ui.sidebar>

            <x-ui.layout.main>
                <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.08),_transparent_38%),linear-gradient(to_bottom,_rgba(255,255,255,0.96),_rgba(248,250,252,1))] dark:bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.18),_transparent_30%),linear-gradient(to_bottom,_rgba(10,10,10,1),_rgba(23,23,23,1))]">
                    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
                        @if ($hasBreadcrumbs)
                            <x-ui.breadcrumbs class="pt-1">
                                @yield('breadcrumbs')
                            </x-ui.breadcrumbs>
                        @endif

                        @yield('content')
                    </div>
                </div>
            </x-ui.layout.main>
        </x-ui.layout>

        <x-ui.toast />

        @stack('modals')
        @livewireScriptConfig
    </body>
</html>
