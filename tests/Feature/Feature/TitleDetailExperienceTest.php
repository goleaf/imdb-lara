<?php

namespace Tests\Feature\Feature;

use App\Models\Title;
use App\Models\User;
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
            ->assertSee('Storyline')
            ->assertSee('Cast')
            ->assertSee('Key crew')
            ->assertSee('Media gallery')
            ->assertSee('Open video')
            ->assertSee('Ratings breakdown')
            ->assertSee('Related titles')
            ->assertSee('Awards')
            ->assertSee('Where to watch')
            ->assertSee('Editorial extensions')
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
