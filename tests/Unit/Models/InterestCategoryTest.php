<?php

namespace Tests\Unit\Models;

use App\Enums\MediaKind;
use App\Models\InterestCategory;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class InterestCategoryTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_preferred_directory_image_builds_a_catalog_media_asset_from_selected_columns(): void
    {
        $interestCategory = tap(new InterestCategory, function (InterestCategory $interestCategory): void {
            $interestCategory->forceFill([
                'id' => 7,
                'name' => 'Animation',
                'directory_image_url' => 'https://images.example.test/categories/animation.jpg',
                'directory_image_width' => 1600,
                'directory_image_height' => 900,
                'directory_image_type' => 'primary',
            ]);
        });

        $asset = $interestCategory->preferredDirectoryImage();

        $this->assertNotNull($asset);
        $this->assertSame(MediaKind::Gallery, $asset->kind);
        $this->assertSame('https://images.example.test/categories/animation.jpg', $asset->url);
        $this->assertSame('Animation', $asset->accessibleAltText());
        $this->assertTrue($asset->is_primary);
    }

    public function test_preferred_directory_image_returns_null_when_no_preview_image_was_selected(): void
    {
        $interestCategory = new InterestCategory([
            'id' => 7,
            'name' => 'Animation',
        ]);

        $this->assertNull($interestCategory->preferredDirectoryImage());
    }
}
