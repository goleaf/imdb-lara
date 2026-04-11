<?php

namespace App\Actions\Layout;

use BladeUI\Icons\Factory;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ResolveFlagViewDataAction
{
    /**
     * @var list<string>|null
     */
    private static ?array $iconPaths = null;

    /**
     * @var array<string, mixed>|null
     */
    private static ?array $languageCountries = null;

    public function __construct(private Factory $iconFactory) {}

    /**
     * @return array{
     *     type: string,
     *     code: string,
     *     iconMarkup: HtmlString
     * }|null
     */
    public function handle(string $type = 'country', mixed $code = null, string $variant = 'flat', ?string $className = null): ?array
    {
        $resolvedType = in_array($type, ['country', 'language'], true) ? $type : null;

        if ($resolvedType === null) {
            return null;
        }

        $resolvedVariant = match ($variant) {
            'default', 'circle', 'flat' => $variant,
            default => 'flat',
        };

        $normalizedCode = Str::of((string) $code)
            ->trim()
            ->replace('_', '-')
            ->lower()
            ->replaceMatches('/[^a-z0-9-]+/', '')
            ->trim('-')
            ->toString();

        if ($normalizedCode === '') {
            return null;
        }

        $svgName = $resolvedType === 'language'
            ? $this->resolveLanguageSvgName($normalizedCode, $resolvedVariant)
            : $this->buildSvgName($resolvedType, $normalizedCode, $resolvedVariant);

        if ($svgName === null || ! $this->iconExists($svgName)) {
            return null;
        }

        $iconMarkup = $this->iconFactory
            ->svg(
                'flag-'.$svgName,
                trim(($className ?: 'size-4').' shrink-0'),
                ['data-slot' => 'icon', 'aria-hidden' => 'true'],
            )
            ->toHtml();

        return [
            'type' => $resolvedType,
            'code' => $normalizedCode,
            'iconMarkup' => new HtmlString($this->scopeSvgIds($iconMarkup)),
        ];
    }

    private function buildSvgName(string $type, string $code, string $variant): string
    {
        return collect([
            $variant === 'default' ? null : $variant,
            $type,
            $code,
        ])->filter()->implode('-');
    }

    private function iconExists(string $svgName): bool
    {
        foreach ($this->iconPaths() as $path) {
            if (is_file($path.'/'.$svgName.'.svg')) {
                return true;
            }
        }

        return false;
    }

    private function resolveLanguageSvgName(string $languageCode, string $variant): ?string
    {
        $baseLanguageCode = Str::before($languageCode, '-');
        $candidateCodes = [$languageCode];

        if ($baseLanguageCode !== '' && $baseLanguageCode !== $languageCode) {
            $candidateCodes[] = $baseLanguageCode;
        }

        $languageCountryConfig = $this->languageCountries()[$baseLanguageCode] ?? null;

        if (is_array($languageCountryConfig)) {
            $countryVariants = array_filter([
                Str::of($languageCode)->after('-')->toString(),
                is_string($languageCountryConfig['default'] ?? null) ? $languageCountryConfig['default'] : null,
            ]);

            foreach ($countryVariants as $countryVariant) {
                $candidateCodes[] = $baseLanguageCode.'-'.$countryVariant;
            }
        }

        foreach (array_values(array_unique(array_filter($candidateCodes))) as $candidateCode) {
            $svgName = $this->buildSvgName('language', $candidateCode, $variant);

            if ($this->iconExists($svgName)) {
                return $svgName;
            }
        }

        if ($baseLanguageCode === '') {
            return null;
        }

        $fallbackMatches = [];

        foreach ($this->iconPaths() as $path) {
            foreach (glob($path.'/language-'.$baseLanguageCode.'-*.svg') ?: [] as $match) {
                $fallbackMatches[] = pathinfo($match, PATHINFO_FILENAME);
            }
        }

        sort($fallbackMatches);

        return $fallbackMatches[0] ?? null;
    }

    /**
     * @return list<string>
     */
    private function iconPaths(): array
    {
        if (self::$iconPaths !== null) {
            return self::$iconPaths;
        }

        $sets = $this->iconFactory->all();
        $paths = $sets['blade-flags']['paths'] ?? [];

        if (! is_array($paths)) {
            return self::$iconPaths = [];
        }

        return self::$iconPaths = array_values(array_filter($paths, 'is_string'));
    }

    /**
     * @return array<string, mixed>
     */
    private function languageCountries(): array
    {
        if (self::$languageCountries !== null) {
            return self::$languageCountries;
        }

        $languageCountriesPath = base_path('vendor/outhebox/blade-flags/config/language-countries.json');

        if (! is_file($languageCountriesPath)) {
            return self::$languageCountries = [];
        }

        $decoded = json_decode((string) file_get_contents($languageCountriesPath), true);

        return self::$languageCountries = is_array($decoded) ? $decoded : [];
    }

    private function scopeSvgIds(string $iconMarkup): string
    {
        preg_match_all('/\sid="([^"]+)"/', $iconMarkup, $iconIdMatches);

        $iconIdMap = collect($iconIdMatches[1] ?? [])
            ->filter(fn (string $id): bool => $id !== '')
            ->unique()
            ->mapWithKeys(fn (string $id): array => [
                $id => 'flag-'.Str::uuid()->toString().'-'.$id,
            ])
            ->all();

        foreach ($iconIdMap as $originalId => $scopedId) {
            $iconMarkup = str_replace('id="'.$originalId.'"', 'id="'.$scopedId.'"', $iconMarkup);
            $iconMarkup = str_replace('url(#'.$originalId.')', 'url(#'.$scopedId.')', $iconMarkup);
            $iconMarkup = str_replace('href="#'.$originalId.'"', 'href="#'.$scopedId.'"', $iconMarkup);
            $iconMarkup = str_replace('xlink:href="#'.$originalId.'"', 'xlink:href="#'.$scopedId.'"', $iconMarkup);
        }

        return $iconMarkup;
    }
}
