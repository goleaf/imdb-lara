<?php

namespace App\Actions\Home;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Enums\MediaKind;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetLatestTrailerTitlesAction
{
    public function __construct(
        private BuildPublicTitleIndexQueryAction $buildPublicTitleIndexQuery,
    ) {}

    public function query(): Builder
    {
        return $this->buildPublicTitleIndexQuery
            ->handle(['sort' => 'trending'])
            ->whereHas('mediaAssets', fn (Builder $mediaQuery) => $mediaQuery
                ->whereIn('kind', [MediaKind::Trailer, MediaKind::Clip, MediaKind::Featurette])
                ->whereNotNull('url'));
    }

    /**
     * @return Collection<int, Title>
     */
    public function handle(int $limit = 4): Collection
    {
        return Cache::remember(
            "home:latest-trailers:{$limit}",
            now()->addMinutes(10),
            fn (): Collection => $this->query()
                ->limit($limit)
                ->get(),
        );
    }
}
