<?php

namespace App\Actions\Home;

use App\Enums\MediaKind;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetAwardsSpotlightTitlesAction
{
    /**
     * @return Collection<int, Title>
     */
    public function handle(int $limit = 4): Collection
    {
        if (Title::usesCatalogOnlySchema()) {
            return new Collection;
        }

        return Cache::remember(
            "home:awards-spotlight:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => Title::query()
                ->select([
                    'id',
                    'name',
                    'slug',
                    'title_type',
                    'release_year',
                    'plot_outline',
                    'synopsis',
                    'tagline',
                    'popularity_rank',
                    'is_published',
                ])
                ->publishedCatalog()
                ->whereHas('statistic', fn (Builder $statisticQuery) => $statisticQuery->where('awards_nominated_count', '>', 0))
                ->with([
                    'statistic:id,title_id,average_rating,rating_count,awards_nominated_count,awards_won_count',
                    'genres:id,name,slug',
                    'mediaAssets' => fn ($mediaQuery) => $mediaQuery
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
                        ->whereIn('kind', [MediaKind::Poster, MediaKind::Backdrop])
                        ->orderBy('position'),
                    'awardNominations' => fn ($awardQuery) => $awardQuery
                        ->select([
                            'id',
                            'award_event_id',
                            'award_category_id',
                            'title_id',
                            'credited_name',
                            'is_winner',
                            'sort_order',
                        ])
                        ->with([
                            'awardEvent:id,award_id,name,slug,year',
                            'awardEvent.award:id,name,slug',
                            'awardCategory:id,name,slug',
                        ])
                        ->orderByDesc('is_winner')
                        ->orderBy('sort_order')
                        ->limit(4),
                ])
                ->orderByDesc(
                    TitleStatistic::query()
                        ->select('awards_won_count')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
                )
                ->orderByDesc(
                    TitleStatistic::query()
                        ->select('awards_nominated_count')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
                )
                ->orderByDesc(
                    TitleStatistic::query()
                        ->select('average_rating')
                        ->whereColumn('title_statistics.title_id', 'titles.id')
                        ->limit(1),
                )
                ->orderBy('popularity_rank')
                ->orderBy('name')
                ->limit($limit)
                ->get(),
        );
    }
}
