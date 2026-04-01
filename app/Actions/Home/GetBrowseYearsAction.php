<?php

namespace App\Actions\Home;

use App\Models\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GetBrowseYearsAction
{
    /**
     * @return Collection<int, array{year: int, titles_count: int}>
     */
    public function handle(int $limit = 12): Collection
    {
        return Cache::remember(
            "home:browse-years:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => Title::query()
                ->select(['release_year'])
                ->publishedCatalog()
                ->whereNotNull('release_year')
                ->orderByDesc('release_year')
                ->pluck('release_year')
                ->countBy()
                ->sortKeysDesc()
                ->take($limit)
                ->map(fn (int $titlesCount, int|string $year): array => [
                    'year' => (int) $year,
                    'titles_count' => $titlesCount,
                ])
                ->values(),
        );
    }
}
