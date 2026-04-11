@props([
    'rank' => null,
])

@php
    $normalizedRank = is_numeric($rank) ? (int) $rank : null;

    [$icon, $color, $label] = match ($normalizedRank) {
        1 => ['trophy', 'amber', 'Rank #1'],
        2 => ['star', 'blue', 'Rank #2'],
        3 => ['sparkles', 'rose', 'Rank #3'],
        default => ['bars-3-bottom-left', null, $normalizedRank ? 'Rank #'.number_format($normalizedRank) : null],
    };
@endphp

@if ($normalizedRank)
    <x-ui.badge
        variant="outline"
        :color="$color"
        :icon="$icon"
        pill
        data-slot="winner-rank-badge"
        :data-rank="$normalizedRank"
    >
        {{ $label }}
    </x-ui.badge>
@endif
