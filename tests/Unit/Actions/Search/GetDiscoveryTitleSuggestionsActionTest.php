<?php

namespace Tests\Unit\Actions\Search;

use App\Actions\Search\GetDiscoveryTitleSuggestionsAction;
use App\Models\Title;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class GetDiscoveryTitleSuggestionsActionTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_it_returns_lightweight_suggestions_without_catalog_card_relations(): void
    {
        Title::query()->create([
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
        ]);

        $suggestion = app(GetDiscoveryTitleSuggestionsAction::class)
            ->handle('the')
            ->sole();

        $this->assertFalse($suggestion->relationLoaded('statistic'));
        $this->assertFalse($suggestion->relationLoaded('genres'));
        $this->assertFalse($suggestion->relationLoaded('countries'));
        $this->assertFalse($suggestion->relationLoaded('languages'));
        $this->assertFalse($suggestion->relationLoaded('plotRecord'));
    }
}
