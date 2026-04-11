<?php

namespace App\Livewire\Pages\Concerns;

use App\Actions\Seo\PageSeoData;
use App\Actions\Seo\ResolvePageShellViewDataAction;
use Illuminate\Contracts\View\View;

trait RendersPageView
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderPageView(string $view, array $data = []): View
    {
        $isAdminView = str_starts_with($view, 'admin.');
        $isAccountView = str_starts_with($view, 'account.');

        if ($isAdminView && ! array_key_exists('catalogOnly', $data)) {
            $data['catalogOnly'] = $this->isCatalogOnlyApplication();
        }

        $sections = view($view, $data)->renderSections();
        $navbarView = match (true) {
            $isAdminView => 'layouts.partials.admin-navbar',
            $isAccountView => 'layouts.partials.account-navbar',
            default => null,
        };
        $sidebarView = match (true) {
            $isAdminView => 'layouts.partials.admin-sidebar',
            $isAccountView => 'layouts.partials.account-sidebar',
            default => null,
        };
        $pageSeo = $data['seo'] ?? null;
        $defaultDescription = 'Screenbase is a Livewire-driven IMDb-style catalog for titles, people, awards, trailers, and discovery.';
        $renderedTitle = trim((string) ($sections['title'] ?? ''));
        $renderedMetaDescription = trim((string) ($sections['meta_description'] ?? ''));
        $defaultRobots = $isAdminView || $isAccountView ? 'noindex,nofollow' : 'index,follow';
        $pageTitle = $pageSeo instanceof PageSeoData
            ? $pageSeo->documentTitle(request())
            : ($renderedTitle !== ''
                ? $renderedTitle.' · Screenbase'
                : 'Screenbase');
        $pageDescription = $pageSeo instanceof PageSeoData
            ? $pageSeo->pageDescription($defaultDescription)
            : ($renderedMetaDescription !== ''
                ? $renderedMetaDescription
                : $defaultDescription);
        $openGraphTitle = $pageSeo instanceof PageSeoData
            ? $pageSeo->openGraphTitle()
            : ($renderedTitle !== ''
                ? $renderedTitle
                : 'Screenbase');
        $openGraphDescription = $pageSeo instanceof PageSeoData
            ? $pageSeo->openGraphDescription($defaultDescription)
            : $pageDescription;

        $layoutData = [
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'pageRobots' => $pageSeo instanceof PageSeoData ? $pageSeo->robots : $defaultRobots,
            'canonicalUrl' => $pageSeo instanceof PageSeoData ? $pageSeo->canonicalUrl(request()) : url()->current(),
            'openGraphTitle' => $openGraphTitle,
            'openGraphDescription' => $openGraphDescription,
            'openGraphType' => $pageSeo instanceof PageSeoData ? $pageSeo->openGraphType : 'website',
            'openGraphImage' => $pageSeo instanceof PageSeoData ? $pageSeo->openGraphImage : null,
            'openGraphImageAlt' => $pageSeo instanceof PageSeoData ? $pageSeo->openGraphImageAlt : null,
            'twitterCard' => $pageSeo instanceof PageSeoData ? $pageSeo->twitterCard() : 'summary',
            'breadcrumbSchema' => $pageSeo instanceof PageSeoData ? $pageSeo->breadcrumbSchema(request()) : null,
            'breadcrumbs' => $sections['breadcrumbs'] ?? null,
            'navbar' => $navbarView ? view($navbarView)->render() : null,
            'sidebar' => $sidebarView ? view($sidebarView)->render() : null,
            'shellVariant' => match (true) {
                $isAdminView => 'admin',
                $isAccountView => 'account',
                default => 'default',
            },
            'showFooter' => ! $isAdminView,
        ];

        request()->attributes->set('livewirePageLayoutData', $layoutData);

        return view('livewire.pages.page-content', [
            'content' => $sections['content'] ?? '',
        ])->layout('layouts.app', [
            'shell' => app(ResolvePageShellViewDataAction::class)->forLivewireLayout($layoutData),
        ]);
    }

    protected function isCatalogOnlyApplication(): bool
    {
        return (bool) config('screenbase.catalog_only', false);
    }
}
