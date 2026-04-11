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
        $sections = view($view, $data)->renderSections();
        $navbarView = match (true) {
            str_starts_with($view, 'admin.') => 'layouts.partials.admin-navbar',
            str_starts_with($view, 'account.') => 'layouts.partials.account-navbar',
            default => 'layouts.partials.public-navbar',
        };
        $pageSeo = $data['seo'] ?? null;
        $defaultDescription = 'Screenbase is a Livewire-driven IMDb-style catalog for titles, people, awards, trailers, and discovery.';
        $renderedTitle = trim((string) ($sections['title'] ?? ''));
        $renderedMetaDescription = trim((string) ($sections['meta_description'] ?? ''));
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
            'pageRobots' => $pageSeo instanceof PageSeoData ? $pageSeo->robots : 'index,follow',
            'canonicalUrl' => $pageSeo instanceof PageSeoData ? $pageSeo->canonicalUrl(request()) : url()->current(),
            'openGraphTitle' => $openGraphTitle,
            'openGraphDescription' => $openGraphDescription,
            'openGraphType' => $pageSeo instanceof PageSeoData ? $pageSeo->openGraphType : 'website',
            'openGraphImage' => $pageSeo instanceof PageSeoData ? $pageSeo->openGraphImage : null,
            'openGraphImageAlt' => $pageSeo instanceof PageSeoData ? $pageSeo->openGraphImageAlt : null,
            'twitterCard' => $pageSeo instanceof PageSeoData ? $pageSeo->twitterCard() : 'summary',
            'breadcrumbSchema' => $pageSeo instanceof PageSeoData ? $pageSeo->breadcrumbSchema(request()) : null,
            'breadcrumbs' => $sections['breadcrumbs'] ?? null,
            'navbarView' => $navbarView,
            'sidebar' => null,
            'shellVariant' => match (true) {
                str_starts_with($view, 'admin.') => 'admin',
                str_starts_with($view, 'account.') => 'account',
                default => 'default',
            },
            'showFooter' => ! str_starts_with($view, 'admin.'),
        ];

        request()->attributes->set('livewirePageLayoutData', $layoutData);

        return view('livewire.pages.page-content', [
            'content' => $sections['content'] ?? '',
        ])->layout('layouts.app', [
            'shell' => app(ResolvePageShellViewDataAction::class)->forLivewireLayout($layoutData),
        ]);
    }
}
