<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Models\Concerns\FormatsRuntimeLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CatalogMediaAsset extends Model
{
    use FormatsRuntimeLabels;

    /**
     * @var list<string>
     */
    private const GENERIC_MEDIA_TEXT = [
        'title image',
        'media asset',
    ];

    protected $connection = 'imdb_mysql';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kind',
        'url',
        'alt_text',
        'caption',
        'width',
        'height',
        'duration_seconds',
        'provider',
        'provider_key',
        'is_primary',
        'position',
        'published_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
            'is_primary' => 'boolean',
            'position' => 'integer',
            'published_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromCatalog(array $attributes): self
    {
        $asset = new self;
        $asset->forceFill([
            'kind' => null,
            'url' => null,
            'alt_text' => null,
            'caption' => null,
            'width' => null,
            'height' => null,
            'duration_seconds' => null,
            'provider' => null,
            'provider_key' => null,
            'is_primary' => false,
            'position' => 0,
            'published_at' => null,
            'metadata' => null,
            ...$attributes,
        ]);
        $asset->exists = true;

        return $asset;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_primary')
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * @param  iterable<array-key, mixed>  $assets
     */
    public static function preferredFrom(iterable $assets, MediaKind ...$kinds): ?self
    {
        /** @var Collection<int, self> $collection */
        $collection = collect($assets)
            ->filter(fn (mixed $asset): bool => $asset instanceof self)
            ->values();

        if ($collection->isEmpty()) {
            return null;
        }

        foreach ($kinds as $kind) {
            $primaryAsset = $collection->first(
                fn (self $asset): bool => $asset->kind === $kind && $asset->is_primary,
            );

            if ($primaryAsset instanceof self) {
                return $primaryAsset;
            }

            $matchingAsset = $collection->first(
                fn (self $asset): bool => $asset->kind === $kind,
            );

            if ($matchingAsset instanceof self) {
                return $matchingAsset;
            }
        }

        return $collection->first();
    }

    public function kindLabel(): string
    {
        return $this->kind?->label() ?? 'Media Asset';
    }

    public function meaningfulCaption(): ?string
    {
        foreach ([$this->caption, $this->alt_text] as $candidate) {
            if ($this->isMeaningfulMediaText($candidate)) {
                return trim((string) $candidate);
            }
        }

        return null;
    }

    public function accessibleAltText(?string $fallback = null): string
    {
        if ($this->isMeaningfulMediaText($this->alt_text)) {
            return trim((string) $this->alt_text);
        }

        if ($this->isMeaningfulMediaText($fallback)) {
            return trim((string) $fallback);
        }

        if ($caption = $this->meaningfulCaption()) {
            return $caption;
        }

        return 'Media image';
    }

    public function durationMinutesLabel(): ?string
    {
        return self::formatSecondsAsMinutesLabel($this->duration_seconds);
    }

    public function durationSecondsLabel(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        return number_format($this->duration_seconds).' sec';
    }

    private function isMeaningfulMediaText(?string $text): bool
    {
        if (! filled($text)) {
            return false;
        }

        return ! in_array(
            mb_strtolower(trim((string) $text)),
            self::GENERIC_MEDIA_TEXT,
            true,
        );
    }
}
