<?php

namespace App\Livewire\Pages\Admin\Concerns;

use App\Enums\MediaKind;
use App\Models\MediaAsset;

trait ResolvesAdminFormState
{
    /**
     * @return array{
     *     fieldName: \Closure(string): string,
     *     fieldOldInputKey: \Closure(string): string,
     *     fieldStatePath: \Closure(string): string
     * }
     */
    public function adminFormBindingData(?string $prefix = null): array
    {
        return [
            'fieldStatePath' => fn (string $field): string => $this->adminFieldStatePath($prefix, $field),
            'fieldName' => fn (string $field): string => $this->adminFieldName($prefix, $field),
            'fieldOldInputKey' => fn (string $field): string => $this->adminFieldOldInputKey($prefix, $field),
        ];
    }

    public function adminFieldStatePath(?string $prefix, string $field): string
    {
        return filled($prefix)
            ? sprintf('%s.%s', $prefix, $field)
            : $field;
    }

    public function adminFieldName(?string $prefix, string $field): string
    {
        return filled($prefix)
            ? sprintf('%s[%s]', $prefix, $field)
            : $field;
    }

    public function adminFieldOldInputKey(?string $prefix, string $field): string
    {
        return $this->adminFieldStatePath($prefix, $field);
    }

    /**
     * @return list<MediaKind>
     */
    public function adminAllowedMediaKinds(MediaAsset $mediaAsset): array
    {
        return MediaKind::allowedForMediable($mediaAsset->mediable ?? $mediaAsset->mediable_type);
    }

    public function adminAllowedMediaKindsIncludeVideo(MediaAsset $mediaAsset): bool
    {
        foreach ($this->adminAllowedMediaKinds($mediaAsset) as $mediaKind) {
            if ($mediaKind->isVideo()) {
                return true;
            }
        }

        return false;
    }

    public function adminMediaKindIsImage(mixed $kind): bool
    {
        return is_string($kind) && in_array($kind, MediaKind::imageValues(), true);
    }

    /**
     * @return array{
     *     allowedMediaKinds: list<MediaKind>,
     *     allowedMediaKindsIncludeVideo: bool,
     *     fieldName: \Closure(string): string,
     *     fieldOldInputKey: \Closure(string): string,
     *     fieldStatePath: \Closure(string): string,
     *     selectedKindIsImage: bool
     * }
     */
    public function adminMediaAssetFormData(MediaAsset $mediaAsset, ?string $prefix = null): array
    {
        return [
            ...$this->adminFormBindingData($prefix),
            'allowedMediaKinds' => $this->adminAllowedMediaKinds($mediaAsset),
            'allowedMediaKindsIncludeVideo' => $this->adminAllowedMediaKindsIncludeVideo($mediaAsset),
            'selectedKindIsImage' => $this->adminMediaKindIsImage(
                old('kind', $mediaAsset->kind?->value),
            ),
        ];
    }
}
