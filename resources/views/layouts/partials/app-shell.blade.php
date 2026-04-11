<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full scroll-smooth [color-scheme:dark]">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $shell['pageTitle'] }}</title>
        <meta name="description" content="{{ $shell['pageDescription'] }}">
        <meta name="robots" content="{{ $shell['pageRobots'] }}">
        <link rel="canonical" href="{{ $shell['canonicalUrl'] }}">
        <meta property="og:site_name" content="Screenbase">
        <meta property="og:type" content="{{ $shell['openGraphType'] }}">
        <meta property="og:title" content="{{ $shell['openGraphTitle'] }}">
        <meta property="og:description" content="{{ $shell['openGraphDescription'] }}">
        <meta property="og:url" content="{{ $shell['canonicalUrl'] }}">
        <meta name="twitter:card" content="{{ $shell['twitterCard'] }}">
        <meta name="twitter:title" content="{{ $shell['openGraphTitle'] }}">
        <meta name="twitter:description" content="{{ $shell['openGraphDescription'] }}">
        @if ($shell['openGraphImage'])
            <meta property="og:image" content="{{ $shell['openGraphImage'] }}">
            <meta name="twitter:image" content="{{ $shell['openGraphImage'] }}">
            @if ($shell['openGraphImageAlt'])
                <meta property="og:image:alt" content="{{ $shell['openGraphImageAlt'] }}">
            @endif
        @endif
        @if ($shell['breadcrumbSchema'])
            <script type="application/ld+json">{!! $shell['breadcrumbSchema'] !!}</script>
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('head')
    </head>
    <body @class([
        'min-h-full antialiased',
        'bg-[#080707] text-stone-50' => $shell['isAuthShell'],
        'bg-neutral-950 text-neutral-50' => ! $shell['isAuthShell'],
    ])>
        @if ($shell['isAuthShell'])
            <div class="sb-auth-shell relative min-h-screen overflow-hidden">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute inset-x-0 top-0 h-64 bg-[radial-gradient(circle_at_top,_rgba(214,181,116,0.22),_transparent_58%)] opacity-90"></div>
                    <div class="absolute inset-y-0 right-0 w-[28rem] bg-[radial-gradient(circle_at_center,_rgba(84,66,46,0.22),_transparent_62%)]"></div>
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.025),transparent_38%,rgba(214,181,116,0.02)_72%,transparent)]"></div>
                </div>

                <div class="relative flex min-h-screen flex-col">
                    <header class="sticky top-0 z-40 border-b border-white/8 bg-black/30 backdrop-blur-xl">
                        <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                            <a href="{{ route('public.home') }}" class="group inline-flex items-center gap-3 text-decoration-none">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#d6b574]/25 bg-white/4 text-[0.72rem] font-semibold uppercase tracking-[0.3em] text-[#d6b574]">
                                    SB
                                </span>
                                <span class="min-w-0">
                                    <span class="sb-auth-kicker block">Premium Entertainment Database</span>
                                    <span class="block text-base font-semibold text-[#f7f1e8] transition-opacity group-hover:opacity-80">
                                        Screenbase
                                    </span>
                                </span>
                            </a>

                            <a
                                href="{{ route('public.discover') }}"
                                class="sb-auth-header-link inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/4 px-4 py-2 text-sm font-medium"
                            >
                                <x-ui.icon name="chevron-left" variant="mini" class="size-4" />
                                <span>Browse catalog</span>
                            </a>
                        </div>
                    </header>

                    <main class="relative flex-1">
                        <div class="mx-auto flex min-h-[calc(100vh-4.75rem)] w-full max-w-6xl items-center justify-center px-4 py-10 sm:px-6 lg:py-16">
                            @isset($slot)
                                {{ $slot }}
                            @elseif (isset($content))
                                {!! $content !!}
                            @else
                                @yield('content')
                            @endisset
                        </div>
                    </main>

                    @if ($shell['showFooter'])
                        <x-ui.footer />
                    @endif
                </div>
            </div>
        @else
            <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(214,181,116,0.16),_transparent_24%),radial-gradient(circle_at_bottom_left,_rgba(94,73,47,0.2),_transparent_26%),linear-gradient(to_bottom,_rgba(10,10,10,1),_rgba(18,16,14,1))]">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top,_rgba(214,181,116,0.18),_transparent_58%)]"></div>
                    <div class="absolute inset-y-0 right-0 w-[32rem] bg-[radial-gradient(circle_at_center,_rgba(84,66,46,0.18),_transparent_62%)]"></div>
                </div>

                <div class="relative flex min-h-screen flex-col">
                    <header class="sticky top-0 z-40 border-b border-white/8 bg-[linear-gradient(180deg,rgba(8,8,8,0.98),rgba(17,15,13,0.94))] shadow-[0_22px_60px_rgba(0,0,0,0.32)] backdrop-blur-xl">
                        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-4 md:px-6">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <a href="{{ route('public.home') }}" class="group inline-flex items-center gap-3 text-decoration-none xl:pr-4">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#d6b574]/25 bg-white/4 text-[0.72rem] font-semibold uppercase tracking-[0.3em] text-[#d6b574]">
                                        SB
                                    </span>
                                    <span class="min-w-0">
                                        <span class="sb-shell-kicker block">Entertainment Database</span>
                                        <span class="sb-shell-brand-name block text-[#f7f1e8] transition-opacity group-hover:opacity-80">
                                            Screenbase
                                        </span>
                                    </span>
                                </a>

                                @unless (request()->routeIs('admin.*'))
                                    <div class="hidden min-w-0 max-w-[54rem] flex-1 xl:block">
                                        <div class="sb-shell-search-stage">
                                            <div class="sb-shell-search-label">Search The Global Catalog</div>
                                            <livewire:search.global-search />
                                        </div>
                                    </div>
                                @endunless
                            </div>

                            @php($hasShellUtilities = $shell['shouldRenderAdminShortcut'] || $shell['shouldRenderWatchlistShortcut'] || $shell['shouldRenderSignOutShortcut'] || $shell['shouldRenderGuestAuthShortcuts'])

                            <div class="sb-shell-topnav" aria-label="Global navigation">
                                @if (filled(trim((string) $shell['renderedNavbar'])))
                                    {!! $shell['renderedNavbar'] !!}
                                @elseif (request()->routeIs('public.*'))
                                    @include('layouts.partials.public-navbar')
                                @endif

                                @if ($hasShellUtilities)
                                    <div class="sb-shell-topnav-utility">
                                        @if ($shell['shouldRenderAdminShortcut'])
                                            <x-ui.button.light-outline
                                                :href="route('admin.dashboard')"
                                                size="sm"
                                                icon="shield-check"
                                            >
                                                Admin
                                            </x-ui.button.light-outline>
                                        @endif

                                        @if ($shell['shouldRenderWatchlistShortcut'])
                                            <x-ui.button.light-outline
                                                :href="route('account.watchlist')"
                                                size="sm"
                                                icon="bookmark"
                                            >
                                                Watchlist
                                            </x-ui.button.light-outline>
                                        @endif

                                        @if ($shell['shouldRenderSignOutShortcut'])
                                            <form method="POST" action="{{ route('logout') }}" class="sb-shell-topnav-utility-form">
                                                @csrf
                                                <x-ui.button
                                                    type="submit"
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="arrow-right-start-on-rectangle"
                                                    class="sb-shell-topnav-utility-button"
                                                >
                                                    Sign out
                                                </x-ui.button>
                                            </form>
                                        @endif

                                        @if ($shell['shouldRenderGuestAuthShortcuts'])
                                            <x-ui.button.light-outline
                                                :href="route('login')"
                                                size="sm"
                                                icon="arrow-right-end-on-rectangle"
                                            >
                                                Sign in
                                            </x-ui.button.light-outline>
                                            <x-ui.button
                                                as="a"
                                                :href="route('register')"
                                                size="sm"
                                                icon="user-plus"
                                                class="sb-shell-topnav-utility-button"
                                            >
                                                Create account
                                            </x-ui.button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </header>

                    <main class="relative flex-1">
                        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 md:px-6">
                            @if ($shell['hasBreadcrumbs'])
                                <x-ui.breadcrumbs class="pt-1">
                                    {!! $shell['renderedBreadcrumbs'] !!}
                                </x-ui.breadcrumbs>
                            @endif

                            @isset($slot)
                                {{ $slot }}
                            @elseif (isset($content))
                                {!! $content !!}
                            @else
                                @yield('content')
                            @endisset
                        </div>
                    </main>

                    @if ($shell['showFooter'])
                        <x-ui.footer />
                    @endif
                </div>
            </div>
        @endif

        <x-ui.toast />

        @stack('modals')
        @livewireScriptConfig
    </body>
</html>
