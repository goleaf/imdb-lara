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
