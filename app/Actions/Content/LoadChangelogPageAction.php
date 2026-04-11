<?php

namespace App\Actions\Content;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LoadChangelogPageAction
{
    /**
     * @return array{
     *     entries: list<array{
     *         date: Carbon,
     *         excerpt: string|null,
     *         html: string,
     *         id: string,
     *         title: string
     *     }>,
     *     entryCount: int,
     *     estimatedReadMinutes: int,
     *     heroHeadline: string,
     *     heroSummary: string,
     *     updatedAt: Carbon,
     *     wordCount: int
     * }
     */
    public function handle(): array
    {
        [$sourcePath, $markdown] = $this->resolveSource();
        $entries = $this->parseEntries($markdown);
        $newestEntry = $entries->first();
        $plainText = Str::of(
            $entries
                ->map(fn (array $entry): string => strip_tags($entry['html']))
                ->implode(' ')
        )->squish()->toString();
        $wordCount = str_word_count($plainText);

        return [
            'entries' => $entries->all(),
            'entryCount' => $entries->count(),
            'estimatedReadMinutes' => max(1, (int) ceil(max($wordCount, 1) / 220)),
            'heroHeadline' => 'Screenbase Changelog',
            'heroSummary' => $newestEntry['excerpt']
                ?? 'Release notes are rendered from the repository changelog and published as a newest-first editorial stream.',
            'updatedAt' => $newestEntry['date']
                ?? ($sourcePath !== null
                    ? Carbon::createFromTimestamp(File::lastModified($sourcePath))
                    : now()),
            'wordCount' => $wordCount,
        ];
    }

    /**
     * @return Collection<int, array{
     *     date: Carbon,
     *     excerpt: string|null,
     *     html: string,
     *     id: string,
     *     title: string
     * }>
     */
    public function parseEntries(string $markdown): Collection
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($markdown)) ?: [];
        $entries = [];
        $currentEntry = null;

        foreach ($lines as $line) {
            $heading = $this->parseEntryHeading($line);

            if ($heading !== null) {
                if ($currentEntry !== null) {
                    $entries[] = $this->buildEntry($currentEntry);
                }

                $currentEntry = [
                    'date' => $heading['date'],
                    'title' => $heading['title'],
                    'lines' => [],
                ];

                continue;
            }

            if ($currentEntry !== null) {
                $currentEntry['lines'][] = $line;
            }
        }

        if ($currentEntry !== null) {
            $entries[] = $this->buildEntry($currentEntry);
        }

        if ($entries === []) {
            $bodyMarkdown = trim($markdown);
            $html = $this->injectHeadingAnchors(
                Str::markdown($bodyMarkdown, $this->markdownOptions())
            );

            return collect([[
                'date' => now(),
                'excerpt' => $this->extractLeadParagraph($bodyMarkdown),
                'html' => $html,
                'id' => 'release-notes',
                'title' => $this->extractFirstHeading($markdown, 1) ?? 'Release notes',
            ]]);
        }

        return collect($entries)
            ->sortByDesc(fn (array $entry): int => $entry['date']->getTimestamp())
            ->values();
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function resolveSource(): array
    {
        foreach (['changelog.md', 'CHANGELOG.md'] as $candidate) {
            $path = base_path($candidate);

            if (File::exists($path)) {
                return [$path, trim(File::get($path))];
            }
        }

        return [
            null,
            implode("\n\n", [
                '## Release notes are not available yet',
                'Create `changelog.md` in the project root to publish the first Screenbase update entry.',
            ]),
        ];
    }

    private function extractFirstHeading(string $markdown, int $level): ?string
    {
        preg_match('/^'.preg_quote(str_repeat('#', $level), '/').'\s+(.+)$/m', $markdown, $matches);

        if (! isset($matches[1])) {
            return null;
        }

        return trim($matches[1]);
    }

    private function extractLeadParagraph(string $markdown): ?string
    {
        $preferredSectionParagraph = $this->extractParagraphAfterHeading($markdown, "### What's New");

        if ($preferredSectionParagraph !== null) {
            return $preferredSectionParagraph;
        }

        $lines = preg_split("/\r\n|\n|\r/", $markdown) ?: [];
        $paragraphLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '') {
                if ($paragraphLines !== []) {
                    break;
                }

                continue;
            }

            if (Str::startsWith($trimmedLine, ['#', '-', '*', '>', '`'])) {
                if ($paragraphLines !== []) {
                    break;
                }

                continue;
            }

            $paragraphLines[] = $trimmedLine;
        }

        if ($paragraphLines === []) {
            return null;
        }

        return implode(' ', $paragraphLines);
    }

    private function extractParagraphAfterHeading(string $markdown, string $heading): ?string
    {
        $lines = preg_split("/\r\n|\n|\r/", $markdown) ?: [];
        $collectParagraph = false;
        $paragraphLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === $heading) {
                $collectParagraph = true;
                $paragraphLines = [];

                continue;
            }

            if (! $collectParagraph) {
                continue;
            }

            if ($trimmedLine === '') {
                if ($paragraphLines !== []) {
                    break;
                }

                continue;
            }

            if (Str::startsWith($trimmedLine, ['#', '-', '*', '>', '`'])) {
                if ($paragraphLines !== []) {
                    break;
                }

                continue;
            }

            $paragraphLines[] = $trimmedLine;
        }

        if ($paragraphLines === []) {
            return null;
        }

        return implode(' ', $paragraphLines);
    }

    /**
     * @param  array{date: Carbon, lines: list<string>, title: string}  $entry
     * @return array{
     *     date: Carbon,
     *     excerpt: string|null,
     *     html: string,
     *     id: string,
     *     title: string
     * }
     */
    private function buildEntry(array $entry): array
    {
        $bodyMarkdown = trim(implode("\n", $entry['lines']));
        $bodyMarkdown = preg_replace('/(?:\n|\A)---\s*$/', '', $bodyMarkdown) ?? $bodyMarkdown;
        $bodyMarkdown = trim($bodyMarkdown);
        $html = $this->injectHeadingAnchors(Str::markdown($bodyMarkdown, $this->markdownOptions()));

        return [
            'date' => $entry['date'],
            'excerpt' => $this->extractLeadParagraph($bodyMarkdown),
            'html' => $html,
            'id' => Str::slug($entry['date']->toDateString().'-'.$entry['title']),
            'title' => $entry['title'],
        ];
    }

    /**
     * @return array{date: Carbon, title: string}|null
     */
    private function parseEntryHeading(string $line): ?array
    {
        $normalizedLine = trim($line);

        if (! Str::startsWith($normalizedLine, '## ')) {
            return null;
        }

        $headingText = trim(Str::after($normalizedLine, '## '));

        if (! preg_match('/^(?:.*?)(?<date>\d{4}-\d{2}-\d{2})(?:\s*[—–-]\s*(?<title>.+))?$/u', $headingText, $matches)) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', (string) $matches['date']);

        if ($date === false) {
            return null;
        }

        return [
            'date' => $date->startOfDay(),
            'title' => trim((string) ($matches['title'] ?? 'Release update')) ?: 'Release update',
        ];
    }

    /**
     * @return array{allow_unsafe_links: bool, html_input: string}
     */
    private function markdownOptions(): array
    {
        return [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ];
    }

    private function injectHeadingAnchors(string $html): string
    {
        $slugCounts = [];

        $decoratedHtml = preg_replace_callback('/<h([1-4])>(.*?)<\/h\1>/is', function (array $matches) use (&$slugCounts): string {
            $level = (int) $matches[1];
            $innerHtml = $matches[2];
            $title = trim(html_entity_decode(strip_tags($innerHtml)));
            $baseId = Str::slug($title) ?: 'section';
            $slugCounts[$baseId] = ($slugCounts[$baseId] ?? 0) + 1;
            $sectionId = $slugCounts[$baseId] === 1 ? $baseId : $baseId.'-'.$slugCounts[$baseId];

            return sprintf(
                '<h%d id="%s"><a href="#%s" class="sb-changelog-heading-link">%s</a></h%d>',
                $level,
                e($sectionId),
                e($sectionId),
                $innerHtml,
                $level,
            );
        }, $html);

        return $decoratedHtml ?? $html;
    }
}
