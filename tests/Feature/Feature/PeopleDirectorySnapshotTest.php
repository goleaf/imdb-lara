<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\GetPeopleDirectorySnapshotAction;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PeopleDirectorySnapshotTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_people_directory_snapshot_action_returns_mysql_backed_metrics(): void
    {
        $snapshot = app(GetPeopleDirectorySnapshotAction::class)->handle();

        $this->assertIsInt($snapshot['publishedPeopleCount']);
        $this->assertGreaterThanOrEqual(0, $snapshot['publishedPeopleCount']);
        $this->assertIsInt($snapshot['awardLinkedPeopleCount']);
        $this->assertGreaterThanOrEqual(0, $snapshot['awardLinkedPeopleCount']);
        $this->assertIsInt($snapshot['creditedPeopleCount']);
        $this->assertGreaterThanOrEqual(0, $snapshot['creditedPeopleCount']);
        $this->assertIsInt($snapshot['professionCount']);
        $this->assertGreaterThanOrEqual(0, $snapshot['professionCount']);
        $this->assertIsArray($snapshot['topProfessions']);
    }

    public function test_people_directory_page_surfaces_the_catalog_snapshot(): void
    {
        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSee('Catalog footprint')
            ->assertSee('Award-linked profiles')
            ->assertSee('Top professions')
            ->assertSeeHtml('data-slot="people-directory-snapshot"');
    }
}
