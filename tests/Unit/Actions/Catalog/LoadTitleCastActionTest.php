<?php

namespace Tests\Unit\Actions\Catalog;

use App\Actions\Catalog\HydrateTitleCastCatalogAction;
use App\Actions\Catalog\LoadTitleCastAction;
use App\Models\Credit;
use App\Models\NameCreditCharacter;
use App\Models\Person;
use App\Models\Title;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class LoadTitleCastActionTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_it_hydrates_missing_title_cast_rows_before_building_the_public_cast_view_data(): void
    {
        $title = Title::query()->create([
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
        ]);

        $hydrator = Mockery::mock(HydrateTitleCastCatalogAction::class);
        $hydrator
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturnUsing(function (Title $resolvedTitle): Title {
                $person = Person::query()->create([
                    'nconst' => 'nm0000206',
                    'imdb_id' => 'nm0000206',
                    'displayName' => 'Keanu Reeves',
                    'primaryname' => 'Keanu Reeves',
                ]);

                $credit = Credit::query()->create([
                    'movie_id' => $resolvedTitle->id,
                    'name_basic_id' => $person->id,
                    'category' => 'actor',
                    'position' => 1,
                ]);

                NameCreditCharacter::query()->create([
                    'name_credit_id' => $credit->id,
                    'position' => 1,
                    'character_name' => 'Neo',
                ]);

                return $resolvedTitle->fresh();
            });

        $this->app->instance(HydrateTitleCastCatalogAction::class, $hydrator);

        $payload = app(LoadTitleCastAction::class)->handle($title);

        $this->assertSame(1, $payload['castCount']);
        $this->assertSame('Keanu Reeves', $payload['castPageCredits']->first()?->person?->name);
        $this->assertSame('Neo', $payload['castPageCredits']->first()?->character_name);
    }
}
