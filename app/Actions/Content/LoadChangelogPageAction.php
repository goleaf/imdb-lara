<?php

namespace App\Actions\Content;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

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
     *     entryNavigation: list<array{
     *         date: Carbon,
     *         id: string,
     *         title: string
     *     }>,
     *     estimatedReadMinutes: int,
     *     heroHeadline: string,
     *     heroSummary: string,
     *     releaseYears: list<string>,
     *     updatedAt: Carbon,
     *     wordCount: int
     * }
     */
    public function handle(): array
    {
        $entries = $this->loadEntries();
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
            'entryNavigation' => $entries
                ->take(8)
                ->map(fn (array $entry): array => [
                    'date' => $entry['date'],
                    'id' => $entry['id'],
                    'title' => $entry['title'],
                ])
                ->values()
                ->all(),
            'estimatedReadMinutes' => max(1, (int) ceil(max($wordCount, 1) / 220)),
            'heroHeadline' => 'Portal Changes',
            'heroSummary' => $newestEntry['excerpt']
                ?? 'Release notes are published from markdown files inside the changelog folder and rendered newest first.',
            'releaseYears' => $entries
                ->map(fn (array $entry): string => $entry['date']->format('Y'))
                ->unique()
                ->values()
                ->all(),
            'updatedAt' => $newestEntry['date'] ?? now(),
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
            return collect([$this->buildStandaloneEntry(trim($markdown))]);
        }

        return collect($entries)
            ->sortByDesc(fn (array $entry): int => $entry['date']->getTimestamp())
            ->values();
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
    private function loadEntries(): Collection
    {
        $directoryEntries = $this->loadDirectoryEntries(base_path('changelog'));

        if ($directoryEntries->isNotEmpty()) {
            return $directoryEntries;
        }

        [, $markdown] = $this->resolveLegacySource();

        return $this->parseEntries($markdown);
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
    private function loadDirectoryEntries(string $directory): Collection
    {
        if (! File::isDirectory($directory)) {
            return collect();
        }

        $entries = collect(File::files($directory))
            ->filter(fn (SplFileInfo $file): bool => $this->isChangelogFile($file))
            ->map(fn (SplFileInfo $file): array => [
                'entry' => $this->buildFileEntry($file->getPathname()),
                'sort_key' => $file->getFilename(),
            ])
            ->values()
            ->all();

        usort($entries, function (array $left, array $right): int {
            $dateComparison = $right['entry']['date']->getTimestamp() <=> $left['entry']['date']->getTimestamp();

            if ($dateComparison !== 0) {
                return $dateComparison;
            }

            return strcmp($right['sort_key'], $left['sort_key']);
        });

        return collect($entries)
            ->map(fn (array $entry): array => $entry['entry'])
            ->values();
    }

    private function isChangelogFile(SplFileInfo $file): bool
    {
        if (Str::lower($file->getExtension()) !== 'md') {
            return false;
        }

        return preg_match('/^changelog-\d{4}-\d{2}-\d{2}(?:[-_].+)?\.md$/i', $file->getFilename()) === 1;
    }

    /**
     * @return array{
     *     date: Carbon,
     *     excerpt: string|null,
     *     html: string,
     *     id: string,
     *     title: string
     * }
     */
    private function buildFileEntry(string $path): array
    {
        $markdown = trim(File::get($path));
        $fileDate = $this->resolveDateFromPath($path)
            ?? Carbon::createFromTimestamp(File::lastModified($path))->startOfDay();
        $fallbackTitle = $this->resolveTitleFromPath($path);
        $firstHeading = $this->extractPrimaryHeading($markdown);
        $headingData = $firstHeading !== null
            ? $this->parseEntryHeadingText($firstHeading['text'])
            : null;
        $title = $headingData['title']
            ?? $firstHeading['text']
            ?? $fallbackTitle
            ?? 'Release update';
        $date = $headingData['date'] ?? $fileDate;
        $bodyMarkdown = $firstHeading !== null
            ? $this->removeFirstHeading($markdown)
            : $markdown;

        return $this->buildStandaloneEntry($bodyMarkdown, $date, $title);
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function resolveLegacySource(): array
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
                '# Release notes are not available yet',
                'Create markdown files like `changelog/changelog-2026-04-11.md` to publish the first Screenbase update entry.',
            ]),
        ];
    }

    private function resolveDateFromPath(string $path): ?Carbon
    {
        if (! preg_match('/changelog-(\d{4}-\d{2}-\d{2})/i', basename($path), $matches)) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d', (string) $matches[1]);

        if ($date === false) {
            return null;
        }

        return $date->startOfDay();
    }

    private function resolveTitleFromPath(string $path): ?string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $slug = preg_replace('/^changelog-\d{4}-\d{2}-\d{2}(?:[-_])?/i', '', $filename);
        $normalizedSlug = trim(str_replace('_', '-', (string) $slug), '-');

        if ($normalizedSlug === '') {
            return null;
        }

        return Str::headline(str_replace('-', ' ', $normalizedSlug));
    }

    /**
     * @return array{line: string, text: string}|null
     */
    private function extractPrimaryHeading(string $markdown): ?array
    {
        preg_match('/^(#{1,2})\s+(.+)$/m', $markdown, $matches);

        if (! isset($matches[0], $matches[2])) {
            return null;
        }

        return [
            'line' => trim((string) $matches[0]),
            'text' => trim((string) $matches[2]),
        ];
    }

    private function removeFirstHeading(string $markdown): string
    {
        $updatedMarkdown = preg_replace('/^(#{1,2})\s+(.+)(?:\r\n|\n|\r)?/m', '', $markdown, 1);

        return trim($updatedMarkdown ?? $markdown);
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

        return $this->buildStandaloneEntry($bodyMarkdown, $entry['date'], $entry['title']);
    }

    /**
     * @return array{
     *     date: Carbon,
     *     excerpt: string|null,
     *     html: string,
     *     id: string,
     *     title: string
     * }
     */
    private function buildStandaloneEntry(string $markdown, ?Carbon $date = null, ?string $title = null): array
    {
        $bodyMarkdown = trim($markdown);
        $firstHeading = $this->extractPrimaryHeading($bodyMarkdown);

        if ($title === null && $firstHeading !== null) {
            $title = $firstHeading['text'];
            $bodyMarkdown = $this->removeFirstHeading($bodyMarkdown);
        }

        $bodyMarkdown = preg_replace('/(?:\n|\A)---\s*$/', '', $bodyMarkdown) ?? $bodyMarkdown;
        $bodyMarkdown = trim($bodyMarkdown);
        $resolvedDate = $date ?? now()->startOfDay();
        $resolvedTitle = $title
            ?? 'Release notes';
        $html = $this->injectHeadingAnchors(
            Str::markdown(
                $bodyMarkdown !== ''
                    ? $bodyMarkdown
                    : 'Release notes for this entry have not been written yet.',
                $this->markdownOptions(),
            )
        );

        return [
            'date' => $resolvedDate,
            'excerpt' => $this->extractLeadParagraph($bodyMarkdown),
            'html' => $html,
            'id' => Str::slug($resolvedDate->toDateString().'-'.$resolvedTitle),
            'title' => $resolvedTitle,
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

        return $this->parseEntryHeadingText(trim(Str::after($normalizedLine, '## ')));
    }

    /**
     * @return array{date: Carbon, title: string}|null
     */
    private function parseEntryHeadingText(string $headingText): ?array
    {
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
