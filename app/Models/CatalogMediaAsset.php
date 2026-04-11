<?php

namespace App\Models;

use App\Enums\MediaKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CatalogMediaAsset extends Model
{
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
        'is_primary',
        'position',
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
            'metadata' => 'array',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromCatalog(array $attributes): self
    {
        $asset = new self;
        $asset->forceFill($attributes);
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

    public function durationMinutesLabel(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        return max(1, (int) ceil($this->duration_seconds / 60)).' min';
    }

    public function durationSecondsLabel(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        return number_format($this->duration_seconds).' sec';
    }
}
