<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\LoadTitleBoxOfficeAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Public\TitleBoxOfficePage;
use App\Models\Title;
use Livewire\Livewire;
use Mockery;
use Tests\Concerns\BuildsCatalogTitleFixtures;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleBoxOfficePageLocalRenderTest extends TestCase
{
    use BuildsCatalogTitleFixtures;
    use UsesCatalogOnlyApplication;

    public function test_local_box_office_page_renders_reporting_footprint_without_runtime_errors(): void
    {
        $title = $this->makeCatalogTitle(attributes: [
            'id' => 1,
            'imdb_id' => 'tt0133093',
            'name' => 'The Matrix',
            'original_name' => 'The Matrix',
            'slug' => 'the-matrix-tt0133093',
            'release_year' => 1999,
        ]);

        $loadTitleBoxOffice = Mockery::mock(LoadTitleBoxOfficeAction::class);
        $loadTitleBoxOffice
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn($this->boxOfficePayload($title));

        $this->app->instance(LoadTitleBoxOfficeAction::class, $loadTitleBoxOffice);

        Livewire::test(TitleBoxOfficePage::class, ['title' => $title])
            ->assertSee('Reporting Footprint')
            ->assertSee('The imported box office record currently carries these commercial fields, currencies, and date details for The Matrix.')
            ->assertSee('Lifetime gross reporting')
            ->assertSee('USD 467,222,728')
            ->assertDontSee('movie_box_office')
            ->assertDontSee('Call to a member function getKey() on string');
    }

    /**
     * @return array<string, mixed>
     */
    private function boxOfficePayload(Title $title): array
    {
        return [
            'title' => $title,
            'poster' => null,
            'backdrop' => null,
            'summaryCards' => collect([
                [
                    'key' => 'lifetimeGross',
                    'label' => 'Lifetime Gross',
                    'value' => 'USD 467,222,728',
                    'copy' => 'Worldwide theatrical total carried by the current import.',
                ],
            ]),
            'rankCards' => collect(),
            'comparisonCards' => collect(),
            'heroContextCards' => collect([
                [
                    'key' => 'reportedFields',
                    'label' => 'Imported Fields',
                    'value' => '4',
                    'copy' => 'Commercial details currently attached to the imported box office record.',
                ],
            ]),
            'reportingRows' => collect([
                [
                    'key' => 'lifetimeGross',
                    'label' => 'Lifetime gross reporting',
                    'badge' => 'Worldwide gross',
                    'copy' => 'Structured worldwide gross values are attached to this imported box office record.',
                ],
            ]),
            'reportedFigureCount' => 1,
            'reportedCoverageCount' => 1,
            'spotlightMetric' => [
                'key' => 'lifetimeGross',
                'label' => 'Lifetime Gross',
                'value' => 'USD 467,222,728',
                'copy' => 'Worldwide theatrical total carried by the current import.',
            ],
            'seo' => new PageSeoData(
                title: $title->name.' Box Office Report',
                description: 'Review opening weekend, lifetime gross, budget, and reporting footprint for '.$title->name.'.',
                canonical: route('public.titles.box-office', $title),
            ),
        ];
    }
}
