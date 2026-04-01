<?php

namespace App\Actions\Home;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;

class GetHomepageTitleRailAction
{
    public function __construct(
        private BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    /**
     * @return Collection<int, Title>
     */
    public function handle(string $rail, int $limit = 6): Collection
    {
        return match ($rail) {
            'trending' => $this->buildPublicTitleIndexQuery
                ->handle(['sort' => 'trending'])
                ->limit($limit)
                ->get(),
            'top-rated-movies' => $this->buildPublicTitleIndexQuery
                ->handle([
                    'sort' => 'rating',
                    'types' => [TitleType::Movie->value],
                ])
                ->limit($limit)
                ->get(),
            'top-rated-series' => $this->buildPublicTitleIndexQuery
                ->handle([
                    'sort' => 'rating',
                    'types' => [TitleType::Series->value, TitleType::MiniSeries->value],
                ])
                ->limit($limit)
                ->get(),
            'coming-soon' => $this->buildPublicTitleIndexQuery
                ->handle([
                    'types' => [
                        TitleType::Movie->value,
                        TitleType::Series->value,
                        TitleType::MiniSeries->value,
                        TitleType::Documentary->value,
                        TitleType::Special->value,
                        TitleType::Short->value,
                    ],
                ])
                ->where(function ($query): void {
                    $query
                        ->whereDate('release_date', '>=', today()->toDateString())
                        ->orWhere(function ($futureYearQuery): void {
                            $futureYearQuery
                                ->whereNull('release_date')
                                ->where('release_year', '>=', now()->year);
                        });
                })
                ->reorder()
                ->orderBy('release_date')
                ->orderBy('release_year')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
            'recently-added' => $this->buildPublicTitleIndexQuery
                ->handle([
                    'types' => [
                        TitleType::Movie->value,
                        TitleType::Series->value,
                        TitleType::MiniSeries->value,
                        TitleType::Documentary->value,
                        TitleType::Special->value,
                        TitleType::Short->value,
                    ],
                ])
                ->reorder()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(),
            default => $this->buildPublicTitleIndexQuery
                ->handle(['sort' => 'popular'])
                ->limit($limit)
                ->get(),
        };
    }
}
