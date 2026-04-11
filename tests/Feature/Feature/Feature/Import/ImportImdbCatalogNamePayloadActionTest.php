<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Actions\Import\ImportImdbCatalogNamePayloadAction;
use App\Models\NameBasic;
use App\Models\NameCredit;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class ImportImdbCatalogNamePayloadActionTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_sync_filmography_merges_duplicate_unique_name_credit_rows_before_writing(): void
    {
        $person = NameBasic::query()->create([
            'nconst' => 'nm3142672',
            'imdb_id' => 'nm3142672',
            'displayName' => 'Duplicate Actor',
            'primaryname' => 'Duplicate Actor',
        ]);

        $action = app(ImportImdbCatalogNamePayloadAction::class);

        $syncFilmography = \Closure::bind(
            function (NameBasic $person, array $filmographyPayload): void {
                $this->syncFilmography($person, $filmographyPayload);
            },
            $action,
            ImportImdbCatalogNamePayloadAction::class,
        );

        $syncFilmography($person, [
            'credits' => [
                [
                    'title' => [
                        'id' => 'tt31938062',
                        'type' => 'movie',
                        'primaryTitle' => 'Duplicate Credits',
                    ],
                    'category' => 'actor',
                    'episodeCount' => 1,
                    'characters' => ['First Alias'],
                ],
                [
                    'title' => [
                        'id' => 'tt31938062',
                        'type' => 'movie',
                        'primaryTitle' => 'Duplicate Credits',
                    ],
                    'category' => 'actor',
                    'episodeCount' => 2,
                    'characters' => ['Second Alias', 'First Alias'],
                ],
            ],
        ]);

        $credits = NameCredit::query()->where('name_basic_id', $person->getKey())->get();

        $this->assertCount(1, $credits);
        $this->assertSame(2, $credits->first()->episode_count);
        $this->assertSame(1, $credits->first()->position);
        $this->assertSame(
            ['First Alias', 'Second Alias'],
            $credits->first()->nameCreditCharacters()->orderBy('position')->pluck('character_name')->all(),
        );
    }
}
