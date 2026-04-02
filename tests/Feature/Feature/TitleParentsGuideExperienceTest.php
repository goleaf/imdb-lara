<?php

namespace Tests\Feature\Feature;

use App\Models\Title;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleParentsGuideExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_parents_guide_page_renders_structured_advisories_votes_and_spoilers(): void
    {
        $title = Title::factory()->movie()->create([
            'name' => 'Neon Harbor',
            'slug' => 'neon-harbor',
            'age_rating' => 'PG-13',
            'is_published' => true,
            'imdb_payload' => [
                'parentsGuide' => [
                    'advisories' => [
                        [
                            'category' => 'violence',
                            'severity' => 'moderate',
                            'text' => 'Dockside fistfights and tense gun standoffs.',
                            'yesVotes' => 18,
                            'noVotes' => 6,
                        ],
                        [
                            'category' => 'language',
                            'severity' => 'mild',
                            'reviews' => [
                                ['text' => 'Occasional strong language in heated scenes.'],
                            ],
                            'voteCount' => 11,
                        ],
                    ],
                    'spoilers' => [
                        'Late reveal about the final shipment.',
                    ],
                ],
                'certificates' => [
                    'certificates' => [
                        [
                            'rating' => 'PG-13',
                            'country' => ['code' => 'US', 'name' => 'United States'],
                            'attributes' => ['violence', 'language'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->get(route('public.titles.parents-guide', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-parents-hero"')
            ->assertSeeHtml('data-slot="title-parent-advisories"')
            ->assertSeeHtml('data-slot="title-parent-certificates"')
            ->assertSeeHtml('data-slot="title-parent-spoilers"')
            ->assertSee('Parents Guide')
            ->assertSee('Content Concerns')
            ->assertSee('Violence')
            ->assertSee('Moderate')
            ->assertSee('18 / 6 split')
            ->assertSee('Language')
            ->assertSee('11 recorded votes')
            ->assertSee('Occasional strong language in heated scenes.')
            ->assertSee('Late reveal about the final shipment.')
            ->assertSee('PG-13')
            ->assertSee('United States');
    }

    public function test_seeded_title_parents_guide_route_renders_the_dedicated_page_shell(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $title = Title::query()->where('slug', 'northern-signal')->firstOrFail();

        $this->get(route('public.titles.parents-guide', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Parents Guide')
            ->assertSeeHtml('data-slot="title-parent-advisories"');
    }
}
