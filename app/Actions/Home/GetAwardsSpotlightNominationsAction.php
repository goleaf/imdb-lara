<?php

namespace App\Actions\Home;

use App\Models\AwardNomination;
use App\Models\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GetAwardsSpotlightNominationsAction
{
    /**
     * @return Collection<int, array{
     *     nomination: AwardNomination,
     *     title: Title,
     *     eventLabel: string,
     *     categoryLabel: string,
     *     statusLabel: string,
     *     honoreeLabel: string|null
     * }>
     */
    public function handle(int $limit = 4): Collection
    {
        return Cache::remember(
            "home:awards-spotlight-nominations:{$limit}",
            now()->addMinutes(10),
            function () use ($limit): Collection {
                $nominations = AwardNomination::query()
                    ->select([
                        'id',
                        'movie_id',
                        'event_imdb_id',
                        'award_category_id',
                        'award_year',
                        'text',
                        'is_winner',
                        'winner_rank',
                        'position',
                    ])
                    ->whereNotNull('movie_id')
                    ->whereHas('title', fn ($titleQuery) => $titleQuery->publishedCatalog())
                    ->with([
                        'awardEvent:imdb_id,name',
                        'awardCategory:id,name',
                        'people' => fn ($personQuery) => $personQuery->select([
                            'name_basics.id',
                            'name_basics.nconst',
                            'name_basics.imdb_id',
                            'name_basics.primaryname',
                            'name_basics.displayName',
                        ]),
                        'title' => fn ($titleQuery) => $titleQuery
                            ->select([
                                'movies.id',
                                'movies.tconst',
                                'movies.imdb_id',
                                'movies.primarytitle',
                                'movies.originaltitle',
                                'movies.titletype',
                                'movies.isadult',
                                'movies.startyear',
                                'movies.endyear',
                                'movies.runtimeminutes',
                                'movies.title_type_id',
                                'movies.runtimeSeconds',
                            ])
                            ->publishedCatalog()
                            ->with([
                                'statistic:movie_id,aggregate_rating,vote_count',
                                'titleImages:id,movie_id,position,url,width,height,type',
                                'primaryImageRecord:movie_id,url,width,height,type',
                                'plotRecord:movie_id,plot',
                            ]),
                    ])
                    ->orderByDesc('is_winner')
                    ->orderByDesc('award_year')
                    ->orderBy('position')
                    ->limit(max($limit * 4, 12))
                    ->get();

                return $nominations
                    ->filter(fn (AwardNomination $nomination): bool => $nomination->title instanceof Title)
                    ->unique('movie_id')
                    ->take($limit)
                    ->map(function (AwardNomination $nomination): array {
                        $honoreeLabel = $nomination->people
                            ->pluck('name')
                            ->filter()
                            ->join(', ');

                        return [
                            'nomination' => $nomination,
                            'title' => $nomination->title,
                            'eventLabel' => $nomination->awardEvent?->name ?: 'Awards Archive',
                            'categoryLabel' => $nomination->awardCategory?->name ?: 'Award nomination',
                            'statusLabel' => $nomination->is_winner ? 'Winner' : 'Nominee',
                            'honoreeLabel' => $honoreeLabel !== '' ? $honoreeLabel : null,
                        ];
                    })
                    ->values();
            },
        );
    }
}
