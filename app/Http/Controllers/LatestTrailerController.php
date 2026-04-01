<?php

namespace App\Http\Controllers;

use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Contracts\View\View;

class LatestTrailerController extends Controller
{
    public function __invoke(): View
    {
        $trailerKinds = [MediaKind::Trailer, MediaKind::Clip, MediaKind::Featurette];

        $titles = Title::query()
            ->select([
                'id',
                'name',
                'slug',
                'title_type',
                'release_year',
                'plot_outline',
                'popularity_rank',
                'is_published',
            ])
            ->published()
            ->where('title_type', '!=', TitleType::Episode)
            ->whereHas('titleVideos', fn ($query) => $query->whereIn('kind', $trailerKinds))
            ->with([
                'genres:id,name,slug',
                'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                'mediaAssets' => fn ($query) => $query
                    ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position', 'is_primary'])
                    ->where('kind', MediaKind::Poster)
                    ->orderBy('position')
                    ->limit(1),
                'titleVideos' => fn ($query) => $query
                    ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'caption', 'provider', 'published_at'])
                    ->whereIn('kind', $trailerKinds)
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->orderByDesc(
                MediaAsset::query()
                    ->select('published_at')
                    ->whereColumn('media_assets.mediable_id', 'titles.id')
                    ->where('media_assets.mediable_type', Title::class)
                    ->whereIn('kind', $trailerKinds)
                    ->orderByDesc('published_at')
                    ->limit(1),
            )
            ->simplePaginate(12)
            ->withQueryString();

        return view('trailers.index', [
            'titles' => $titles,
        ]);
    }
}
