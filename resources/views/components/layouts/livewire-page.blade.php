@props([
    'pageTitle' => 'Screenbase',
    'pageDescription' => 'Screenbase is a Livewire-driven IMDb-style platform for discovery, ratings, reviews, and curation.',
    'pageRobots' => 'index,follow',
    'canonicalUrl' => null,
    'openGraphTitle' => 'Screenbase',
    'openGraphDescription' => null,
    'openGraphType' => 'website',
    'openGraphImage' => null,
    'openGraphImageAlt' => null,
    'twitterCard' => 'summary',
    'breadcrumbSchema' => null,
    'breadcrumbs' => null,
    'navbar' => null,
    'sidebar' => null,
])

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
    $breadcrumbs = $pageShellState['breadcrumbs'] ?? $breadcrumbs;
    $navbar = $pageShellState['navbar'] ?? $navbar;
    $sidebar = $pageShellState['sidebar'] ?? $sidebar;

    $canonicalUrl = filled($canonicalUrl) ? $canonicalUrl : url()->current();
    $openGraphDescription = filled($openGraphDescription) ? $openGraphDescription : $pageDescription;
    $renderedBreadcrumbs = $breadcrumbs;
    $renderedNavbar = $navbar;
    $renderedSidebar = $sidebar;
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

@include('layouts.partials.app-shell')
