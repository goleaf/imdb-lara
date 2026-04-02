<?php

namespace Tests\Feature\Feature;

use App\Models\Title;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleTriviaAndGoofsExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_trivia_and_goofs_page_renders_tabs_cards_spoilers_and_signal_labels(): void
    {
        $title = Title::factory()->movie()->create([
            'name' => 'Red Cipher',
            'slug' => 'red-cipher',
            'is_published' => true,
            'imdb_payload' => [
                'trivia' => [
                    'triviaEntries' => [
                        [
                            'text' => 'The observatory corridor was built in-camera.',
                            'interestCount' => 18,
                            'voteCount' => 12,
                        ],
                        [
                            'spoiler' => [
                                'text' => 'The lighthouse voice is heard in the opening frame.',
                            ],
                            'voteCount' => 6,
                        ],
                    ],
                    'totalCount' => 2,
                ],
                'goofs' => [
                    'goofEntries' => [
                        [
                            'text' => 'A coffee cup switches hands between cuts.',
                            'voteCount' => 7,
                        ],
                        [
                            'text' => 'The snowfall percentage jumps from 4 to 14 overnight.',
                            'isSpoiler' => true,
                            'voteCount' => 2,
                        ],
                    ],
                    'totalCount' => 2,
                ],
            ],
        ]);

        $this->get(route('public.titles.trivia', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-trivia-hero"')
            ->assertSeeHtml('data-slot="title-trivia-tabs"')
            ->assertSeeHtml('data-slot="title-trivia-cards"')
            ->assertSeeHtml('data-slot="title-goof-cards"')
            ->assertSee('Trivia & Goofs')
            ->assertSee('The observatory corridor was built in-camera.')
            ->assertSee('The lighthouse voice is heard in the opening frame.')
            ->assertSee('A coffee cup switches hands between cuts.')
            ->assertSee('The snowfall percentage jumps from 4 to 14 overnight.')
            ->assertSee('Signal 18')
            ->assertSee('Signal 7')
            ->assertSee('Spoiler');
    }

    public function test_seeded_title_trivia_route_renders_the_dedicated_page_shell(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $title = Title::query()->where('slug', 'northern-signal')->firstOrFail();

        $this->get(route('public.titles.trivia', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Trivia & Goofs')
            ->assertSeeHtml('data-slot="title-trivia-tabs"')
            ->assertSeeHtml('data-slot="title-goof-cards"');
    }
}
