<?php

namespace Tests\Feature\Feature;

use App\Enums\MediaKind;
use App\Enums\WatchState;
use App\Models\ListItem;
use App\Models\MediaAsset;
use App\Models\Rating;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleDetailExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_page_renders_the_full_public_detail_experience(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $title = Title::query()->where('slug', 'northern-signal')->firstOrFail();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-hero"')
            ->assertSee('Deep discovery map')
            ->assertSee('Storyline')
            ->assertSee('Cast')
            ->assertSee('Key crew')
            ->assertSee('Quick facts')
            ->assertSee('Keywords')
            ->assertSee('Release dates')
            ->assertSee('Parents guide')
            ->assertSee('Open full guide')
            ->assertSee('Open trivia dossier')
            ->assertSee('Trivia')
            ->assertSee('Goofs')
            ->assertSee('Technical specs')
            ->assertSee('Box office')
            ->assertSee('Open full report')
            ->assertSee('Media gallery')
            ->assertSee('Open video')
            ->assertSee('Ratings breakdown')
            ->assertSee('Related titles')
            ->assertSee('Open metadata map')
            ->assertSee('Awards')
            ->assertSee('Where to watch')
            ->assertSee('Future-ready')
            ->assertSee('Reserved for future import support')
            ->assertSee('Afterlight Protocol')
            ->assertSee('Celestial Screen Awards');
    }

    public function test_series_title_page_renders_tv_specific_sections_without_affecting_movie_pages(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $series = Title::query()->where('slug', 'static-bloom')->firstOrFail();
        $movie = Title::query()->where('slug', 'northern-signal')->firstOrFail();

        $this->get(route('public.titles.show', $series))
            ->assertOk()
            ->assertSeeHtml('data-slot="series-guide-navigation"')
            ->assertSee('Season navigation')
            ->assertSee('Latest season overview')
            ->assertSee('Top-rated episodes')
            ->assertSee('Static Bloom: Signal Path');

        $this->get(route('public.titles.show', $movie))
            ->assertOk()
            ->assertDontSee('Latest season overview')
            ->assertDontSee('Top-rated episodes');
    }

    public function test_title_page_only_shows_the_edit_link_to_authorized_roles(): void
    {
        $title = Title::factory()->create();
        $editor = User::factory()->editor()->create();
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertDontSee('Edit title');

        $this->actingAs($editor)
            ->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('Edit title');
    }

    public function test_authenticated_title_page_load_reflects_personal_rating_tracking_and_custom_list_state(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create([
            'name' => 'Neon Harbor',
            'slug' => 'neon-harbor',
            'is_published' => true,
        ]);

        Rating::factory()->for($user)->for($title)->create([
            'score' => 8,
        ]);

        $watchlist = UserList::factory()->for($user)->watchlist()->create();
        ListItem::factory()->for($watchlist, 'userList')->for($title)->completed()->create();

        $customList = UserList::factory()->for($user)->create([
            'name' => 'Dockside Futures',
        ]);
        ListItem::factory()->for($customList, 'userList')->for($title)->create([
            'watch_state' => WatchState::Completed,
        ]);

        $this->actingAs($user)
            ->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('Saved as 8/10')
            ->assertSee('This title is already in your private watchlist.')
            ->assertSee('Completed')
            ->assertSee('Already saved in your lists')
            ->assertSee('Dockside Futures');
    }

    public function test_title_page_renders_payload_backed_release_guide_and_box_office_sections(): void
    {
        $title = Title::factory()->create([
            'name' => 'Neon Harbor',
            'search_keywords' => 'neon conspiracy, harbor noir',
            'age_rating' => 'R',
            'release_date' => '2024-04-01',
            'imdb_payload' => [
                'releaseDates' => [
                    'releaseDates' => [
                        [
                            'country' => ['code' => 'US', 'name' => 'United States'],
                            'releaseDate' => ['year' => 2024, 'month' => 4, 'day' => 1],
                        ],
                        [
                            'country' => ['code' => 'GB', 'name' => 'United Kingdom'],
                            'releaseDate' => ['year' => 2024, 'month' => 4, 'day' => 12],
                        ],
                    ],
                ],
                'parentsGuide' => [
                    'advisories' => [
                        [
                            'category' => 'violence',
                            'severity' => 'moderate',
                            'text' => 'Dockside fistfights and tense gun standoffs.',
                        ],
                        [
                            'category' => 'language',
                            'severity' => 'mild',
                            'reviews' => [
                                ['text' => 'Occasional strong language in heated scenes.'],
                            ],
                        ],
                    ],
                    'spoilers' => [
                        'Final act hostage reveal.',
                    ],
                ],
                'trivia' => [
                    'triviaEntries' => [
                        [
                            'text' => 'The dock siren was recorded from a retired ferry horn.',
                            'interestCount' => 14,
                        ],
                        [
                            'spoiler' => [
                                'text' => 'The crate number matches the ending coordinates.',
                            ],
                            'voteCount' => 4,
                        ],
                    ],
                ],
                'goofs' => [
                    'goofEntries' => [
                        [
                            'text' => 'A glass refills between reverse shots.',
                            'voteCount' => 7,
                        ],
                    ],
                ],
                'certificates' => [
                    'certificates' => [
                        [
                            'rating' => 'R',
                            'country' => ['code' => 'US', 'name' => 'United States'],
                            'attributes' => ['violence', 'language'],
                        ],
                    ],
                ],
                'boxOffice' => [
                    'budget' => ['amount' => '55000000', 'currency' => 'USD'],
                    'openingWeekendGross' => ['amount' => '12345000', 'currency' => 'USD'],
                    'worldwideGross' => ['amount' => '98000000', 'currency' => 'USD'],
                ],
            ],
        ]);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('Neon Conspiracy')
            ->assertSee('Harbor Noir')
            ->assertSee('United States')
            ->assertSee('United Kingdom')
            ->assertSee('Apr 1, 2024')
            ->assertSee('Apr 12, 2024')
            ->assertSee('Violence')
            ->assertSee('Moderate')
            ->assertSee('Dockside fistfights and tense gun standoffs.')
            ->assertSee('Occasional strong language in heated scenes.')
            ->assertSee('Final act hostage reveal.')
            ->assertSee('The dock siren was recorded from a retired ferry horn.')
            ->assertSee('The crate number matches the ending coordinates.')
            ->assertSee('A glass refills between reverse shots.')
            ->assertSee('USD 55,000,000')
            ->assertSee('USD 98,000,000');
    }

    public function test_title_page_renders_gallery_and_video_previews_from_attached_media_assets(): void
    {
        $title = Title::factory()->movie()->create([
            'name' => 'Neon Harbor',
            'slug' => 'neon-harbor',
            'plot_outline' => 'A midnight exchange on the docks turns into a citywide manhunt.',
            'is_published' => true,
        ]);

        MediaAsset::factory()->for($title, 'mediable')->poster()->create([
            'url' => 'https://images.example.test/neon-harbor-poster.jpg',
            'alt_text' => 'Neon Harbor poster',
            'caption' => 'Primary one-sheet',
            'is_primary' => true,
        ]);
        MediaAsset::factory()->for($title, 'mediable')->backdrop()->create([
            'url' => 'https://images.example.test/neon-harbor-backdrop.jpg',
            'alt_text' => 'Neon Harbor backdrop',
            'caption' => 'Harbor skyline at night',
            'is_primary' => true,
        ]);
        MediaAsset::factory()->for($title, 'mediable')->create([
            'kind' => MediaKind::Gallery,
            'url' => 'https://images.example.test/neon-harbor-gallery.jpg',
            'alt_text' => 'Neon Harbor gallery image',
            'caption' => 'Production still from the harbor tunnel.',
        ]);
        MediaAsset::factory()->for($title, 'mediable')->trailer()->create([
            'url' => 'https://videos.example.test/neon-harbor-trailer',
            'provider' => 'youtube',
            'provider_key' => 'neon-harbor-trailer',
            'caption' => 'Official Trailer',
            'duration_seconds' => 142,
        ]);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('Media gallery')
            ->assertSee('Primary one-sheet')
            ->assertSee('Harbor skyline at night')
            ->assertSee('Production still from the harbor tunnel.')
            ->assertSee('Official Trailer')
            ->assertSee('Open video');
    }

    public function test_authorized_editors_can_update_title_metadata_from_the_admin_edit_screen(): void
    {
        $title = Title::factory()->create([
            'name' => 'Old Title',
            'plot_outline' => 'Old outline.',
        ]);

        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->patch(route('admin.titles.update', $title), [
                'name' => 'Refined Title',
                'original_name' => 'Refined Title Original',
                'release_year' => 2024,
                'end_year' => null,
                'release_date' => '2024-02-01',
                'runtime_minutes' => 118,
                'age_rating' => 'PG-13',
                'plot_outline' => 'A refined outline for the title page.',
                'synopsis' => 'Expanded synopsis copy for editorial testing.',
                'tagline' => 'The signal sharpens.',
                'origin_country' => 'US',
                'original_language' => 'en',
                'meta_title' => 'Refined Title | Screenbase',
                'meta_description' => 'Refined metadata description.',
                'search_keywords' => 'refined, title, screenbase',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.titles.edit', $title));

        $this->assertDatabaseHas('titles', [
            'id' => $title->id,
            'name' => 'Refined Title',
            'plot_outline' => 'A refined outline for the title page.',
            'is_published' => 1,
        ]);
    }
}
