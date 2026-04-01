@php
    /** @var \App\Actions\Seo\PageSeoData|null $pageSeo */
    $pageSeo = $seo ?? null;
    $defaultDescription = 'Screenbase is a Livewire-driven IMDb-style platform for discovery, ratings, reviews, and curation.';
    $sectionPageTitleOverride = trim((string) $__env->yieldContent('page_title_override'));
    $sectionPageDescriptionOverride = trim((string) $__env->yieldContent('page_description_override'));
    $sectionPageRobots = trim((string) $__env->yieldContent('page_robots'));
    $sectionCanonicalUrl = trim((string) $__env->yieldContent('canonical_url'));
    $sectionOpenGraphTitle = trim((string) $__env->yieldContent('open_graph_title'));
    $sectionOpenGraphDescription = trim((string) $__env->yieldContent('open_graph_description'));
    $sectionOpenGraphType = trim((string) $__env->yieldContent('open_graph_type'));
    $sectionOpenGraphImage = trim((string) $__env->yieldContent('open_graph_image'));
    $sectionOpenGraphImageAlt = trim((string) $__env->yieldContent('open_graph_image_alt'));
    $sectionTwitterCard = trim((string) $__env->yieldContent('twitter_card'));
    $sectionBreadcrumbSchema = trim((string) $__env->yieldContent('breadcrumb_schema'));
    $renderedTitle = trim((string) ($sectionTitle ?? $__env->yieldContent('title')));
    $pageTitle = $pageTitleOverride
        ?? ($sectionPageTitleOverride !== '' ? $sectionPageTitleOverride : null)
        ?? $pageSeo?->documentTitle(request())
        ?? ($renderedTitle !== ''
            ? $renderedTitle.' · Screenbase'
            : 'Screenbase');

    $renderedMetaDescription = trim((string) ($sectionMetaDescription ?? $__env->yieldContent('meta_description')));
    $pageDescription = $pageDescriptionOverride
        ?? ($sectionPageDescriptionOverride !== '' ? $sectionPageDescriptionOverride : null)
        ?? $pageSeo?->pageDescription($defaultDescription)
        ?? ($renderedMetaDescription !== ''
            ? $renderedMetaDescription
            : $defaultDescription);

    $slotBreadcrumbs = isset($breadcrumbs) && ! is_array($breadcrumbs) ? $breadcrumbs : null;
    $slotNavbar = isset($navbar) && ! is_array($navbar) ? $navbar : null;
    $slotSidebar = isset($sidebar) && ! is_array($sidebar) ? $sidebar : null;
    $renderedBreadcrumbs = $slotBreadcrumbs ?? ($sectionBreadcrumbs ?? $__env->yieldContent('breadcrumbs'));
    $renderedNavbar = $slotNavbar ?? ($sectionNavbar ?? $__env->yieldContent('navbar'));
    $renderedSidebar = $slotSidebar ?? ($sectionSidebar ?? $__env->yieldContent('sidebar'));
    $renderedNavbarText = strip_tags((string) $renderedNavbar);
    $hasBreadcrumbs = trim((string) $renderedBreadcrumbs) !== '';
    $pageRobots = $pageRobotsOverride
        ?? ($sectionPageRobots !== '' ? $sectionPageRobots : null)
        ?? $pageSeo?->robots
        ?? 'index,follow';
    $canonicalUrl = $canonicalUrlOverride
        ?? ($sectionCanonicalUrl !== '' ? $sectionCanonicalUrl : null)
        ?? $pageSeo?->canonicalUrl(request())
        ?? url()->current();
    $openGraphTitle = $openGraphTitleOverride
        ?? ($sectionOpenGraphTitle !== '' ? $sectionOpenGraphTitle : null)
        ?? $pageSeo?->openGraphTitle()
        ?? ($renderedTitle !== '' ? $renderedTitle : 'Screenbase');
    $openGraphDescription = $openGraphDescriptionOverride
        ?? ($sectionOpenGraphDescription !== '' ? $sectionOpenGraphDescription : null)
        ?? $pageSeo?->openGraphDescription($defaultDescription)
        ?? $pageDescription;
    $openGraphType = $openGraphTypeOverride
        ?? ($sectionOpenGraphType !== '' ? $sectionOpenGraphType : null)
        ?? $pageSeo?->openGraphType
        ?? 'website';
    $openGraphImage = $openGraphImageOverride
        ?? ($sectionOpenGraphImage !== '' ? $sectionOpenGraphImage : null)
        ?? $pageSeo?->openGraphImage;
    $openGraphImageAlt = $openGraphImageAltOverride
        ?? ($sectionOpenGraphImageAlt !== '' ? $sectionOpenGraphImageAlt : null)
        ?? $pageSeo?->openGraphImageAlt;
    $twitterCard = $twitterCardOverride
        ?? ($sectionTwitterCard !== '' ? $sectionTwitterCard : null)
        ?? $pageSeo?->twitterCard()
        ?? 'summary';
    $breadcrumbSchema = $breadcrumbSchemaOverride
        ?? ($sectionBreadcrumbSchema !== '' ? $sectionBreadcrumbSchema : null)
        ?? $pageSeo?->breadcrumbSchema(request());
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
