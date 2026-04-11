<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\ContributionStatus;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserStatus;
use App\Models\Contribution;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAssetUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_media_and_moderation_mutation_routes_are_registered(): void
    {
        $routeNames = [
            'admin.titles.media-assets.store',
            'admin.people.media-assets.store',
            'admin.media-assets.update',
            'admin.media-assets.destroy',
            'admin.reviews.update',
            'admin.reports.update',
            'admin.contributions.update',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName.' should be registered.');
        }
    }

    public function test_editor_can_upload_replace_and_delete_title_media_assets_and_public_title_page_uses_latest_primary(): void
    {
        Storage::fake('public');

        $editor = User::factory()->editor()->create();
        $title = Title::factory()->create();
        $existingPrimaryPoster = MediaAsset::factory()->poster()->for($title, 'mediable')->create();

        $this->actingAs($editor)
            ->post(route('admin.titles.media-assets.store', $title), [
                'kind' => 'poster',
                'file' => UploadedFile::fake()->image('poster-one.jpg', 600, 900),
                'url' => null,
                'alt_text' => 'Poster one',
                'caption' => 'Primary poster',
                'width' => null,
                'height' => null,
                'provider' => null,
                'provider_key' => null,
                'language' => 'en',
                'duration_seconds' => null,
                'metadata' => null,
                'is_primary' => '1',
                'position' => 0,
                'published_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.titles.edit', $title));

        $uploadedPoster = MediaAsset::query()
            ->where('mediable_type', Title::class)
            ->where('mediable_id', $title->id)
            ->where('provider', 'upload')
            ->latest('id')
            ->firstOrFail();

        $existingPrimaryPoster->refresh();

        $this->assertTrue($uploadedPoster->is_primary);
        $this->assertFalse($existingPrimaryPoster->is_primary);
        $this->assertNotNull($uploadedPoster->storagePath());
        Storage::disk('public')->assertExists($uploadedPoster->storagePath());

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee($uploadedPoster->url, false);

        $previousUploadPath = $uploadedPoster->storagePath();

        $this->actingAs($editor)
            ->patch(route('admin.media-assets.update', $uploadedPoster), [
                'kind' => 'poster',
                'file' => UploadedFile::fake()->image('poster-two.jpg', 800, 1200),
                'url' => null,
                'alt_text' => 'Poster two',
                'caption' => 'Updated poster',
                'width' => null,
                'height' => null,
                'provider' => null,
                'provider_key' => null,
                'language' => 'en',
                'duration_seconds' => null,
                'metadata' => null,
                'is_primary' => '1',
                'position' => 1,
                'published_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.media-assets.edit', $uploadedPoster));

        $uploadedPoster->refresh();

        $this->assertNotSame($previousUploadPath, $uploadedPoster->storagePath());
        Storage::disk('public')->assertMissing($previousUploadPath);
        Storage::disk('public')->assertExists($uploadedPoster->storagePath());

        $this->actingAs($editor)
            ->delete(route('admin.media-assets.destroy', $uploadedPoster))
            ->assertRedirect(route('admin.titles.edit', $title));

        $this->assertSoftDeleted('media_assets', ['id' => $uploadedPoster->id]);
        Storage::disk('public')->assertMissing($uploadedPoster->storagePath());
    }

    public function test_editor_can_upload_person_headshots_and_validation_rejects_invalid_media_sources_or_kinds(): void
    {
        Storage::fake('public');

        $editor = User::factory()->editor()->create();
        $person = Person::factory()->create();
        $title = Title::factory()->create();

        $this->actingAs($editor)
            ->post(route('admin.people.media-assets.store', $person), [
                'kind' => 'headshot',
                'file' => UploadedFile::fake()->image('headshot.jpg', 500, 700),
                'url' => null,
                'alt_text' => 'Ava Mercer headshot',
                'caption' => 'Portrait',
                'width' => null,
                'height' => null,
                'provider' => null,
                'provider_key' => null,
                'language' => 'en',
                'duration_seconds' => null,
                'metadata' => null,
                'is_primary' => '1',
                'position' => 0,
                'published_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.people.edit', $person));

        $headshot = MediaAsset::query()
            ->where('mediable_type', Person::class)
            ->where('mediable_id', $person->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('headshot', $headshot->kind->value);
        Storage::disk('public')->assertExists($headshot->storagePath());

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee($headshot->url, false);

        $this->actingAs($editor)
            ->from(route('admin.titles.edit', $title))
            ->post(route('admin.titles.media-assets.store', $title), [
                'kind' => 'trailer',
                'file' => UploadedFile::fake()->image('not-a-video.jpg'),
                'url' => null,
                'alt_text' => null,
                'caption' => null,
                'width' => null,
                'height' => null,
                'provider' => null,
                'provider_key' => null,
                'language' => null,
                'duration_seconds' => null,
                'metadata' => null,
                'is_primary' => '0',
                'position' => 0,
                'published_at' => null,
            ])
            ->assertRedirect(route('admin.titles.edit', $title))
            ->assertSessionHasErrors(['file', 'url']);

        $this->actingAs($editor)
            ->from(route('admin.people.edit', $person))
            ->post(route('admin.people.media-assets.store', $person), [
                'kind' => 'poster',
                'file' => null,
                'url' => 'https://videos.example.test/person-poster',
                'alt_text' => null,
                'caption' => null,
                'width' => null,
                'height' => null,
                'provider' => 'external',
                'provider_key' => 'person-poster',
                'language' => null,
                'duration_seconds' => null,
                'metadata' => null,
                'is_primary' => '0',
                'position' => 0,
                'published_at' => null,
            ])
            ->assertRedirect(route('admin.people.edit', $person))
            ->assertSessionHasErrors(['kind']);
    }

    public function test_staff_can_update_moderation_routes_over_http(): void
    {
        $moderator = User::factory()->moderator()->create();
        $editor = User::factory()->editor()->create();
        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $title = Title::factory()->movie()->create();
        $review = Review::factory()->for($author, 'author')->published()->create([
            'title_id' => $title->id,
        ]);
        $report = Report::factory()->for($reporter, 'reporter')->for($review, 'reportable')->create();
        $list = UserList::factory()->for($author)->public()->create();
        $listContribution = Contribution::factory()->for($reporter)->for($list, 'contributable')->create();

        $this->actingAs($moderator)
            ->patch(route('admin.reviews.update', $review), [
                'status' => ReviewStatus::Rejected->value,
                'moderation_notes' => 'Rejected from HTTP moderation route.',
            ])
            ->assertRedirect(route('admin.reviews.index'));

        $this->actingAs($moderator)
            ->patch(route('admin.reports.update', $report), [
                'status' => ReportStatus::Resolved->value,
                'content_action' => 'hide_content',
                'resolution_notes' => 'Resolved from HTTP moderation route.',
                'suspend_owner' => '1',
            ])
            ->assertRedirect(route('admin.reports.index'));

        $this->actingAs($editor)
            ->patch(route('admin.contributions.update', $listContribution), [
                'status' => ContributionStatus::Approved->value,
                'notes' => 'Approved from HTTP route.',
            ])
            ->assertRedirect(route('admin.contributions.index'));

        $review->refresh();
        $report->refresh();
        $listContribution->refresh();
        $author->refresh();

        $this->assertSame(ReviewStatus::Rejected, $review->status);
        $this->assertNull($review->published_at);
        $this->assertSame(ReportStatus::Resolved, $report->status);
        $this->assertSame(UserStatus::Suspended, $author->status);
        $this->assertSame(ContributionStatus::Approved, $listContribution->status);
    }
}
