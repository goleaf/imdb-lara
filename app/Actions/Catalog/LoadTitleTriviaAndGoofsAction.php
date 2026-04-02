<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadTitleTriviaAndGoofsAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: MediaAsset|null,
     *     backdrop: MediaAsset|null,
     *     triviaItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     goofItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     triviaTotalCount: int,
     *     goofTotalCount: int,
     *     spoilerFactCount: int,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
            'mediaAssets' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'position',
                    'is_primary',
                ])
                ->ordered(),
        ]);

        $poster = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Poster, MediaKind::Backdrop);
        $backdrop = MediaAsset::preferredFrom($title->mediaAssets, MediaKind::Backdrop, MediaKind::Poster);
        $summary = $this->summarize($title);
        $openGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            ...$summary,
            'seo' => new PageSeoData(
                title: $title->name.' Trivia & Goofs',
                description: 'Browse trivia notes, goofs, spoiler labels, and community signal for '.$title->name.'.',
                canonical: route('public.titles.trivia', $title),
                openGraphType: $openGraphType,
                openGraphImage: $backdrop?->url ?? $poster?->url,
                openGraphImageAlt: $backdrop?->alt_text ?: $poster?->alt_text ?: $title->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                    ['label' => 'Trivia & Goofs'],
                ],
                paginationPageName: null,
            ),
        ];
    }

    /**
     * @return array{
     *     triviaItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     goofItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     triviaTotalCount: int,
     *     goofTotalCount: int,
     *     spoilerFactCount: int
     * }
     */
    public function summarize(Title $title): array
    {
        $triviaSection = $title->imdbPayloadSection('trivia');
        $goofsSection = $title->imdbPayloadSection('goofs');
        $triviaItems = $this->buildFactItems(collect(data_get($triviaSection, 'triviaEntries', [])));
        $goofItems = $this->buildFactItems(collect(data_get($goofsSection, 'goofEntries') ?? data_get($goofsSection, 'items') ?? []));

        return [
            'triviaItems' => $triviaItems,
            'goofItems' => $goofItems,
            'triviaTotalCount' => $this->resolveTotalCount($triviaSection, $triviaItems->count()),
            'goofTotalCount' => $this->resolveTotalCount($goofsSection, $goofItems->count()),
            'spoilerFactCount' => $triviaItems->where('isSpoiler', true)->count() + $goofItems->where('isSpoiler', true)->count(),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $entries
     * @return Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>
     */
    private function buildFactItems(Collection $entries): Collection
    {
        return $entries
            ->map(function (mixed $entry): ?array {
                $spoilerText = is_array($entry)
                    ? $this->nullableString(data_get($entry, 'spoiler.text'))
                    : null;
                $text = match (true) {
                    is_array($entry) => $this->nullableString(data_get($entry, 'text'))
                        ?? $this->nullableString(data_get($entry, 'plainText'))
                        ?? $spoilerText,
                    is_string($entry) => $this->nullableString($entry),
                    default => null,
                };

                if ($text === null) {
                    return null;
                }

                $score = is_array($entry)
                    ? $this->firstNullableInt([
                        data_get($entry, 'interestCount'),
                        data_get($entry, 'voteCount'),
                        data_get($entry, 'votes.total'),
                        data_get($entry, 'voteBreakdown.total'),
                        data_get($entry, 'upVotes'),
                    ])
                    : null;

                $isSpoiler = $spoilerText !== null
                    || (bool) (is_array($entry)
                        ? (data_get($entry, 'isSpoiler')
                            ?? data_get($entry, 'containsSpoilers')
                            ?? data_get($entry, 'spoiler.isSpoiler')
                            ?? false)
                        : false);

                return [
                    'text' => str($text)->squish()->toString(),
                    'isSpoiler' => $isSpoiler,
                    'score' => $score,
                    'scoreLabel' => $score !== null ? 'Signal '.number_format($score) : null,
                    'scoreTone' => $this->scoreTone($score),
                ];
            })
            ->filter()
            ->values();
    }

    private function resolveTotalCount(mixed $section, int $fallbackCount): int
    {
        if (! is_array($section)) {
            return $fallbackCount;
        }

        return max(
            $fallbackCount,
            $this->firstNullableInt([
                data_get($section, 'totalCount'),
                data_get($section, 'count'),
            ]) ?? 0,
        );
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function firstNullableInt(array $values): ?int
    {
        foreach ($values as $value) {
            if (is_scalar($value) && is_numeric((string) $value)) {
                return max(0, (int) $value);
            }
        }

        return null;
    }

    private function scoreTone(?int $score): string
    {
        return match (true) {
            $score === null => 'neutral',
            $score >= 15 => 'high',
            $score >= 5 => 'mid',
            default => 'light',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
