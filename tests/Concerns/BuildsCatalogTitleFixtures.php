<?php

namespace Tests\Concerns;

use App\Enums\MediaKind;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait BuildsCatalogTitleFixtures
{
    /**
     * @param  list<Genre>  $genres
     * @param  list<MediaAsset>  $mediaAssets
     */
    protected function makeCatalogTitle(
        array $attributes = [],
        array $genres = [],
        ?TitleStatistic $statistic = null,
        array $mediaAssets = [],
    ): Title {
        $title = new Title;
        $title->forceFill([
            'id' => 1,
            'imdb_id' => 'tt0000001',
            'name' => 'Untitled',
            'original_name' => 'Untitled',
            'slug' => 'untitled-tt0000001',
            'title_type' => 'movie',
            'release_year' => 2000,
            'end_year' => null,
            'release_date' => null,
            'runtime_minutes' => 120,
            'runtime_seconds' => 7200,
            'age_rating' => null,
            'tagline' => null,
            'origin_country' => null,
            'original_language' => null,
            'popularity_rank' => null,
            'canonical_title_id' => null,
            'plot_outline' => null,
            'synopsis' => null,
            'meta_title' => null,
            'meta_description' => null,
            'search_keywords' => null,
            'is_published' => true,
            ...$attributes,
        ]);
        $title->exists = true;
        $title->setRelation('genres', new EloquentCollection($genres));
        $title->setRelation('statistic', $statistic);
        $title->setRelation('mediaAssets', new EloquentCollection($mediaAssets));

        return $title;
    }

    protected function makeCatalogGenre(int $id, string $name): Genre
    {
        $genre = new Genre;
        $genre->forceFill([
            'id' => $id,
            'name' => $name,
            'slug' => sprintf('%s-g%d', str($name)->slug()->toString(), $id),
        ]);
        $genre->exists = true;

        return $genre;
    }

    protected function makeCatalogStatistic(int $titleId, float $averageRating, int $ratingCount): TitleStatistic
    {
        $statistic = new TitleStatistic;
        $statistic->forceFill([
            'title_id' => $titleId,
            'average_rating' => $averageRating,
            'rating_count' => $ratingCount,
        ]);
        $statistic->exists = true;

        return $statistic;
    }

    protected function makeCatalogPoster(int $titleId, string $url, array $attributes = []): MediaAsset
    {
        $mediaAsset = new MediaAsset;
        $mediaAsset->forceFill([
            'id' => 1,
            'mediable_type' => Title::class,
            'mediable_id' => $titleId,
            'kind' => MediaKind::Poster,
            'url' => $url,
            'alt_text' => null,
            'caption' => null,
            'width' => 1000,
            'height' => 1500,
            'duration_seconds' => null,
            'metadata' => [],
            'is_primary' => true,
            'position' => 1,
            ...$attributes,
        ]);
        $mediaAsset->exists = true;

        return $mediaAsset;
    }
}
