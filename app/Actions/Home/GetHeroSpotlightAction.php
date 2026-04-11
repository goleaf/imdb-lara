<?php

namespace App\Actions\Home;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Support\Facades\Cache;

class GetHeroSpotlightAction
{
    /**
     * @var list<string>
     */
    private const FEATURED_CREDIT_CATEGORIES = [
        'actor',
        'actress',
        'director',
        'writer',
        'producer',
    ];

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
                    ]);

                $title = $query->first();

                if (! $title instanceof Title) {
                    return null;
                }

                $this->loadFeaturedCredits($title);

                return $title;
            },
        );
    }

    private function loadFeaturedCredits(Title $title): void
    {
        $title->setRelation('credits', $title->credits()
            ->select([
                'name_credits.id',
                'name_credits.name_basic_id',
                'name_credits.movie_id',
                'name_credits.category',
                'name_credits.episode_count',
                'name_credits.position',
            ])
            ->whereIn('name_credits.category', self::FEATURED_CREDIT_CATEGORIES)
            ->ordered()
            ->withPersonPreview()
            ->limit(8)
            ->get());
    }
}
