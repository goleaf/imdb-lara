<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Enums\MediaKind;
use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveMediaAssetAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(MediaAsset $mediaAsset, Model $mediable, array $attributes): MediaAsset
    {
        $attributes = $this->normalizeAttributes($attributes);
        $uploadedFile = $attributes['file'] ?? null;

        unset($attributes['file']);

        $attributes['is_primary'] = (bool) ($attributes['is_primary'] ?? false);
        $attributes['metadata'] = $this->decodeMetadata($attributes['metadata'] ?? null);

        $previousUpload = $mediaAsset->exists && $mediaAsset->isUploadBacked()
            ? [
                'disk' => $mediaAsset->storageDisk(),
                'path' => $mediaAsset->storagePath(),
            ]
            : null;
        $storedUpload = null;
        $deletePreviousUpload = false;

        if ($uploadedFile instanceof UploadedFile) {
            $storedUpload = $this->storeUploadedFile(
                $uploadedFile,
                $mediable,
                MediaKind::tryFrom((string) $attributes['kind']) ?? MediaKind::Gallery,
            );

            $attributes = $this->applyStoredUpload(
                $attributes,
                $uploadedFile,
                $storedUpload['disk'],
                $storedUpload['path'],
            );
            $deletePreviousUpload = $previousUpload !== null;
        } elseif ($mediaAsset->exists && $mediaAsset->isUploadBacked()) {
            if (blank($attributes['url'] ?? null)) {
                $attributes = $this->preserveExistingUploadAttributes($mediaAsset, $attributes);
            } else {
                $deletePreviousUpload = true;
            }
        }

        if ($attributes['is_primary']) {
            $existingAssetsQuery = $mediable->mediaAssets()
                ->where('kind', $attributes['kind']);

            if ($mediaAsset->exists) {
                $existingAssetsQuery->whereKeyNot($mediaAsset->getKey());
            }

            $existingAssetsQuery->update(['is_primary' => false]);
        }

        try {
            $mediaAsset->fill($attributes);
            $mediaAsset->mediable()->associate($mediable);
            $mediaAsset->save();
        } catch (\Throwable $throwable) {
            if ($storedUpload !== null) {
                Storage::disk($storedUpload['disk'])->delete($storedUpload['path']);
            }

            throw $throwable;
        }

        if ($deletePreviousUpload && $previousUpload !== null) {
            $this->deleteStoredUpload(
                $previousUpload['disk'],
                $previousUpload['path'],
                $mediaAsset,
            );
        }

        return $mediaAsset->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preserveExistingUploadAttributes(MediaAsset $mediaAsset, array $attributes): array
    {
        $attributes['url'] = $mediaAsset->url;
        $attributes['provider'] = $mediaAsset->provider;
        $attributes['provider_key'] = $mediaAsset->provider_key;
        $attributes['width'] = $attributes['width'] ?? $mediaAsset->width;
        $attributes['height'] = $attributes['height'] ?? $mediaAsset->height;
        $attributes['metadata'] = $this->mergeMetadata($mediaAsset->metadata, $attributes['metadata']);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function applyStoredUpload(
        array $attributes,
        UploadedFile $uploadedFile,
        string $disk,
        string $path,
    ): array {
        $attributes['url'] = Storage::disk($disk)->url($path);
        $attributes['provider'] = 'upload';
        $attributes['provider_key'] = $path;
        $attributes['metadata'] = $this->mergeMetadata(
            [
                'storage' => [
                    'disk' => $disk,
                    'path' => $path,
                    'visibility' => 'public',
                    'mime_type' => $uploadedFile->getClientMimeType(),
                    'file_size' => $uploadedFile->getSize(),
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'extension' => Str::lower($uploadedFile->getClientOriginalExtension()),
                ],
            ],
            $attributes['metadata'],
        );

        $realPath = $uploadedFile->getRealPath();
        $dimensions = $realPath ? getimagesize($realPath) : false;

        if (is_array($dimensions)) {
            $attributes['width'] = (int) ($dimensions[0] ?? $attributes['width'] ?? 0);
            $attributes['height'] = (int) ($dimensions[1] ?? $attributes['height'] ?? 0);
        }

        return $attributes;
    }

    /**
     * @return array{disk: string, path: string}
     */
    private function storeUploadedFile(
        UploadedFile $uploadedFile,
        Model $mediable,
        MediaKind $kind,
    ): array {
        $disk = 'public';
        $path = $uploadedFile->storePublicly(
            $this->storageDirectory($mediable, $kind),
            $disk,
        );

        return [
            'disk' => $disk,
            'path' => $path,
        ];
    }

    private function storageDirectory(Model $mediable, MediaKind $kind): string
    {
        $mediableSegment = Str::plural(Str::kebab(class_basename($mediable)));
        $slug = $mediable->getAttribute('slug');
        $identifier = $mediable->getKey();

        if (is_string($slug) && $slug !== '') {
            $identifier .= '-'.Str::slug($slug);
        }

        return sprintf('media/%s/%s/%s', $mediableSegment, $identifier, $kind->value);
    }

    /**
     * @param  array<string, mixed>|null  $baseMetadata
     * @param  array<string, mixed>|null  $overrideMetadata
     * @return array<string, mixed>|null
     */
    private function mergeMetadata(?array $baseMetadata, ?array $overrideMetadata): ?array
    {
        if ($baseMetadata === null) {
            return $overrideMetadata;
        }

        if ($overrideMetadata === null) {
            return $baseMetadata;
        }

        /** @var array<string, mixed> */
        return array_replace_recursive($baseMetadata, $overrideMetadata);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeMetadata(mixed $metadata): ?array
    {
        if (! is_string($metadata) || trim($metadata) === '') {
            return null;
        }

        /** @var mixed */
        $decoded = json_decode($metadata, true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }

    private function deleteStoredUpload(?string $disk, ?string $path, MediaAsset $mediaAsset): void
    {
        if ($disk === null || $path === null) {
            return;
        }

        if ($mediaAsset->isUploadBacked() && $mediaAsset->storagePath() === $path) {
            return;
        }

        Storage::disk($disk)->delete($path);
    }
}
