@props([
    'type' => 'country',
    'code' => null,
    'variant' => 'flat',
])

@php
    $type = in_array($type, ['country', 'language'], true) ? $type : null;

    $variant = match ($variant) {
        'default', 'circle', 'flat' => $variant,
        default => 'flat',
    };

    $normalizedCode = str((string) $code)
        ->trim()
        ->replace('_', '-')
        ->lower()
        ->replaceMatches('/[^a-z0-9-]+/', '')
        ->trim('-')
        ->toString();

    $iconPaths = collect(app(\BladeUI\Icons\Factory::class)->all()['blade-flags']['paths'] ?? []);
    $iconExists = function (string $svgName) use ($iconPaths): bool {
        return $iconPaths->contains(fn (string $path): bool => file_exists($path.'/'.$svgName.'.svg'));
    };

    $resolveLanguageSvgName = function (string $languageCode, string $variant) use ($iconPaths, $iconExists): ?string {
        $baseLanguageCode = str($languageCode)->before('-')->toString();
        $candidateCodes = collect([$languageCode]);

        if ($baseLanguageCode !== '' && $baseLanguageCode !== $languageCode) {
            $candidateCodes->push($baseLanguageCode);
        }

        $languageCountriesPath = base_path('vendor/outhebox/blade-flags/config/language-countries.json');
        $languageCountries = file_exists($languageCountriesPath)
            ? json_decode((string) file_get_contents($languageCountriesPath), true)
            : [];
        $languageCountryConfig = is_array($languageCountries) ? ($languageCountries[$baseLanguageCode] ?? null) : null;

        if (is_array($languageCountryConfig)) {
            $countryVariants = collect([
                str($languageCode)->after('-')->toString(),
                $languageCountryConfig['default'] ?? null,
            ])->filter(fn (?string $countryCode): bool => filled($countryCode));

            foreach ($countryVariants as $countryVariant) {
                $candidateCodes->push($baseLanguageCode.'-'.$countryVariant);
            }
        }

        foreach ($candidateCodes->unique()->filter() as $candidateCode) {
            $svgName = collect([
                $variant === 'default' ? null : $variant,
                'language',
                $candidateCode,
            ])->filter()->implode('-');

            if ($iconExists($svgName)) {
                return $svgName;
            }
        }

        if ($baseLanguageCode === '') {
            return null;
        }

        return $iconPaths
            ->flatMap(fn (string $path): array => glob($path.'/language-'.$baseLanguageCode.'-*.svg') ?: [])
            ->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME))
            ->sort()
            ->first();
    };

    $iconName = null;
    $canRender = false;

    if ($type && $normalizedCode !== '') {
        $flagSvgName = $type === 'language'
            ? $resolveLanguageSvgName($normalizedCode, $variant)
            : collect([
                $variant === 'default' ? null : $variant,
                $type,
                $normalizedCode,
            ])->filter()->implode('-');

        if ($flagSvgName) {
            $iconName = 'flag-'.$flagSvgName;
            $canRender = $iconExists($flagSvgName);
        }
    }

    $iconClasses = trim(($attributes->get('class') ?: 'size-4').' shrink-0');
@endphp

@if ($canRender)
    <span
        {{ $attributes->except('class')->class('inline-flex items-center') }}
        data-slot="flag"
        data-flag-type="{{ $type }}"
        data-flag-code="{{ $normalizedCode }}"
        aria-hidden="true"
    >
        <x-ui.icon :name="'bk:'.$iconName" class="{{ $iconClasses }}" />
    </span>
@endif
