<?php

namespace App\Actions\Home;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Enums\MediaKind;
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
                    ->addSelect([
                        'tagline',
                        'synopsis',
                        'created_at',
                    ])
                    ->with([
                        'titleImages' => fn ($imageQuery) => $imageQuery
                            ->select([
                                'id',
                                'mediable_type',
                                'mediable_id',
                                'kind',
                                'url',
                                'alt_text',
                                'caption',
                                'position',
                                'is_primary',
                            ])
                            ->whereIn('kind', [MediaKind::Backdrop, MediaKind::Poster])
                            ->orderBy('position'),
                        'titleVideos' => fn ($videoQuery) => $videoQuery
                            ->select([
                                'id',
                                'mediable_type',
                                'mediable_id',
                                'kind',
                                'url',
                                'caption',
                                'provider',
                                'published_at',
                            ])
                            ->where('kind', MediaKind::Trailer)
                            ->orderByDesc('published_at')
                            ->limit(1),
                        'credits.person:id,name,slug',
                    ]);

                return $query->first() ?? $this->buildPublicTitleIndexQuery
                    ->handle([
                        'sort' => 'popular',
                        'excludeEpisodes' => true,
                    ])
                    ->addSelect([
                        'tagline',
                        'synopsis',
                        'created_at',
                    ])
                    ->with([
                        'titleImages' => fn ($imageQuery) => $imageQuery
                            ->select([
                                'id',
                                'mediable_type',
                                'mediable_id',
                                'kind',
                                'url',
                                'alt_text',
                                'caption',
                                'position',
                                'is_primary',
                            ])
                            ->whereIn('kind', [MediaKind::Backdrop, MediaKind::Poster])
                            ->orderBy('position'),
                        'titleVideos' => fn ($videoQuery) => $videoQuery
                            ->select([
                                'id',
                                'mediable_type',
                                'mediable_id',
                                'kind',
                                'url',
                                'caption',
                                'provider',
                                'published_at',
                            ])
                            ->where('kind', MediaKind::Trailer)
                            ->orderByDesc('published_at')
                            ->limit(1),
                        'credits.person:id,name,slug',
                    ])
                    ->first();
            },
        );
    }
}
