<?php

namespace Tests\Unit\Actions\Search;

use App\Actions\Search\BuildSearchTitleResultsQueryAction;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class BuildSearchTitleResultsQueryActionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_it_targets_the_remote_movies_catalog_shape_in_catalog_only_mode(): void
    {
        $query = app(BuildSearchTitleResultsQueryAction::class)->handle([
            'search' => 'shrek',
            'sort' => 'popular',
        ]);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertMatchesRegularExpression('/from [`"]movies[`"]/', $sql);
        $this->assertStringContainsString('movie_ratings', $sql);
        $this->assertStringContainsString('primarytitle', $sql);
        $this->assertStringContainsString('originaltitle', $sql);
        $this->assertStringNotContainsString('title_statistics', $sql);
        $this->assertStringNotContainsString('deleted_at', $sql);
        $this->assertContains('%shrek%', $bindings);
    }
}
