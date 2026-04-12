<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\ContributionStatus;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserStatus;
use App\Livewire\Admin\ContributionModerationCard;
use App\Livewire\Admin\ReportModerationCard;
use App\Livewire\Admin\ReviewModerationCard;
use App\Livewire\Pages\Admin\MediaAssetEditPage;
use App\Livewire\Pages\Admin\PersonEditPage;
use App\Livewire\Pages\Admin\TitleEditPage;
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
use Livewire\Livewire;
use Tests\TestCase;

class MediaAssetUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_media_and_moderation_controller_routes_are_not_registered(): void
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
            $this->assertFalse(Route::has($routeName), $routeName.' should not be registered on the Livewire-only admin surface.');
        }
    }

    public function test_editor_can_upload_replace_and_delete_title_media_assets_and_public_title_page_uses_latest_primary(): void
    {
        Storage::fake('public');

        $editor = User::factory()->editor()->create();
        $title = Title::factory()->create();
        $existingPrimaryPoster = MediaAsset::factory()->poster()->for($title, 'mediable')->create();

        $this->actingAs($editor);

        Livewire::test(TitleEditPage::class, ['title' => $title])
            ->set('draftMediaAsset.kind', 'poster')
            ->set('draftMediaAsset.file', UploadedFile::fake()->image('poster-one.jpg', 600, 900))
            ->set('draftMediaAsset.alt_text', 'Poster one')
            ->set('draftMediaAsset.caption', 'Primary poster')
            ->set('draftMediaAsset.language', 'en')
            ->set('draftMediaAsset.is_primary', true)
            ->set('draftMediaAsset.position', 0)
            ->set('draftMediaAsset.published_at', now()->format('Y-m-d\\TH:i'))
            ->call('saveDraftMediaAsset');

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

        $editMediaPage = Livewire::test(MediaAssetEditPage::class, ['mediaAsset' => $uploadedPoster])
            ->set('kind', 'poster')
            ->set('file', UploadedFile::fake()->image('poster-two.jpg', 800, 1200))
            ->set('alt_text', 'Poster two')
            ->set('caption', 'Updated poster')
            ->set('language', 'en')
            ->set('is_primary', true)
            ->set('position', 1)
            ->set('published_at', now()->format('Y-m-d\\TH:i'))
            ->call('saveMediaAsset');

        $uploadedPoster->refresh();

        $editMediaPage->assertRedirect(route('admin.media-assets.edit', $uploadedPoster));
        $this->assertNotSame($previousUploadPath, $uploadedPoster->storagePath());
        Storage::disk('public')->assertMissing($previousUploadPath);
        Storage::disk('public')->assertExists($uploadedPoster->storagePath());

        Livewire::test(MediaAssetEditPage::class, ['mediaAsset' => $uploadedPoster])
            ->call('deleteMediaAsset')
            ->assertRedirect(route('admin.titles.edit', $title));

        $this->assertSoftDeleted('media_assets', ['id' => $uploadedPoster->id]);
        Storage::disk('public')->assertMissing($uploadedPoster->storagePath());
    }

    public function test_editor_can_upload_person_headshots_and_validation_reject_invalid_media_sources_or_kinds(): void
    {
        Storage::fake('public');

        $editor = User::factory()->editor()->create();
        $person = Person::factory()->create();
        $title = Title::factory()->create();

        $this->actingAs($editor);

        Livewire::test(PersonEditPage::class, ['person' => $person])
            ->set('draftMediaAsset.kind', 'headshot')
            ->set('draftMediaAsset.file', UploadedFile::fake()->image('headshot.jpg', 500, 700))
            ->set('draftMediaAsset.alt_text', 'Ava Mercer headshot')
            ->set('draftMediaAsset.caption', 'Portrait')
            ->set('draftMediaAsset.language', 'en')
            ->set('draftMediaAsset.is_primary', true)
            ->set('draftMediaAsset.position', 0)
            ->call('saveDraftMediaAsset');

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

        Livewire::test(TitleEditPage::class, ['title' => $title])
            ->set('draftMediaAsset.kind', 'trailer')
            ->set('draftMediaAsset.file', UploadedFile::fake()->image('not-a-video.jpg'))
            ->set('draftMediaAsset.is_primary', false)
            ->set('draftMediaAsset.position', 0)
            ->call('saveDraftMediaAsset')
            ->assertHasErrors(['file', 'url']);

        Livewire::test(PersonEditPage::class, ['person' => $person])
            ->set('draftMediaAsset.kind', 'poster')
            ->set('draftMediaAsset.url', 'https://videos.example.test/person-poster')
            ->set('draftMediaAsset.provider', 'external')
            ->set('draftMediaAsset.provider_key', 'person-poster')
            ->set('draftMediaAsset.is_primary', false)
            ->set('draftMediaAsset.position', 0)
            ->call('saveDraftMediaAsset')
            ->assertHasErrors(['kind']);
    }

    public function test_staff_can_update_moderation_cards_over_livewire(): void
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

        $this->actingAs($moderator);

        Livewire::test(ReviewModerationCard::class, ['review' => $review])
            ->set('status', ReviewStatus::Rejected->value)
            ->set('moderationNotes', 'Rejected from Livewire moderation card.')
            ->call('save');

        Livewire::test(ReportModerationCard::class, ['report' => $report])
            ->set('status', ReportStatus::Resolved->value)
            ->set('contentAction', 'hide_content')
            ->set('resolutionNotes', 'Resolved from Livewire moderation card.')
            ->set('suspendOwner', true)
            ->call('save');

        $this->actingAs($editor);

        Livewire::test(ContributionModerationCard::class, ['contribution' => $listContribution])
            ->set('status', ContributionStatus::Approved->value)
            ->set('notes', 'Approved from Livewire moderation card.')
            ->call('save');

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
