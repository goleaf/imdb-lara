<?php

namespace App\Models;

use App\Enums\MediaKind;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'kind',
        'url',
        'alt_text',
        'caption',
        'width',
        'height',
        'provider',
        'provider_key',
        'language',
        'duration_seconds',
        'metadata',
        'is_primary',
        'position',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'is_primary' => 'boolean',
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_primary')
            ->orderBy('position')
            ->orderByDesc('published_at')
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

            if ($primaryAsset) {
                return $primaryAsset;
            }

            $matchingAsset = $collection->first(
                fn (self $asset): bool => $asset->kind === $kind,
            );

            if ($matchingAsset) {
                return $matchingAsset;
            }
        }

        return $collection->first();
    }

    public function isUploadBacked(): bool
    {
        return $this->provider === 'upload' && filled($this->provider_key);
    }

    public function storageDisk(): ?string
    {
        $disk = data_get($this->metadata, 'storage.disk');

        if (is_string($disk) && $disk !== '') {
            return $disk;
        }

        return $this->isUploadBacked() ? 'public' : null;
    }

    public function storagePath(): ?string
    {
        $path = data_get($this->metadata, 'storage.path');

        if (is_string($path) && $path !== '') {
            return $path;
        }

        return $this->isUploadBacked() ? $this->provider_key : null;
    }
}
