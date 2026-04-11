<?php

namespace Tests\Feature\Feature;

use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleBoxOfficeExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_box_office_page_renders_the_remote_catalog_report_shell(): void
    {
        $title = $this->sampleTitleWithReportedBoxOfficeFigures();

        $this->get(route('public.titles.box-office', $title))
            ->assertOk()
            ->assertSee($title->name)
            ->assertSee('Box Office Report')
            ->assertSeeHtml('data-slot="title-box-office-hero"')
            ->assertSeeHtml('data-slot="title-box-office-metrics"')
            ->assertSeeHtml('data-slot="title-box-office-ranks"')
            ->assertSeeHtml('data-slot="title-box-office-comparisons"')
            ->assertSeeHtml('data-slot="title-box-office-markets"')
            ->assertSeeHtml('text-[#f4eee5] decoration-white/20 hover:text-white')
            ->assertSee('Revenue Dashboard')
            ->assertSee('Ranked Positions')
            ->assertSee('Reporting Footprint')
            ->assertSee('Figure coverage')
            ->assertDontSee('Reporting status')
            ->assertDontSee('Comparison availability')
            ->assertDontSee('Pending')
            ->assertDontSee('Waiting');
    }

    public function test_title_detail_page_links_to_the_box_office_route(): void
    {
        $title = $this->sampleTitleWithBoxOffice();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee(route('public.titles.box-office', $title), false)
            ->assertSee('Box Office');
    }
}
