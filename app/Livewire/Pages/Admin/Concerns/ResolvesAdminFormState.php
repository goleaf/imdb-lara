<?php

namespace App\Livewire\Pages\Admin\Concerns;

use App\Enums\MediaKind;
use App\Models\MediaAsset;

trait ResolvesAdminFormState
{
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
}
