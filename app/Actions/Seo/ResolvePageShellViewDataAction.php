<?php

namespace App\Actions\Seo;

use Illuminate\Contracts\View\Factory as ViewFactory;

class ResolvePageShellViewDataAction
{
    private const DEFAULT_DESCRIPTION = 'Screenbase is a Livewire-driven IMDb-style platform for discovery, ratings, reviews, and curation.';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forStandardLayout(ViewFactory $viewFactory, array $data): array
    {
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

        $shellVariant = $data['shellVariant']
            ?? ($sectionShellVariant !== '' ? $sectionShellVariant : 'default');
        $defaultRobots = in_array($shellVariant, ['auth', 'account', 'admin'], true)
            || request()->routeIs('login')
            || request()->routeIs('register')
            || request()->routeIs('account.*')
            || request()->routeIs('admin.*')
            ? 'noindex,nofollow'
            : 'index,follow';
        $pageTitle = $data['pageTitleOverride']
            ?? ($sectionPageTitleOverride !== '' ? $sectionPageTitleOverride : null)
            ?? ($pageSeo instanceof PageSeoData ? $pageSeo->documentTitle(request()) : null)
            ?? ($renderedTitle !== '' ? $renderedTitle.' · Screenbase' : 'Screenbase');

        $pageDescription = $data['pageDescriptionOverride']
            ?? ($sectionPageDescriptionOverride !== '' ? $sectionPageDescriptionOverride : null)
            ?? ($pageSeo instanceof PageSeoData ? $pageSeo->pageDescription(self::DEFAULT_DESCRIPTION) : null)
            ?? ($renderedMetaDescription !== '' ? $renderedMetaDescription : self::DEFAULT_DESCRIPTION);

        $resolvedData = [
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'pageRobots' => $data['pageRobotsOverride']
                ?? ($sectionPageRobots !== '' ? $sectionPageRobots : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->robots : null)
                ?? $defaultRobots,
            'canonicalUrl' => $data['canonicalUrlOverride']
                ?? ($sectionCanonicalUrl !== '' ? $sectionCanonicalUrl : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->canonicalUrl(request()) : null)
                ?? url()->current(),
            'openGraphTitle' => $data['openGraphTitleOverride']
                ?? ($sectionOpenGraphTitle !== '' ? $sectionOpenGraphTitle : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphTitle() : null)
                ?? ($renderedTitle !== '' ? $renderedTitle : 'Screenbase'),
            'openGraphDescription' => $data['openGraphDescriptionOverride']
                ?? ($sectionOpenGraphDescription !== '' ? $sectionOpenGraphDescription : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphDescription(self::DEFAULT_DESCRIPTION) : null)
                ?? $pageDescription,
            'openGraphType' => $data['openGraphTypeOverride']
                ?? ($sectionOpenGraphType !== '' ? $sectionOpenGraphType : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphType : null)
                ?? 'website',
            'openGraphImage' => $data['openGraphImageOverride']
                ?? ($sectionOpenGraphImage !== '' ? $sectionOpenGraphImage : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphImage : null),
            'openGraphImageAlt' => $data['openGraphImageAltOverride']
                ?? ($sectionOpenGraphImageAlt !== '' ? $sectionOpenGraphImageAlt : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->openGraphImageAlt : null),
            'twitterCard' => $data['twitterCardOverride']
                ?? ($sectionTwitterCard !== '' ? $sectionTwitterCard : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->twitterCard() : null)
                ?? 'summary',
            'breadcrumbSchema' => $data['breadcrumbSchemaOverride']
                ?? ($sectionBreadcrumbSchema !== '' ? $sectionBreadcrumbSchema : null)
                ?? ($pageSeo instanceof PageSeoData ? $pageSeo->breadcrumbSchema(request()) : null),
            'renderedBreadcrumbs' => $renderedBreadcrumbs,
            'renderedNavbar' => $renderedNavbar,
            'renderedSidebar' => $renderedSidebar,
            'shellVariant' => $shellVariant,
            'showFooter' => $data['showFooter']
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
        $renderedNavbarText = strip_tags((string) ($data['renderedNavbar'] ?? ''));

        return [
            ...$data,
            'hasBreadcrumbs' => $this->trimString($data['renderedBreadcrumbs'] ?? null) !== '',
            'isAuthShell' => ($data['shellVariant'] ?? 'default') === 'auth',
            'shouldRenderAdminShortcut' => auth()->user()?->can('access-admin-area')
                && ! request()->routeIs('admin.*')
                && ! str_contains($renderedNavbarText, 'Admin'),
            'shouldRenderWatchlistShortcut' => auth()->check()
                && ! str_contains($renderedNavbarText, 'Watchlist'),
            'shouldRenderSignOutShortcut' => auth()->check()
                && ! str_contains($renderedNavbarText, 'Sign out'),
            'shouldRenderGuestAuthShortcuts' => ! auth()->check()
                && ! str_contains($renderedNavbarText, 'Sign in')
                && ! str_contains($renderedNavbarText, 'Create account'),
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
