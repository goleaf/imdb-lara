@props([
    'rating' => null,
    'href' => null,
])

@php
    $ratingModel = $rating instanceof \App\Models\CertificateRating ? $rating : null;
    $rawValue = $ratingModel?->name;

    if (is_string($rating) && $rating !== '') {
        $rawValue = $rating;
    }

    $ratingValue = \App\Enums\CertificateRatingValue::fromValue($rawValue);
    $label = $ratingModel?->resolvedLabel()
        ?? $ratingValue?->label()
        ?? (filled($rawValue) ? (string) $rawValue : 'Unrated');
    $description = $ratingModel?->shortDescription()
        ?? $ratingValue?->description()
        ?? 'Regional age classification attached to this title.';
    $tone = $ratingModel?->tone()
        ?? $ratingValue?->tone()
        ?? 'neutral';
    $iconName = $ratingModel?->iconName()
        ?? $ratingValue?->iconName()
        ?? 'circle-question';
    $href ??= $ratingModel ? route('public.certificate-ratings.show', $ratingModel) : null;
    $tag = filled($href) ? 'a' : 'span';
@endphp

<{{ $tag }}
    @if (filled($href))
        href="{{ $href }}"
    @endif
    {{ $attributes->class([
        'sb-certificate-rating-chip',
        'sb-certificate-rating-tone--'.$tone,
        'hover:opacity-80 transition' => filled($href),
    ]) }}
    title="{{ $description }}"
    data-slot="certificate-rating-chip"
>
    <span class="sb-certificate-rating-chip__icon" data-slot="certificate-rating-chip-icon">
        <x-ui.fontawesome-icon :name="$iconName" class="text-[0.8rem]" />
    </span>
    <span class="truncate">{{ $label }}</span>
</{{ $tag }}>
