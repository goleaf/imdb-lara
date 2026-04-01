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

    $iconName = null;
    $canRender = false;

    if ($type && $normalizedCode !== '') {
        $flagSvgName = collect([
            $variant === 'default' ? null : $variant,
            $type,
            $normalizedCode,
        ])->filter()->implode('-');

        $iconName = 'flag-'.$flagSvgName;

        $iconFile = collect(app(\BladeUI\Icons\Factory::class)->all()['blade-flags']['paths'] ?? [])
            ->contains(fn (string $path): bool => file_exists($path.'/'.$flagSvgName.'.svg'));

        $canRender = $iconFile;
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
