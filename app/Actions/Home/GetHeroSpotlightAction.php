<?php

namespace App\Actions\Home;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Support\Facades\Cache;

class GetHeroSpotlightAction
{
    public function __construct(
        private BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    public function handle(): ?Title
    {
        return Cache::remember(
            'home:hero-spotlight',
            now()->addMinutes(10),
            function (): ?Title {
                $query = $this->buildPublicTitleIndexQuery
                    ->handle([
                        'sort' => 'trending',
                        'types' => [
                            TitleType::Movie->value,
                            TitleType::Series->value,
                            TitleType::MiniSeries->value,
                            TitleType::Documentary->value,
                            TitleType::Special->value,
                            TitleType::Short->value,
                        ],
                    ])
                    ->with([
                        'titleVideos:imdb_id,movie_id,video_type_id,name,description,width,height,runtime_seconds,position',
                        'credits' => fn ($creditQuery) => $creditQuery
                            ->select(['name_basic_id', 'movie_id', 'category', 'episode_count', 'position'])
                            ->whereIn('category', ['actor', 'actress', 'director', 'writer', 'producer'])
                            ->with([
                                'person' => fn ($personQuery) => $personQuery
                                    ->select([
                                        'id',
                                        'nconst',
                                        'imdb_id',
                                        'primaryname',
                                        'displayName',
                                        'alternativeNames',
                                        'primaryProfessions',
                                        'biography',
                                        'birthLocation',
                                        'deathLocation',
                                        'primaryImage_url',
                                        'primaryImage_width',
                                        'primaryImage_height',
                                    ])
                                    ->with([
                                        'personImages:name_basic_id,position,url,width,height,type',
                                        'professionTerms:id,name',
                                    ])
                                    ->limit(8),
                            ])
                            ->orderBy('position')
                            ->limit(8),
                    ]);

                return $query->first();
            },
        );
    }
}
