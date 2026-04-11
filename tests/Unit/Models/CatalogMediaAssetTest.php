<?php

namespace Tests\Unit\Models;

use App\Enums\MediaKind;
use App\Models\CatalogMediaAsset;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CatalogMediaAssetTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_it_hides_generic_media_copy_and_uses_accessible_fallback_text(): void
    {
        $asset = CatalogMediaAsset::fromCatalog([
            'kind' => MediaKind::Poster,
            'url' => 'https://cdn.example.com/poster.jpg',
            'alt_text' => 'Title image',
        ]);

        $this->assertNull($asset->meaningfulCaption());
        $this->assertSame('Spider-Man: Into the Spider-Verse', $asset->accessibleAltText('Spider-Man: Into the Spider-Verse'));
    }

    public function test_it_prefers_real_caption_text_when_available(): void
    {
        $asset = CatalogMediaAsset::fromCatalog([
            'kind' => MediaKind::Backdrop,
            'url' => 'https://cdn.example.com/backdrop.jpg',
            'alt_text' => 'Title image',
            'caption' => 'Miles swings through the city skyline.',
        ]);

        $this->assertSame('Miles swings through the city skyline.', $asset->meaningfulCaption());
        $this->assertSame('Miles swings through the city skyline.', $asset->accessibleAltText());
    }

    public function test_it_formats_duration_labels_with_hours_for_longer_videos(): void
    {
        $clip = CatalogMediaAsset::fromCatalog([
            'kind' => MediaKind::Clip,
            'url' => 'https://cdn.example.com/clip.mp4',
            'duration_seconds' => 180,
        ]);
        $featurette = CatalogMediaAsset::fromCatalog([
            'kind' => MediaKind::Featurette,
            'url' => 'https://cdn.example.com/featurette.mp4',
            'duration_seconds' => 7020,
        ]);

        $this->assertSame('3 min', $clip->durationMinutesLabel());
        $this->assertSame('117 min (1h 57min)', $featurette->durationMinutesLabel());
    }
}
