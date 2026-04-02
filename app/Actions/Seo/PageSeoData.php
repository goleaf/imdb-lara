<?php

namespace App\Actions\Seo;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PageSeoData
{
    /**
     * @param  list<array{label: string, href?: string|null}>  $breadcrumbs
     * @param  list<string>  $allowedQueryParameters
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public string $robots = 'index,follow',
        public string $openGraphType = 'website',
        public ?string $openGraphTitle = null,
        public ?string $openGraphDescription = null,
        public ?string $openGraphImage = null,
        public ?string $openGraphImageAlt = null,
        public array $breadcrumbs = [],
        public ?string $paginationPageName = 'page',
        public bool $preserveQueryString = false,
        public array $allowedQueryParameters = [],
        public string $siteName = 'Screenbase',
    ) {}

    public function documentTitle(Request $request): string
    {
        $title = trim($this->title);
        $pageNumber = $this->currentPage($request);

        if ($pageNumber > 1) {
            $title .= ' - Page '.$pageNumber;
        }

        if (
            $title === ''
            || Str::contains(Str::lower($title), Str::lower($this->siteName))
        ) {
            return $title !== '' ? $title : $this->siteName;
        }

        return $title.' · '.$this->siteName;
    }

    public function pageDescription(string $defaultDescription): string
    {
        $description = Str::of(strip_tags($this->description ?: $defaultDescription))
            ->squish()
            ->limit(200)
            ->toString();

        return $description !== '' ? $description : $defaultDescription;
    }

    public function openGraphTitle(): string
    {
        return $this->openGraphTitle ?: $this->title;
    }

    public function openGraphDescription(string $defaultDescription): string
    {
        return $this->openGraphDescription ?: $this->pageDescription($defaultDescription);
    }

    public function canonicalUrl(Request $request): string
    {
        return $this->urlForPage($request, $this->currentPage($request));
    }

    public function breadcrumbSchema(Request $request): ?string
    {
        if ($this->breadcrumbs === []) {
            return null;
        }

        $currentUrl = $this->canonicalUrl($request);
        $items = collect($this->breadcrumbs)
            ->values()
            ->map(function (array $breadcrumb, int $index) use ($currentUrl): ?array {
                $label = Str::of(strip_tags((string) ($breadcrumb['label'] ?? '')))
                    ->squish()
                    ->toString();

                if ($label === '') {
                    return null;
                }

                $itemUrl = $breadcrumb['href'] ?? null;

                if (! filled($itemUrl) && $index === array_key_last($this->breadcrumbs)) {
                    $itemUrl = $currentUrl;
                }

                $item = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $label,
                ];

                if (filled($itemUrl)) {
                    $item['item'] = $itemUrl;
                }

                return $item;
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function twitterCard(): string
    {
        return filled($this->openGraphImage) ? 'summary_large_image' : 'summary';
    }

    private function currentPage(Request $request): int
    {
        if (! filled($this->paginationPageName)) {
            return 1;
        }

        $page = (int) $request->query($this->paginationPageName, 1);

        return $page > 1 ? $page : 1;
    }

    private function filteredQueryParameters(Request $request): array
    {
        if (! $this->preserveQueryString) {
            return [];
        }

        $queryParameters = $request->query();

        if ($this->allowedQueryParameters !== []) {
            $queryParameters = Arr::only($queryParameters, $this->allowedQueryParameters);
        }

        if (filled($this->paginationPageName)) {
            unset($queryParameters[$this->paginationPageName]);
        }

        return $queryParameters;
    }

    private function urlForPage(Request $request, int $pageNumber): string
    {
        $baseUrl = $this->canonical ?: $request->url();
        $queryParameters = $this->filteredQueryParameters($request);

        if (filled($this->paginationPageName) && $pageNumber > 1) {
            $queryParameters[$this->paginationPageName] = $pageNumber;
        }

        if ($queryParameters === []) {
            return $baseUrl;
        }

        ksort($queryParameters);

        return $baseUrl.'?'.Arr::query($queryParameters);
    }
}
