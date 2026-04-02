<?php

namespace Tests\Feature\Feature;

use App\Enums\MediaKind;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Title;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleDetailExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_page_renders_biography_known_for_awards_trademarks_filmography_and_collaborators(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $person = Person::query()->where('slug', 'ava-mercer')->firstOrFail();

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSeeHtml('data-slot="people-detail-hero"')
            ->assertSeeHtml('data-slot="avatar"')
            ->assertSee($person->name)
            ->assertSee('A. Mercer')
            ->assertSee('Biography')
            ->assertSee('Known for')
            ->assertSee('Awards summary')
            ->assertSee('Trademarks')
            ->assertSee('Filmography')
            ->assertSee('Frequent collaborators')
            ->assertSee('Related titles')
            ->assertSee('Northern Signal')
            ->assertSee('Harbor Nine: The Deep End');
    }

    public function test_person_page_renders_payload_backed_trademarks_and_award_summary(): void
    {
        $person = Person::factory()->create([
            'name' => 'Mira Stone',
            'slug' => 'mira-stone',
            'short_biography' => 'Mira Stone is an actor known for severe genre performances.',
            'imdb_payload' => [
                'details' => [
                    'birthName' => 'Mira Elise Stone',
                    'heightCm' => 173,
                    'meterRanking' => [
                        'difference' => 4,
                        'changeDirection' => 'UP',
                    ],
                    'trademarks' => [
                        ['text' => 'Measured line deliveries and stillness under pressure.'],
                        ['text' => 'A clipped, low-register speaking voice.'],
                    ],
                ],
            ],
        ]);

        $title = Title::factory()->create([
            'name' => 'Signal Margin',
            'slug' => 'signal-margin',
        ]);

        $awardEvent = AwardEvent::factory()->create([
            'name' => '2026 Signal Honors',
            'year' => 2026,
        ]);

        $awardCategory = AwardCategory::factory()->create([
            'award_id' => $awardEvent->award_id,
            'name' => 'Best Lead Performance',
        ]);

        AwardNomination::factory()->forPerson()->winner()->create([
            'award_event_id' => $awardEvent->id,
            'award_category_id' => $awardCategory->id,
            'person_id' => $person->id,
            'title_id' => $title->id,
        ]);

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee('Awards summary')
            ->assertSee('Winner')
            ->assertSee('Best Lead Performance')
            ->assertSee('Trademarks')
            ->assertSee('Measured line deliveries and stillness under pressure.')
            ->assertSee('A clipped, low-register speaking voice.')
            ->assertSee('Birth name')
            ->assertSee('Mira Elise Stone');
    }

    public function test_person_page_renders_headshot_and_portrait_gallery_assets(): void
    {
        $person = Person::factory()->create([
            'name' => 'Mira Stone',
            'slug' => 'mira-stone',
            'short_biography' => 'Mira Stone is an actor known for severe genre performances.',
            'is_published' => true,
        ]);

        MediaAsset::factory()->for($person, 'mediable')->headshot()->create([
            'url' => 'https://images.example.test/mira-stone-headshot.jpg',
            'alt_text' => 'Portrait of Mira Stone',
            'caption' => 'Festival portrait',
            'is_primary' => true,
        ]);
        MediaAsset::factory()->for($person, 'mediable')->create([
            'kind' => MediaKind::Gallery,
            'url' => 'https://images.example.test/mira-stone-gallery.jpg',
            'alt_text' => 'Mira Stone gallery image',
            'caption' => 'Press room still',
        ]);

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSeeHtml('data-slot="avatar"')
            ->assertSee('Portrait gallery')
            ->assertSee('Press room still')
            ->assertSee('https://images.example.test/mira-stone-headshot.jpg', false);
    }

    public function test_person_page_uses_imported_alternate_names_and_short_bio_when_full_biography_is_missing(): void
    {
        $person = Person::factory()->create([
            'name' => 'Ava Mercer',
            'slug' => 'ava-mercer',
            'alternate_names' => null,
            'imdb_alternative_names' => ['A. Mercer', 'Ava L. Mercer'],
            'biography' => null,
            'short_biography' => 'Ava Mercer is a stage and screen performer known for tightly controlled dramatic roles.',
            'is_published' => true,
        ]);

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee('Alternate names')
            ->assertSee('A. Mercer')
            ->assertSee('Ava L. Mercer')
            ->assertSee('This profile currently includes the short public biography.')
            ->assertSee('Ava Mercer is a stage and screen performer known for tightly controlled dramatic roles.');
    }

    public function test_people_browse_page_renders_the_livewire_directory_surface(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $person = Person::query()->where('slug', 'ava-mercer')->firstOrFail();

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSeeHtml('data-slot="avatar"')
            ->assertSeeHtml('data-slot="browse-people-hero"')
            ->assertSee('Browse People')
            ->assertSee('Actors')
            ->assertSee('Ava Mercer')
            ->assertSee('Talia Rowe')
            ->assertSee(route('public.people.show', $person), false);
    }
}
