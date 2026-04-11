<?php

namespace App\Actions\Layout;

use Illuminate\Support\Str;

class ResolveBreadcrumbIconAction
{
    /**
     * @var array<string, string>
     */
    private const EXACT_LABEL_ICON_MAP = [
        'admin' => 'shield-check',
        'awards' => 'trophy',
        'box office' => 'banknotes',
        'browse people' => 'users',
        'catalog explorer' => 'rectangle-stack',
        'changes' => 'sparkles',
        'contributions queue' => 'clipboard-document-check',
        'create' => 'plus',
        'create credit' => 'plus',
        'discovery' => 'sparkles',
        'discover' => 'sparkles',
        'edit' => 'pencil-square',
        'edit credit' => 'pencil-square',
        'full cast' => 'users',
        'home' => 'home',
        'interest categories' => 'squares-2x2',
        'keywords & connections' => 'rectangle-stack',
        'latest reviews' => 'chat-bubble-left-right',
        'manage genres' => 'tag',
        'manage award categories' => 'trophy',
        'manage media assets' => 'photo',
        'manage people' => 'users',
        'manage titles' => 'film',
        'media gallery' => 'photo',
        'moderate reviews' => 'chat-bubble-left-right',
        'parents guide' => 'information-circle',
        'people' => 'users',
        'profile settings' => 'cog-6-tooth',
        'public lists' => 'queue-list',
        'reports' => 'flag',
        'search' => 'magnifying-glass',
        'titles' => 'film',
        'trailers' => 'play',
        'trivia & goofs' => 'sparkles',
        'tv shows' => 'tv',
        'your lists' => 'queue-list',
        'your watchlist' => 'bookmark',
    ];

    public function handle(?string $label = null, ?string $href = null): ?string
    {
        $normalizedLabel = $this->normalizeLabel($label);
        $segments = $this->segmentsFor($href);

        return $this->iconForExactLabel($normalizedLabel, $segments)
            ?? $this->iconForPath($segments, $normalizedLabel);
    }

    /**
     * @param  list<string>  $segments
     */
    private function iconForExactLabel(string $normalizedLabel, array $segments): ?string
    {
        if ($normalizedLabel === '') {
            return null;
        }

        if ($normalizedLabel === 'dashboard') {
            return in_array('admin', $segments, true)
                ? 'chart-bar-square'
                : 'home';
        }

        return self::EXACT_LABEL_ICON_MAP[$normalizedLabel] ?? null;
    }

    /**
     * @param  list<string>  $segments
     */
    private function iconForPath(array $segments, string $normalizedLabel): ?string
    {
        if ($segments === []) {
            return $normalizedLabel === 'home' ? 'home' : null;
        }

        if (in_array('admin', $segments, true)) {
            return $this->iconForAdminPath($segments, $normalizedLabel);
        }

        if (in_array('watchlist', $segments, true)) {
            return 'bookmark';
        }

        if (in_array('settings', $segments, true)) {
            return 'cog-6-tooth';
        }

        if (in_array('discover', $segments, true)) {
            return 'sparkles';
        }

        if (in_array('search', $segments, true)) {
            return 'magnifying-glass';
        }

        if (in_array('awards', $segments, true)) {
            return 'trophy';
        }

        if (in_array('trailers', $segments, true)) {
            return 'play';
        }

        if (in_array('reviews', $segments, true)) {
            return 'chat-bubble-left-right';
        }

        if (in_array('lists', $segments, true)) {
            return 'queue-list';
        }

        if (in_array('users', $segments, true)) {
            return 'user';
        }

        if (in_array('people', $segments, true)) {
            return $this->isCollectionLabel($normalizedLabel, ['browse people', 'manage people', 'people'])
                ? 'users'
                : 'user';
        }

        if (in_array('titles', $segments, true) || in_array('movies', $segments, true)) {
            return $this->iconForTitlePath($segments);
        }

        if (
            in_array('series', $segments, true)
            || in_array('seasons', $segments, true)
            || in_array('episodes', $segments, true)
        ) {
            return 'tv';
        }

        if (in_array('catalog', $segments, true) || in_array('explorer', $segments, true)) {
            return 'rectangle-stack';
        }

        if (in_array('interest-categories', $segments, true)) {
            return 'squares-2x2';
        }

        if (in_array('changes', $segments, true)) {
            return 'sparkles';
        }

        if (in_array('companies', $segments, true)) {
            return 'building-office-2';
        }

        if (
            in_array('aka-attributes', $segments, true)
            || in_array('aka-types', $segments, true)
            || in_array('award-categories', $segments, true)
            || in_array('company-credit-attributes', $segments, true)
        ) {
            return in_array('award-categories', $segments, true) ? 'trophy' : 'tag';
        }

        if (in_array('certificates', $segments, true)) {
            return 'shield-check';
        }

        return null;
    }

    /**
     * @param  list<string>  $segments
     */
    private function iconForAdminPath(array $segments, string $normalizedLabel): string
    {
        if (in_array('reports', $segments, true)) {
            return 'flag';
        }

        if (in_array('contributions', $segments, true)) {
            return 'clipboard-document-check';
        }

        if (in_array('reviews', $segments, true)) {
            return 'chat-bubble-left-right';
        }

        if (in_array('media-assets', $segments, true)) {
            return 'photo';
        }

        if (in_array('genres', $segments, true)) {
            return 'tag';
        }

        if (in_array('award-categories', $segments, true)) {
            return 'trophy';
        }

        if (in_array('people', $segments, true)) {
            return $this->isCollectionLabel($normalizedLabel, ['manage people', 'people'])
                ? 'users'
                : 'user';
        }

        if (in_array('titles', $segments, true) || in_array('credits', $segments, true)) {
            return 'film';
        }

        if (in_array('seasons', $segments, true) || in_array('episodes', $segments, true)) {
            return 'tv';
        }

        return 'chart-bar-square';
    }

    /**
     * @param  list<string>  $segments
     */
    private function iconForTitlePath(array $segments): string
    {
        if (in_array('box-office', $segments, true)) {
            return 'banknotes';
        }

        if (in_array('cast', $segments, true)) {
            return 'users';
        }

        if (in_array('metadata', $segments, true)) {
            return 'rectangle-stack';
        }

        if (in_array('parents-guide', $segments, true)) {
            return 'information-circle';
        }

        if (
            in_array('media', $segments, true)
            || in_array('gallery', $segments, true)
            || in_array('archive', $segments, true)
        ) {
            return 'photo';
        }

        if (in_array('trivia', $segments, true) || in_array('goofs', $segments, true)) {
            return 'sparkles';
        }

        return 'film';
    }

    /**
     * @return list<string>
     */
    private function segmentsFor(?string $href): array
    {
        $path = filled($href)
            ? parse_url($href, PHP_URL_PATH)
            : request()->path();

        if (! is_string($path) || $path === '' || $path === '/') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $segment): string => Str::of(urldecode($segment))
                ->lower()
                ->trim()
                ->toString(),
            explode('/', trim($path, '/')),
        )));
    }

    private function normalizeLabel(?string $label): string
    {
        if (! filled($label)) {
            return '';
        }

        return Str::of(html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->squish()
            ->lower()
            ->toString();
    }

    /**
     * @param  list<string>  $labels
     */
    private function isCollectionLabel(string $normalizedLabel, array $labels): bool
    {
        return in_array($normalizedLabel, $labels, true);
    }
}
