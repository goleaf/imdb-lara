<?php

namespace Tests\Feature\Feature;

use App\Enums\TitleRelationshipType;
use App\Models\Title;
use App\Models\TitleRelationship;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleMetadataExplorationExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_metadata_page_renders_grouped_keywords_and_connection_cards(): void
    {
        $title = Title::factory()->movie()->create([
            'name' => 'Signal Harbor',
            'slug' => 'signal-harbor',
            'search_keywords' => 'harbor conspiracy, signal breach, polar thriller, cold war mystery, surveillance relay, frozen pipeline, coded distress',
        ]);

        $precedingTitle = Title::factory()->movie()->create([
            'name' => 'Signal Harbor Zero',
            'slug' => 'signal-harbor-zero',
            'plot_outline' => 'The first harbor breach leaves a coded trail under the ice.',
        ]);

        $followingTitle = Title::factory()->movie()->create([
            'name' => 'Signal Harbor Aftermath',
            'slug' => 'signal-harbor-aftermath',
            'plot_outline' => 'The fallout spreads across northern relay stations.',
        ]);

        $similarTitle = Title::factory()->movie()->create([
            'name' => 'Mirror Current',
            'slug' => 'mirror-current',
            'plot_outline' => 'A mirrored data leak ties two submarine crews together.',
        ]);

        TitleRelationship::factory()->create([
            'from_title_id' => $title->id,
            'to_title_id' => $precedingTitle->id,
            'relationship_type' => TitleRelationshipType::Sequel,
            'weight' => 9,
            'notes' => 'Direct continuation of the harbor incident dossier.',
        ]);

        TitleRelationship::factory()->create([
            'from_title_id' => $followingTitle->id,
            'to_title_id' => $title->id,
            'relationship_type' => TitleRelationshipType::Sequel,
            'weight' => 7,
            'notes' => 'Later investigation into the relay collapse.',
        ]);

        TitleRelationship::factory()->create([
            'from_title_id' => $title->id,
            'to_title_id' => $similarTitle->id,
            'relationship_type' => TitleRelationshipType::Similar,
            'weight' => 6,
        ]);

        $this->get(route('public.titles.metadata', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-metadata-hero"')
            ->assertSeeHtml('data-slot="title-keyword-map"')
            ->assertSeeHtml('data-slot="title-connection-map"')
            ->assertSeeHtml('data-slot="connection-card"')
            ->assertSee('Keywords & Connections')
            ->assertSee('Primary Cues')
            ->assertSee('Story Vectors')
            ->assertSee('Follows')
            ->assertSee('Followed By')
            ->assertSee('Similar To')
            ->assertSee('Signal Harbor Zero')
            ->assertSee('Signal Harbor Aftermath')
            ->assertSee('Mirror Current')
            ->assertSee('Harbor Conspiracy')
            ->assertSee('Signal Breach');
    }

    public function test_seeded_title_metadata_route_renders_keywords_and_connections(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $title = Title::query()->where('slug', 'northern-signal')->firstOrFail();

        $this->get(route('public.titles.metadata', $title))
            ->assertOk()
            ->assertSee('Keywords & Connections')
            ->assertSee('Keyword Map')
            ->assertSee('Title Connections')
            ->assertSee('Arctic Mystery')
            ->assertSee('Buried Signal')
            ->assertSee('Afterlight Protocol');
    }
}
