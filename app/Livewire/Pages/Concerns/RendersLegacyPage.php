<?php

namespace App\Livewire\Pages\Concerns;

use App\Actions\Seo\PageSeoData;
use Illuminate\Contracts\View\View;

trait RendersLegacyPage
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderLegacyPage(string $view, array $data = []): View
    {
        $sections = view($view, $data)->renderSections();
        $layoutView = match (true) {
            str_starts_with($view, 'admin.') => 'layouts.admin',
            str_starts_with($view, 'account.') => 'layouts.account',
            default => 'layouts.public',
        };
        $layoutSections = view($layoutView, $data)->renderSections();
        $pageSeo = $data['seo'] ?? null;
        $defaultDescription = 'Screenbase is a Livewire-driven IMDb-style platform for discovery, ratings, reviews, and curation.';
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
            'navbar' => $layoutSections['navbar'] ?? null,
            'sidebar' => $layoutSections['sidebar'] ?? null,
        ];

        return view('livewire.pages.legacy-page', [
            'content' => $sections['content'] ?? '',
        ])->layout('components.layouts.livewire-page', $layoutData);
    }
}
