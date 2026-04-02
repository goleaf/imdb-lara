<?php

namespace App\Actions\Home;

use App\Models\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GetBrowseKeywordsAction
{
    /**
     * @return Collection<int, array{keyword: string, titles_count: int}>
     */
    public function handle(int $limit = 12): Collection
    {
        return Cache::remember(
            "home:browse-keywords:{$limit}",
            now()->addMinutes(10),
            function () use ($limit): Collection {
                /** @var Collection<int, array{keyword: string, titles_count: int}> $keywords */
                $keywords = Title::query()
                    ->select(['id', 'search_keywords'])
                    ->publishedCatalog()
                    ->whereNotNull('search_keywords')
                    ->get()
                    ->reduce(function (Collection $carry, Title $title): Collection {
                        $keywordsForTitle = collect(
                            preg_split('/\s*,\s*/', (string) $title->search_keywords, -1, PREG_SPLIT_NO_EMPTY) ?: [],
                        )
                            ->map(function (string $keyword): string {
                                return trim((string) preg_replace('/\s+/', ' ', $keyword));
                            })
                            ->filter(function (string $keyword): bool {
                                return $keyword !== ''
                                    && mb_strlen($keyword) >= 4
                                    && ! in_array(Str::lower($keyword), ['movie', 'movies', 'show', 'shows', 'series', 'film', 'tv'], true);
                            })
                            ->unique(function (string $keyword): string {
                                return (string) Str::of($keyword)->lower()->replaceMatches('/\s+/', ' ');
                            });

                        foreach ($keywordsForTitle as $keyword) {
                            $normalizedKeyword = (string) Str::of($keyword)->lower()->replaceMatches('/\s+/', ' ');
                            $entry = $carry->get($normalizedKeyword, [
                                'keyword' => Str::of($keyword)->trim()->headline()->value(),
                                'titles_count' => 0,
                            ]);

                            $entry['titles_count']++;

                            $carry->put($normalizedKeyword, $entry);
                        }

                        return $carry;
                    }, collect())
                    ->sort(function (array $left, array $right): int {
                        if ($left['titles_count'] === $right['titles_count']) {
                            return strcmp($left['keyword'], $right['keyword']);
                        }

                        return $right['titles_count'] <=> $left['titles_count'];
                    })
                    ->take($limit)
                    ->values();

                return $keywords;
            },
        );
    }
}
