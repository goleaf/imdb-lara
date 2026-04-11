<?php

namespace App\Actions\Seo;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Route;

class ResolvePageShellViewDataAction
{
    private const DEFAULT_DESCRIPTION = 'Screenbase is a Livewire-driven IMDb-style catalog for titles, people, awards, trailers, and discovery.';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forStandardLayout(ViewFactory $viewFactory, array $data): array
    {
        $livewireLayoutData = request()->attributes->get('livewirePageLayoutData');
        $livewireLayoutData = is_array($livewireLayoutData) ? $livewireLayoutData : [];
        $pageSeo = $data['seo'] ?? null;
        $sectionPageTitleOverride = $this->trimString($viewFactory->yieldContent('page_title_override'));
        $sectionPageDescriptionOverride = $this->trimString($viewFactory->yieldContent('page_description_override'));
        $sectionPageRobots = $this->trimString($viewFactory->yieldContent('page_robots'));
        $sectionCanonicalUrl = $this->trimString($viewFactory->yieldContent('canonical_url'));
        $sectionOpenGraphTitle = $this->trimString($viewFactory->yieldContent('open_graph_title'));
        $sectionOpenGraphDescription = $this->trimString($viewFactory->yieldContent('open_graph_description'));
        $sectionOpenGraphType = $this->trimString($viewFactory->yieldContent('open_graph_type'));
        $sectionOpenGraphImage = $this->trimString($viewFactory->yieldContent('open_graph_image'));
        $sectionOpenGraphImageAlt = $this->trimString($viewFactory->yieldContent('open_graph_image_alt'));
        $sectionTwitterCard = $this->trimString($viewFactory->yieldContent('twitter_card'));
        $sectionBreadcrumbSchema = $this->trimString($viewFactory->yieldContent('breadcrumb_schema'));
        $sectionShellVariant = $this->trimString($viewFactory->yieldContent('shell_variant'));
        $sectionShowFooter = $this->trimString($viewFactory->yieldContent('show_footer'));
        $renderedTitle = $this->trimString($data['sectionTitle'] ?? $viewFactory->yieldContent('title'));
        $renderedMetaDescription = $this->trimString($data['sectionMetaDescription'] ?? $viewFactory->yieldContent('meta_description'));
        $slotBreadcrumbs = $this->slotContent($data, 'breadcrumbs');
        $slotNavbar = $this->slotContent($data, 'navbar');
        $slotSidebar = $this->slotContent($data, 'sidebar');
        $renderedBreadcrumbs = $slotBreadcrumbs ?? ($data['sectionBreadcrumbs'] ?? $viewFactory->yieldContent('breadcrumbs'));
        $renderedNavbar = $slotNavbar ?? ($data['sectionNavbar'] ?? $viewFactory->yieldContent('navbar'));
        $renderedSidebar = $slotSidebar ?? ($data['sectionSidebar'] ?? $viewFactory->yieldContent('sidebar'));

        $shellVariant = $livewireLayoutData['shellVariant']
            ?? $data['shellVariant']
            ?? ($sectionShellVariant !== '' ? $sectionShellVariant : 'default');
        $defaultRobots = in_array($shellVariant, ['auth', 'account', 'admin'], true)
            || request()->routeIs('login')
            || request()->routeIs('register')
            || request()->routeIs('account.*')
            || request()->routeIs('admin.*')
            ? 'noindex,nofollow'
            : 'index,follow';
        $pageTitle = $livewireLayoutData['pageTitle']
            ?? $data['pageTitleOverride']
            ?? ($sectionPageTitleOverride !== '' ? $sectionPageTitleOverride : null)
            ?? ($pageSeo instanceof PageSeoData ? $pageSeo->documentTitle(request()) : null)
            ?? ($renderedTitle !== '' ? $renderedTitle.' · Screenbase' : 'Screenbase');

        $pageDescription = $livewireLayoutData['pageDescription']
            ?? $data['pageDescriptionOverride']
            ?? ($sectionPageDescriptionOverride !== '' ? $sectionPageDescriptionOverride : null)
            ?? ($pageSeo instanceof PageSeoData ? $pageSeo->pageDescription(self::DEFAULT_DESCRIPTION) : null)
            ?? ($renderedMetaDescription !== '' ? $renderedMetaDescription : self::DEFAULT_DESCRIPTION);

        $resolvedData = [
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'pageRobots' => $livewireLayoutData['pageRobots']
                ?? $data['pageRobotsOverride']
                ?? ($sectionPageRobots !== '' ? $sectionPageRobots : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->robots : null)
                ?? $defaultRobots,
            'canonicalUrl' => $livewireLayoutData['canonicalUrl']
                ?? $data['canonicalUrlOverride']
                ?? ($sectionCanonicalUrl !== '' ? $sectionCanonicalUrl : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->canonicalUrl(request()) : null)
                ?? url()->current(),
            'openGraphTitle' => $livewireLayoutData['openGraphTitle']
                ?? $data['openGraphTitleOverride']
                ?? ($sectionOpenGraphTitle !== '' ? $sectionOpenGraphTitle : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphTitle() : null)
                ?? ($renderedTitle !== '' ? $renderedTitle : 'Screenbase'),
            'openGraphDescription' => $livewireLayoutData['openGraphDescription']
                ?? $data['openGraphDescriptionOverride']
                ?? ($sectionOpenGraphDescription !== '' ? $sectionOpenGraphDescription : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphDescription(self::DEFAULT_DESCRIPTION) : null)
                ?? $pageDescription,
            'openGraphType' => $livewireLayoutData['openGraphType']
                ?? $data['openGraphTypeOverride']
                ?? ($sectionOpenGraphType !== '' ? $sectionOpenGraphType : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphType : null)
                ?? 'website',
            'openGraphImage' => $livewireLayoutData['openGraphImage']
                ?? $data['openGraphImageOverride']
                ?? ($sectionOpenGraphImage !== '' ? $sectionOpenGraphImage : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphImage : null),
            'openGraphImageAlt' => $livewireLayoutData['openGraphImageAlt']
                ?? $data['openGraphImageAltOverride']
                ?? ($sectionOpenGraphImageAlt !== '' ? $sectionOpenGraphImageAlt : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphImageAlt : null),
            'twitterCard' => $livewireLayoutData['twitterCard']
                ?? $data['twitterCardOverride']
                ?? ($sectionTwitterCard !== '' ? $sectionTwitterCard : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->twitterCard() : null)
                ?? 'summary',
            'breadcrumbSchema' => $livewireLayoutData['breadcrumbSchema']
                ?? $data['breadcrumbSchemaOverride']
                ?? ($sectionBreadcrumbSchema !== '' ? $sectionBreadcrumbSchema : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->breadcrumbSchema(request()) : null),
            'renderedBreadcrumbs' => $this->trimString($renderedBreadcrumbs) !== ''
                ? $renderedBreadcrumbs
                : ($livewireLayoutData['breadcrumbs'] ?? null),
            'renderedNavbar' => $this->trimString($renderedNavbar) !== ''
                ? $renderedNavbar
                : ($livewireLayoutData['navbar'] ?? null),
            'renderedSidebar' => $this->trimString($renderedSidebar) !== ''
                ? $renderedSidebar
                : ($livewireLayoutData['sidebar'] ?? null),
            'shellVariant' => $shellVariant,
            'showFooter' => $livewireLayoutData['showFooter']
                ?? $data['showFooter']
                ?? $this->parseBooleanValue($sectionShowFooter, true),
        ];

        return $this->finalize($resolvedData);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forLivewireLayout(array $data): array
    {
        $resolvedData = [
            'pageTitle' => $data['pageTitle'] ?? 'Screenbase',
            'pageDescription' => $data['pageDescription'] ?? self::DEFAULT_DESCRIPTION,
            'pageRobots' => $data['pageRobots'] ?? 'index,follow',
            'canonicalUrl' => filled($data['canonicalUrl'] ?? null) ? $data['canonicalUrl'] : url()->current(),
            'openGraphTitle' => $data['openGraphTitle'] ?? ($data['pageTitle'] ?? 'Screenbase'),
            'openGraphDescription' => filled($data['openGraphDescription'] ?? null)
                ? $data['openGraphDescription']
                : ($data['pageDescription'] ?? self::DEFAULT_DESCRIPTION),
            'openGraphType' => $data['openGraphType'] ?? 'website',
            'openGraphImage' => $data['openGraphImage'] ?? null,
            'openGraphImageAlt' => $data['openGraphImageAlt'] ?? null,
            'twitterCard' => $data['twitterCard'] ?? 'summary',
            'breadcrumbSchema' => $data['breadcrumbSchema'] ?? null,
            'renderedBreadcrumbs' => $data['breadcrumbs'] ?? null,
            'renderedNavbar' => $data['navbar'] ?? null,
            'renderedSidebar' => $data['sidebar'] ?? null,
            'shellVariant' => $data['shellVariant'] ?? 'default',
            'showFooter' => $data['showFooter'] ?? true,
        ];

        return $this->finalize($resolvedData);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function finalize(array $data): array
    {
        $catalogOnly = (bool) config('screenbase.catalog_only', false);
        $authShortcutsEnabled = (bool) config('screenbase.shell.auth_shortcuts_enabled', true);
        $adminShortcutsEnabled = (bool) config('screenbase.shell.admin_shortcuts_enabled', true);
        $watchlistShortcutsEnabled = (bool) config('screenbase.shell.watchlist_shortcuts_enabled', true);
        $shellVariant = $data['shellVariant'] ?? 'default';
        $isAuthShell = $shellVariant === 'auth';
        $isPortalShell = in_array($shellVariant, ['account', 'admin'], true);
        $hasBreadcrumbs = $this->trimString($data['renderedBreadcrumbs'] ?? null) !== '';
        $hasRenderedNavbar = $this->trimString($data['renderedNavbar'] ?? null) !== '';
        $renderedNavbarText = strip_tags((string) ($data['renderedNavbar'] ?? ''));
        $shouldRenderAdminShortcut = ! $catalogOnly
            && $adminShortcutsEnabled
            && auth()->user()?->canAccessAdminPanel()
            && Route::has('admin.dashboard')
            && ! request()->routeIs('admin.*')
            && ! str_contains($renderedNavbarText, 'Admin');
        $shouldRenderWatchlistShortcut = ! $catalogOnly
            && $watchlistShortcutsEnabled
            && auth()->check()
            && Route::has('account.watchlist')
            && ! str_contains($renderedNavbarText, 'Watchlist');
        $shouldRenderSignOutShortcut = ! $catalogOnly
            && $authShortcutsEnabled
            && auth()->check()
            && Route::has('logout')
            && ! str_contains($renderedNavbarText, 'Sign out');
        $shouldRenderGuestAuthShortcuts = ! $catalogOnly
            && $authShortcutsEnabled
            && ! auth()->check()
            && Route::has('login')
            && Route::has('register')
            && ! str_contains($renderedNavbarText, 'Sign in')
            && ! str_contains($renderedNavbarText, 'Create account');

        return [
            ...$data,
            'isCatalogOnlyApplication' => $catalogOnly,
            'hasBreadcrumbs' => $hasBreadcrumbs,
            'hasRenderedNavbar' => $hasRenderedNavbar,
            'isAuthShell' => $isAuthShell,
            'isPortalShell' => $isPortalShell,
            'shouldRenderAdminShortcut' => $shouldRenderAdminShortcut,
            'shouldRenderWatchlistShortcut' => $shouldRenderWatchlistShortcut,
            'shouldRenderSignOutShortcut' => $shouldRenderSignOutShortcut,
            'shouldRenderGuestAuthShortcuts' => $shouldRenderGuestAuthShortcuts,
            'hasShellUtilities' => $shouldRenderAdminShortcut
                || $shouldRenderWatchlistShortcut
                || $shouldRenderSignOutShortcut
                || $shouldRenderGuestAuthShortcuts,
        ];
    }

    private function parseBooleanValue(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalizedValue = $this->trimString($value);

        if ($normalizedValue === '') {
            return $default;
        }

        return ! in_array(strtolower($normalizedValue), ['0', 'false', 'off', 'no'], true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function slotContent(array $data, string $key): mixed
    {
        if (! array_key_exists($key, $data) || is_array($data[$key])) {
            return null;
        }

        return $data[$key];
    }

    private function trimString(mixed $value): string
    {
        return trim((string) $value);
    }
}
