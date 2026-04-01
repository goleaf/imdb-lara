@php
    $pageShellState = app(\App\Livewire\Pages\Support\PageShellState::class)->all();
    $pageTitle = $pageShellState['pageTitle'] ?? $pageTitle;
    $pageDescription = $pageShellState['pageDescription'] ?? $pageDescription;
    $pageRobots = $pageShellState['pageRobots'] ?? $pageRobots;
    $canonicalUrl = $pageShellState['canonicalUrl'] ?? $canonicalUrl;
    $openGraphTitle = $pageShellState['openGraphTitle'] ?? $openGraphTitle;
    $openGraphDescription = $pageShellState['openGraphDescription'] ?? $openGraphDescription;
    $openGraphType = $pageShellState['openGraphType'] ?? $openGraphType;
    $openGraphImage = $pageShellState['openGraphImage'] ?? $openGraphImage;
    $openGraphImageAlt = $pageShellState['openGraphImageAlt'] ?? $openGraphImageAlt;
    $twitterCard = $pageShellState['twitterCard'] ?? $twitterCard;
    $breadcrumbSchema = $pageShellState['breadcrumbSchema'] ?? $breadcrumbSchema;
    $renderedBreadcrumbs = $pageShellState['breadcrumbs'] ?? $renderedBreadcrumbs;
    $renderedNavbar = $pageShellState['navbar'] ?? $renderedNavbar;
    $renderedSidebar = $pageShellState['sidebar'] ?? $renderedSidebar;
    $renderedNavbarText = strip_tags((string) $renderedNavbar);
    $hasBreadcrumbs = trim((string) $renderedBreadcrumbs) !== '';
    $shouldRenderAdminShortcut = auth()->user()?->can('access-admin-area')
        && ! request()->routeIs('admin.*')
        && ! str_contains($renderedNavbarText, 'Admin');
    $shouldRenderWatchlistShortcut = auth()->check()
        && ! str_contains($renderedNavbarText, 'Watchlist');
    $shouldRenderSignOutShortcut = auth()->check()
        && ! str_contains($renderedNavbarText, 'Sign out');
    $shouldRenderGuestAuthShortcuts = ! auth()->check()
        && ! str_contains($renderedNavbarText, 'Sign in')
        && ! str_contains($renderedNavbarText, 'Create account');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="{{ $pageRobots }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:site_name" content="Screenbase">
        <meta property="og:type" content="{{ $openGraphType }}">
        <meta property="og:title" content="{{ $openGraphTitle }}">
        <meta property="og:description" content="{{ $openGraphDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta name="twitter:card" content="{{ $twitterCard }}">
        <meta name="twitter:title" content="{{ $openGraphTitle }}">
        <meta name="twitter:description" content="{{ $openGraphDescription }}">
        @if ($openGraphImage)
            <meta property="og:image" content="{{ $openGraphImage }}">
            <meta name="twitter:image" content="{{ $openGraphImage }}">
            @if ($openGraphImageAlt)
                <meta property="og:image:alt" content="{{ $openGraphImageAlt }}">
            @endif
        @endif
        @if ($breadcrumbSchema)
            <script type="application/ld+json">{!! $breadcrumbSchema !!}</script>
        @endif

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

                @unless (request()->routeIs('admin.*') || request()->routeIs('public.search'))
                    <div class="hidden min-w-0 max-w-2xl flex-1 px-4 xl:block">
                        <livewire:search.global-search />
                    </div>
                @endunless

                <x-ui.navbar class="ml-auto">
                    {!! $renderedNavbar !!}

                    @if ($shouldRenderAdminShortcut)
                        <x-ui.navbar.item
                            :href="route('admin.dashboard')"
                            label="Admin"
                            icon="shield-check"
                            :active="request()->routeIs('admin.*')"
                        />
                    @endif

                    @if ($shouldRenderWatchlistShortcut)
                        <x-ui.navbar.item
                            :href="route('account.watchlist')"
                            label="Watchlist"
                            icon="bookmark"
                            :active="request()->routeIs('account.*')"
                        />
                    @endif

                    @if ($shouldRenderSignOutShortcut)
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
                    @endif

                    @if ($shouldRenderGuestAuthShortcuts)
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
                    @endif
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

                {!! $renderedSidebar !!}

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
                                {!! $renderedBreadcrumbs !!}
                            </x-ui.breadcrumbs>
                        @endif

                        @isset($slot)
                            {{ $slot }}
                        @else
                            @yield('content')
                        @endisset
                    </div>

                    @unless (request()->routeIs('admin.*') || request()->routeIs('public.search'))
                        <x-ui.footer class="mx-auto w-full max-w-7xl px-4 pb-6 md:px-6 md:pb-8" />
                    @endunless
                </div>
            </x-ui.layout.main>
        </x-ui.layout>

        <x-ui.toast />

        @stack('modals')
        @livewireScriptConfig
    </body>
</html>
