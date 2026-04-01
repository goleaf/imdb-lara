<?php

namespace App\Actions\Home;

use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Support\Collection;

class GetBrowseYearsAction
{
    /**
     * @return Collection<int, array{year: int, titles_count: int}>
     */
    public function handle(int $limit = 12): Collection
    {
        return Title::query()
            ->select(['release_year'])
            ->published()
            ->where('title_type', '!=', TitleType::Episode)
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
            ->values();
    }
}
