<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAssetUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_upload_and_replace_a_title_poster(): void
    {
        Storage::fake('public');

        $editor = User::factory()->editor()->create();
        $title = Title::factory()->movie()->create([
            'name' => 'Aurora Relay',
            'slug' => 'aurora-relay',
        ]);

        $this->actingAs($editor)
            ->post(route('admin.titles.media-assets.store', $title), [
                'kind' => MediaKind::Poster->value,
                'file' => UploadedFile::fake()->image('aurora-relay-poster.jpg', 640, 960),
                'url' => '',
                'alt_text' => 'Aurora Relay poster',
                'caption' => 'Primary theatrical poster',
                'provider' => '',
                'provider_key' => '',
                'language' => 'en',
                'duration_seconds' => '',
                'metadata' => json_encode(['credit' => 'Studio Unit'], JSON_THROW_ON_ERROR),
                'is_primary' => true,
                'position' => 0,
                'published_at' => '2026-04-01 12:00:00',
            ])
            ->assertRedirect(route('admin.titles.edit', $title));

        $mediaAsset = MediaAsset::query()
            ->whereMorphedTo('mediable', $title)
            ->firstOrFail();

        $this->assertSame(MediaKind::Poster, $mediaAsset->kind);
        $this->assertSame('upload', $mediaAsset->provider);
        $this->assertTrue($mediaAsset->is_primary);
        $this->assertSame(640, $mediaAsset->width);
        $this->assertSame(960, $mediaAsset->height);
        $this->assertSame('public', data_get($mediaAsset->metadata, 'storage.disk'));
        $this->assertSame($mediaAsset->provider_key, data_get($mediaAsset->metadata, 'storage.path'));
        $this->assertSame('Studio Unit', data_get($mediaAsset->metadata, 'credit'));
        Storage::disk('public')->assertExists($mediaAsset->provider_key);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee($mediaAsset->url, false);

        $originalPath = $mediaAsset->provider_key;

        $this->actingAs($editor)
            ->patch(route('admin.media-assets.update', $mediaAsset), [
                'kind' => MediaKind::Poster->value,
                'file' => UploadedFile::fake()->image('aurora-relay-poster-revised.jpg', 800, 1200),
                'url' => '',
                'alt_text' => 'Aurora Relay revised poster',
                'caption' => 'Revised primary poster',
                'width' => '',
                'height' => '',
                'provider' => '',
                'provider_key' => '',
                'language' => 'en',
                'duration_seconds' => '',
                'metadata' => json_encode(['credit' => 'Campaign Refresh'], JSON_THROW_ON_ERROR),
                'is_primary' => true,
                'position' => 0,
                'published_at' => '2026-04-02 12:00:00',
            ])
            ->assertRedirect(route('admin.media-assets.edit', $mediaAsset));

        $mediaAsset->refresh();

        $this->assertSame('upload', $mediaAsset->provider);
        $this->assertNotSame($originalPath, $mediaAsset->provider_key);
        $this->assertSame(800, $mediaAsset->width);
        $this->assertSame(1200, $mediaAsset->height);
        $this->assertSame('Campaign Refresh', data_get($mediaAsset->metadata, 'credit'));
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($mediaAsset->provider_key);
    }

    public function test_editor_can_upload_a_person_headshot_and_render_it_publicly(): void
    {
        Storage::fake('public');

        $editor = User::factory()->editor()->create();
        $person = Person::factory()->create([
            'name' => 'Marta Vale',
            'slug' => 'marta-vale',
            'is_published' => true,
        ]);

        $this->actingAs($editor)
            ->post(route('admin.people.media-assets.store', $person), [
                'kind' => MediaKind::Headshot->value,
                'file' => UploadedFile::fake()->image('marta-vale-headshot.jpg', 720, 900),
                'url' => '',
                'alt_text' => 'Portrait of Marta Vale',
                'caption' => 'Profile headshot',
                'provider' => '',
                'provider_key' => '',
                'language' => '',
                'duration_seconds' => '',
                'metadata' => '',
                'is_primary' => true,
                'position' => 0,
                'published_at' => '2026-04-01 10:00:00',
            ])
            ->assertRedirect(route('admin.people.edit', $person));

        $mediaAsset = MediaAsset::query()
            ->whereMorphedTo('mediable', $person)
            ->firstOrFail();

        $this->assertSame(MediaKind::Headshot, $mediaAsset->kind);
        $this->assertTrue($mediaAsset->isUploadBacked());
        Storage::disk('public')->assertExists($mediaAsset->provider_key);

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee($mediaAsset->url, false);
    }

    public function test_regular_users_cannot_manage_admin_media_assets(): void
    {
        Storage::fake('public');

        $regularUser = User::factory()->create();
        $title = Title::factory()->movie()->create();

        $this->actingAs($regularUser)
            ->post(route('admin.titles.media-assets.store', $title), [
                'kind' => MediaKind::Poster->value,
                'file' => UploadedFile::fake()->image('forbidden-poster.jpg', 600, 900),
                'url' => '',
                'alt_text' => 'Forbidden poster',
                'caption' => 'Should never persist',
                'provider' => '',
                'provider_key' => '',
                'language' => '',
                'duration_seconds' => '',
                'metadata' => '',
                'is_primary' => true,
                'position' => 0,
                'published_at' => '',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('media_assets', 0);
    }
}
