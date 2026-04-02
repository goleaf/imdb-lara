<?php

namespace Tests\Feature\Feature;

use App\Models\Award;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Person;
use App\Models\Title;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardsArchiveExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_awards_archive_page_renders_event_category_and_linked_honorees(): void
    {
        $award = Award::factory()->create([
            'name' => 'Aurora Guild Awards',
            'slug' => 'aurora-guild-awards',
            'description' => 'An annual guild archive for contemporary screen work.',
        ]);

        $event = AwardEvent::factory()->for($award)->create([
            'name' => '2024 Aurora Guild Awards',
            'slug' => '2024-aurora-guild-awards',
            'year' => 2024,
            'location' => 'Toronto',
        ]);

        $bestPicture = AwardCategory::factory()->for($award)->create([
            'name' => 'Best Picture',
            'slug' => 'best-picture',
            'recipient_scope' => 'title',
        ]);

        $bestLead = AwardCategory::factory()->for($award)->create([
            'name' => 'Best Lead Performance',
            'slug' => 'best-lead-performance',
            'recipient_scope' => 'person',
        ]);

        $title = Title::factory()->movie()->create([
            'name' => 'Glass Harbor',
            'slug' => 'glass-harbor',
            'release_year' => 2024,
        ]);

        $person = Person::factory()->create([
            'name' => 'Ava Stone',
            'slug' => 'ava-stone',
            'known_for_department' => 'Acting',
        ]);

        AwardNomination::factory()->for($event)->for($bestPicture, 'awardCategory')->winner()->create([
            'title_id' => $title->id,
            'sort_order' => 1,
        ]);

        AwardNomination::factory()->for($event)->for($bestLead, 'awardCategory')->forPerson()->create([
            'person_id' => $person->id,
            'sort_order' => 2,
        ]);

        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Awards Archive')
            ->assertSeeHtml('data-slot="awards-archive-hero"')
            ->assertSeeHtml('data-slot="awards-archive-shell"')
            ->assertSeeHtml('data-slot="awards-timeline"')
            ->assertSeeHtml('data-slot="award-event-marker"')
            ->assertSeeHtml('data-slot="award-event-card"')
            ->assertSee('2024 Aurora Guild Awards')
            ->assertSee('Best Picture')
            ->assertSee('Best Lead Performance')
            ->assertSee('Winner')
            ->assertSee('Nominee')
            ->assertSee('Glass Harbor')
            ->assertSee('Ava Stone')
            ->assertSee('Toronto');
    }

    public function test_seeded_awards_archive_route_renders_demo_catalog_records(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Awards Archive')
            ->assertSee('Ceremony')
            ->assertSee('2025 Celestial Screen Awards')
            ->assertSee('Best Picture')
            ->assertSee('Best Lead Performance')
            ->assertSee('Best Episode')
            ->assertSee('Northern Signal')
            ->assertSee('Ava Mercer')
            ->assertSee('Static Bloom: Pilot');
    }
}
