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
                'id',
                'title_id',
                'person_id',
                'department',
                'job',
                'character_name',
                'billing_order',
                'is_principal',
                'person_profession_id',
                'episode_id',
                'credited_as',
            ])
            ->whereIn('credits.department', ['Cast', 'Directing', 'Writing', 'Production'])
            ->ordered()
            ->withPersonPreview()
            ->limit(8)
            ->get());
    }
}
