<?php

namespace Tests\Unit\Actions\Content;

use App\Actions\Content\LoadChangelogPageAction;
use Illuminate\Support\Facades\File;
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

    public function test_it_prefers_file_based_changelog_entries_when_markdown_files_exist(): void
    {
        $directory = base_path('changelog');
        $newerEntryPath = $directory.'/changelog-2099-01-02-public-release.md';
        $olderEntryPath = $directory.'/changelog-2099-01-01.md';

        File::ensureDirectoryExists($directory);
        File::put($olderEntryPath, implode("\n", [
            '# Quiet maintenance release',
            '',
            'Small polish items landed.',
        ]));
        File::put($newerEntryPath, implode("\n", [
            '# 2099-01-02 — Full Livewire migration for changes',
            '',
            'The changelog now reads from per-day markdown files.',
            '',
            '### What changed',
            'Titles no longer clip in the release stream.',
        ]));

        try {
            $payload = app(LoadChangelogPageAction::class)->handle();
        } finally {
            File::delete([$newerEntryPath, $olderEntryPath]);
        }

        $this->assertSame('Portal Changes', $payload['heroHeadline']);
        $this->assertSame('2099-01-02', $payload['entries'][0]['date']->toDateString());
        $this->assertSame('Full Livewire migration for changes', $payload['entries'][0]['title']);
        $this->assertSame('2099-01-01', $payload['entries'][1]['date']->toDateString());
        $this->assertSame('Quiet maintenance release', $payload['entries'][1]['title']);
        $this->assertStringContainsString('Titles no longer clip', $payload['entries'][0]['html']);
        $this->assertSame('The changelog now reads from per-day markdown files.', $payload['entries'][0]['excerpt']);
    }
}
