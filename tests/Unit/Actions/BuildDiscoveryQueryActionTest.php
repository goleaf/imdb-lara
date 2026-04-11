<?php

namespace Tests\Unit\Actions;

use App\Actions\Search\BuildDiscoveryQueryAction;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class BuildDiscoveryQueryActionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_it_uses_a_title_first_search_shape_for_discovery_keywords(): void
    {
        $query = app(BuildDiscoveryQueryAction::class)->handle([
            'search' => 'the matrix',
            'sort' => 'popular',
        ]);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('`primarytitle` = ?', $sql);
        $this->assertStringContainsString('`originaltitle` = ?', $sql);
        $this->assertStringContainsString('`primarytitle` like ?', $sql);
        $this->assertStringContainsString('`originaltitle` like ?', $sql);
        $this->assertContains('the matrix', $bindings);
        $this->assertContains('the matrix%', $bindings);
        $this->assertNotContains('%the matrix%', $bindings);
        $this->assertStringNotContainsString('movie_plots', $sql);
    }
}
