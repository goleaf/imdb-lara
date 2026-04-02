<?php

namespace Tests\Feature\Feature;

use App\Models\Title;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleBoxOfficeExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_box_office_page_renders_metrics_rankings_and_market_breakdowns(): void
    {
        $title = Title::factory()->movie()->create([
            'name' => 'Neon Harbor',
            'slug' => 'neon-harbor',
            'is_published' => true,
            'imdb_payload' => [
                'boxOffice' => [
                    'budget' => ['amount' => '55000000', 'currency' => 'USD'],
                    'openingWeekendGross' => ['amount' => '12345000', 'currency' => 'USD'],
                    'domesticGross' => ['amount' => '40000000', 'currency' => 'USD'],
                    'worldwideGross' => ['amount' => '98000000', 'currency' => 'USD'],
                    'theatricalRuns' => [
                        ['market' => 'United States', 'weeks' => 8],
                        ['market' => 'United Kingdom', 'weeks' => 5],
                    ],
                ],
            ],
        ]);

        Title::factory()->movie()->create([
            'name' => 'Sky Ledger',
            'slug' => 'sky-ledger',
            'is_published' => true,
            'imdb_payload' => [
                'boxOffice' => [
                    'budget' => ['amount' => '120000000', 'currency' => 'USD'],
                    'openingWeekendGross' => ['amount' => '60000000', 'currency' => 'USD'],
                    'domesticGross' => ['amount' => '190000000', 'currency' => 'USD'],
                    'worldwideGross' => ['amount' => '320000000', 'currency' => 'USD'],
                ],
            ],
        ]);

        Title::factory()->movie()->create([
            'name' => 'Quiet Meridian',
            'slug' => 'quiet-meridian',
            'is_published' => true,
            'imdb_payload' => [
                'boxOffice' => [
                    'budget' => ['amount' => '20000000', 'currency' => 'USD'],
                    'openingWeekendGross' => ['amount' => '4000000', 'currency' => 'USD'],
                    'domesticGross' => ['amount' => '18000000', 'currency' => 'USD'],
                    'worldwideGross' => ['amount' => '30000000', 'currency' => 'USD'],
                ],
            ],
        ]);

        $this->get(route('public.titles.box-office', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-box-office-hero"')
            ->assertSeeHtml('data-slot="title-box-office-metrics"')
            ->assertSeeHtml('data-slot="title-box-office-ranks"')
            ->assertSeeHtml('data-slot="title-box-office-comparisons"')
            ->assertSeeHtml('data-slot="title-box-office-markets"')
            ->assertSee('Box Office Report')
            ->assertSee('Opening Weekend')
            ->assertSee('Lifetime Gross')
            ->assertSee('Domestic Gross')
            ->assertSee('Production Budget')
            ->assertSee('USD 12,345,000')
            ->assertSee('USD 98,000,000')
            ->assertSee('USD 40,000,000')
            ->assertSee('USD 55,000,000')
            ->assertSee('Budget Multiple')
            ->assertSee('1.8x')
            ->assertSee('Domestic Share')
            ->assertSee('40.8%')
            ->assertSee('International Gross')
            ->assertSee('USD 58,000,000')
            ->assertSee('#2')
            ->assertSee('Out of 3 tracked USD records for this metric.')
            ->assertSee('United States')
            ->assertSee('8 weeks')
            ->assertSee('United Kingdom')
            ->assertSee('5 weeks');
    }

    public function test_seeded_title_box_office_route_renders_the_dedicated_page_shell(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $title = Title::query()->where('slug', 'northern-signal')->firstOrFail();

        $this->get(route('public.titles.box-office', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Box Office Report')
            ->assertSeeHtml('data-slot="title-box-office-ranks"')
            ->assertSeeHtml('data-slot="title-box-office-markets"');
    }
}
