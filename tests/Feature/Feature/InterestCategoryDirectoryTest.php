<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\GetInterestCategoryDirectorySnapshotAction;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class InterestCategoryDirectoryTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_interest_category_directory_snapshot_action_returns_mysql_backed_metrics(): void
    {
        $snapshot = app(GetInterestCategoryDirectorySnapshotAction::class)->handle();

        $this->assertIsInt($snapshot['categoryCount']);
        $this->assertGreaterThanOrEqual(0, $snapshot['categoryCount']);
        $this->assertIsInt($snapshot['interestCount']);
        $this->assertGreaterThanOrEqual(0, $snapshot['interestCount']);
        $this->assertIsInt($snapshot['titleLinkedInterestCount']);
        $this->assertGreaterThanOrEqual(0, $snapshot['titleLinkedInterestCount']);
        $this->assertIsArray($snapshot['topCategories']);
    }

    public function test_interest_category_directory_page_surfaces_the_catalog_snapshot_and_browser(): void
    {
        Livewire::withoutLazyLoading();

        $this->get(route('public.interest-categories.index'))
            ->assertOk()
            ->assertSee('Interest Categories')
            ->assertSee('Catalog clusters')
            ->assertSee('Top category lanes')
            ->assertSeeHtml('data-slot="interest-category-directory-snapshot"')
            ->assertSeeHtml('data-slot="interest-category-browser-island"');
    }

    public function test_interest_category_detail_page_surfaces_related_interests_and_titles(): void
    {
        $category = $this->sampleInterestCategory();

        $this->get(route('public.interest-categories.show', $category))
            ->assertOk()
            ->assertSee($category->name)
            ->assertSee('Category overview')
            ->assertSee('Related interests')
            ->assertSee('Linked titles')
            ->assertSee(route('public.titles.index', ['theme' => $category->slug]), false)
            ->assertSeeHtml('data-slot="interest-category-detail-hero"')
            ->assertSeeHtml('data-slot="interest-category-summary-panels"');
    }
}
