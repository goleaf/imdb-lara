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

    public function scopeForKinds(Builder $query, MediaKind ...$kinds): Builder
    {
        if ($kinds === []) {
            return $query;
        }

        return $query->whereIn(
            'kind',
            array_map(static fn (MediaKind $kind): string => $kind->value, $kinds),
        );
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->whereIn('kind', MediaKind::imageValues());
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->whereIn('kind', MediaKind::videoValues());
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

    public function isImage(): bool
    {
        return $this->kind?->isImage() ?? false;
    }

    public function isVideo(): bool
    {
        return $this->kind?->isVideo() ?? false;
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

    public function adminAttachedLabel(): string
    {
        $mediable = $this->mediable;

        return match (true) {
            $mediable instanceof Title => $mediable->name,
            $mediable instanceof Person => $mediable->name,
            default => class_basename((string) $this->mediable_type).' #'.$this->mediable_id,
        };
    }

    public function adminAttachedEditUrl(): ?string
    {
        $mediable = $this->mediable;

        return match (true) {
            $mediable instanceof Title => route('admin.titles.edit', $mediable),
            $mediable instanceof Person => route('admin.people.edit', $mediable),
            default => null,
        };
    }
}
