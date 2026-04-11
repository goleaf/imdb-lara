<?php

namespace Tests\Unit\Actions\Content;

use App\Actions\Content\LoadChangelogPageAction;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class LoadChangelogPageActionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_it_parses_entries_by_heading_boundaries_and_sorts_them_by_date_descending(): void
    {
        $entries = app(LoadChangelogPageAction::class)->parseEntries(implode("\n", [
            '## 2026-03-01 — Older update',
            '',
            'Intro for the older update.',
            '',
            '### What changed',
            'Older body copy.',
            '',
            '---',
            '',
            '## 🗓️ 2026-04-11 — Newer update',
            '',
            'Newest intro paragraph.',
            '',
            '### What changed',
            'Newest body copy.',
        ]));

        $this->assertCount(2, $entries);
        $this->assertSame('Newer update', $entries[0]['title']);
        $this->assertSame('2026-04-11', $entries[0]['date']->toDateString());
        $this->assertSame('Older update', $entries[1]['title']);
        $this->assertSame('2026-03-01', $entries[1]['date']->toDateString());
        $this->assertStringContainsString('Newest intro paragraph.', $entries[0]['html']);
        $this->assertStringContainsString('What changed', $entries[0]['html']);
        $this->assertStringNotContainsString('<hr', $entries[0]['html']);
        $this->assertSame('Newest intro paragraph.', $entries[0]['excerpt']);
    }
}
