<?php

namespace Tests\Feature\Feature;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
use App\Models\Title;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleMediaGalleryExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_media_gallery_page_renders_grouped_media_sections(): void
    {
        $title = Title::factory()->movie()->create([
            'name' => 'Neon Harbor',
            'slug' => 'neon-harbor',
            'plot_outline' => 'A midnight exchange on the docks turns into a citywide manhunt.',
        ]);

        MediaAsset::factory()->for($title, 'mediable')->poster()->create([
            'url' => 'https://images.example.test/neon-harbor-poster.jpg',
            'alt_text' => 'Neon Harbor poster',
            'caption' => 'Primary one-sheet',
            'is_primary' => true,
            'position' => 0,
        ]);
        MediaAsset::factory()->for($title, 'mediable')->backdrop()->create([
            'url' => 'https://images.example.test/neon-harbor-backdrop.jpg',
            'alt_text' => 'Neon Harbor backdrop',
            'caption' => 'Harbor skyline at night',
            'is_primary' => true,
            'position' => 1,
        ]);
        MediaAsset::factory()->for($title, 'mediable')->create([
            'kind' => MediaKind::Still,
            'url' => 'https://images.example.test/neon-harbor-still.jpg',
            'alt_text' => 'Neon Harbor still',
            'caption' => 'A midnight exchange on the docks.',
            'position' => 2,
        ]);
        MediaAsset::factory()->for($title, 'mediable')->create([
            'kind' => MediaKind::Gallery,
            'url' => 'https://images.example.test/neon-harbor-gallery.jpg',
            'alt_text' => 'Neon Harbor gallery image',
            'caption' => 'Production still from the harbor tunnel.',
            'position' => 3,
        ]);
        MediaAsset::factory()->for($title, 'mediable')->trailer()->create([
            'kind' => MediaKind::Trailer,
            'url' => 'https://videos.example.test/neon-harbor-trailer',
            'provider' => 'youtube',
            'provider_key' => 'neon-harbor-trailer',
            'caption' => 'Official Trailer',
            'duration_seconds' => 142,
            'position' => 4,
        ]);
        MediaAsset::factory()->for($title, 'mediable')->create([
            'kind' => MediaKind::Featurette,
            'url' => 'https://videos.example.test/neon-harbor-featurette',
            'provider' => 'vimeo',
            'provider_key' => 'neon-harbor-featurette',
            'caption' => 'Behind the Breakwater',
            'duration_seconds' => 184,
            'position' => 5,
            'published_at' => now(),
        ]);

        $this->get(route('public.titles.media', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-media-hero"')
            ->assertSeeHtml('data-slot="title-media-viewer"')
            ->assertSeeHtml('data-slot="title-media-posters"')
            ->assertSeeHtml('data-slot="title-media-stills"')
            ->assertSeeHtml('data-slot="title-media-backdrops"')
            ->assertSeeHtml('data-slot="title-media-trailers"')
            ->assertSee('Neon Harbor Media Gallery')
            ->assertSee('Posters')
            ->assertSee('Stills')
            ->assertSee('Backdrops')
            ->assertSee('Trailers')
            ->assertSee('Primary one-sheet')
            ->assertSee('Harbor skyline at night')
            ->assertSee('A midnight exchange on the docks.')
            ->assertSee('Production still from the harbor tunnel.')
            ->assertSee('Official Trailer')
            ->assertSee('Behind the Breakwater')
            ->assertSee('Watch featured trailer')
            ->assertSee('Open video');
    }

    public function test_seeded_title_media_gallery_route_renders_public_assets(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $title = Title::query()->where('slug', 'northern-signal')->firstOrFail();

        $this->get(route('public.titles.media', $title))
            ->assertOk()
            ->assertSee('Northern Signal')
            ->assertSee('Posters')
            ->assertSee('Backdrops')
            ->assertSee('Trailers')
            ->assertSee('Watch featured trailer');
    }
}
