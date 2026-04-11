<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Actions\Catalog\GetPeopleDirectorySnapshotAction;
use App\Actions\Catalog\GetPublicPeopleFilterOptionsAction;
use App\Models\AwardNomination;
use App\Models\Credit;
use App\Models\Person;
use App\Models\Profession;
use Illuminate\Support\Collection;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PeopleDirectorySnapshotTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_people_directory_snapshot_action_returns_local_metrics(): void
    {
        $actor = Profession::query()->create(['id' => 1, 'name' => 'actor']);
        $director = Profession::query()->create(['id' => 2, 'name' => 'director']);

        $keanu = Person::query()->create([
            'id' => 1,
            'nconst' => 'nm0000206',
            'imdb_id' => 'nm0000206',
            'primaryname' => 'Keanu Reeves',
            'displayName' => 'Keanu Reeves',
        ]);

        $lana = Person::query()->create([
            'id' => 2,
            'nconst' => 'nm0905154',
            'imdb_id' => 'nm0905154',
            'primaryname' => 'Lana Wachowski',
            'displayName' => 'Lana Wachowski',
        ]);

        $keanu->professionTerms()->attach($actor->id, ['position' => 1]);
        $lana->professionTerms()->attach($director->id, ['position' => 1]);

        Credit::query()->create([
            'name_basic_id' => $keanu->id,
            'movie_id' => 1,
            'category' => 'actor',
            'position' => 1,
        ]);

        $nomination = AwardNomination::query()->create([
            'id' => 1,
            'movie_id' => 1,
            'event_imdb_id' => 'ev0000003',
            'award_category_id' => 1,
            'award_year' => 2000,
            'text' => 'Best Direction',
            'is_winner' => true,
            'position' => 1,
        ]);

        $lana->awardNominations()->attach($nomination->id, ['position' => 1]);

        $snapshot = app(GetPeopleDirectorySnapshotAction::class)->handle();

        $this->assertSame(2, $snapshot['publishedPeopleCount']);
        $this->assertSame(1, $snapshot['awardLinkedPeopleCount']);
        $this->assertSame(1, $snapshot['creditedPeopleCount']);
        $this->assertSame(2, $snapshot['professionCount']);
        $this->assertSame([
            ['name' => 'actor', 'peopleCount' => 1],
            ['name' => 'director', 'peopleCount' => 1],
        ], $snapshot['topProfessions']);
    }

    public function test_people_directory_page_surfaces_the_catalog_snapshot_without_remote_queries(): void
    {
        $snapshot = [
            'publishedPeopleCount' => 2,
            'awardLinkedPeopleCount' => 1,
            'creditedPeopleCount' => 1,
            'professionCount' => 2,
            'topProfessions' => [
                ['name' => 'actor', 'peopleCount' => 1],
                ['name' => 'director', 'peopleCount' => 1],
            ],
        ];

        $getPeopleDirectorySnapshot = Mockery::mock(GetPeopleDirectorySnapshotAction::class);
        $getPeopleDirectorySnapshot
            ->shouldReceive('handle')
            ->once()
            ->andReturn($snapshot);

        $buildPublicPeopleIndexQuery = Mockery::mock(BuildPublicPeopleIndexQueryAction::class);
        $buildPublicPeopleIndexQuery
            ->shouldReceive('handle')
            ->once()
            ->with([
                'search' => '',
                'profession' => null,
                'sort' => 'popular',
            ])
            ->andReturn(
                Person::query()
                    ->selectDirectoryColumns()
                    ->whereKey(-1)
            );

        $getPublicPeopleFilterOptions = Mockery::mock(GetPublicPeopleFilterOptionsAction::class);
        $getPublicPeopleFilterOptions
            ->shouldReceive('handle')
            ->once()
            ->andReturn([
                'professions' => collect(),
                'sortOptions' => Collection::make([
                    ['value' => 'popular', 'label' => 'Most popular'],
                    ['value' => 'name', 'label' => 'Alphabetical'],
                    ['value' => 'credits', 'label' => 'Most credits'],
                    ['value' => 'awards', 'label' => 'Most awards'],
                ]),
            ]);

        $this->app->instance(GetPeopleDirectorySnapshotAction::class, $getPeopleDirectorySnapshot);
        $this->app->instance(BuildPublicPeopleIndexQueryAction::class, $buildPublicPeopleIndexQuery);
        $this->app->instance(GetPublicPeopleFilterOptionsAction::class, $getPublicPeopleFilterOptions);

        $this->get(route('public.people.index'))
            ->assertOk()
            ->assertSee('Catalog footprint')
            ->assertSee('Award-linked profiles')
            ->assertSee('Top professions')
            ->assertSee('actor · 1')
            ->assertSeeHtml('data-slot="people-directory-snapshot"');
    }
}
