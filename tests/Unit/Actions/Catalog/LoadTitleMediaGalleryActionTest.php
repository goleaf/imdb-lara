<?php

namespace Tests\Unit\Actions\Catalog;

use App\Actions\Catalog\LoadTitleMediaGalleryAction;
use App\Enums\MediaKind;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use App\Models\TitleVideo;
use App\Models\VideoType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class LoadTitleMediaGalleryActionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_it_paginates_each_image_group_independently_with_eight_items_per_page(): void
    {
        $this->app['request']->query->replace([
            'posters_page' => 2,
            'stills_page' => 2,
            'backdrops_page' => 2,
        ]);

        $title = new Title([
            'tconst' => 'tt4633694',
            'imdb_id' => 'tt4633694',
            'titletype' => 'movie',
            'primarytitle' => 'Spider-Man: Into the Spider-Verse',
            'originaltitle' => 'Spider-Man: Into the Spider-Verse',
            'isadult' => 0,
            'startyear' => 2018,
        ]);

        $title->setRelation('statistic', null);
        $title->setRelation('plotRecord', null);
        $title->setRelation('primaryImageRecord', null);
        $title->setRelation('titleImages', new EloquentCollection([
            ...$this->makeAssets(MediaKind::Poster, 13, 'poster'),
            ...$this->makeAssets(MediaKind::Still, 7, 'still'),
            ...$this->makeAssets(MediaKind::Gallery, 7, 'gallery'),
            ...$this->makeAssets(MediaKind::Backdrop, 15, 'backdrop'),
        ]));
        $title->setRelation('titleVideos', new EloquentCollection([
            $this->makeTrailer(1),
        ]));

        $payload = app(LoadTitleMediaGalleryAction::class)->handle($title);

        $this->assertSame(42, $payload['totalImageAssets']);
        $this->assertSame(13, $payload['posterAssets']->count());
        $this->assertSame(14, $payload['stillAssets']->count());
        $this->assertSame(15, $payload['backdropAssets']->count());
        $this->assertSame(13, count($payload['imageLightboxGroups']['posters']['items']));
        $this->assertSame(14, count($payload['imageLightboxGroups']['stills']['items']));
        $this->assertSame(15, count($payload['imageLightboxGroups']['backdrops']['items']));
        $this->assertSame(['Primary'], $payload['imageLightboxGroups']['posters']['items'][0]['meta']);

        $this->assertSame(8, $payload['posterAssetsPagination']->perPage());
        $this->assertSame(13, $payload['posterAssetsPagination']->total());
        $this->assertSame(2, $payload['posterAssetsPagination']->currentPage());
        $this->assertCount(5, $payload['posterAssetsPagination']->items());
        $this->assertSame('posters_page', $payload['posterAssetsPagination']->getPageName());
        $this->assertStringContainsString('posters_page=2', $payload['posterAssetsPagination']->url(2));
        $this->assertStringContainsString('stills_page=2', $payload['posterAssetsPagination']->url(2));
        $this->assertStringContainsString('backdrops_page=2', $payload['posterAssetsPagination']->url(2));
        $this->assertStringContainsString('#title-media-posters', $payload['posterAssetsPagination']->url(2));

        $this->assertSame(14, $payload['stillAssetsPagination']->total());
        $this->assertSame(2, $payload['stillAssetsPagination']->currentPage());
        $this->assertCount(6, $payload['stillAssetsPagination']->items());
        $this->assertSame('stills_page', $payload['stillAssetsPagination']->getPageName());
        $this->assertStringContainsString('#title-media-stills', $payload['stillAssetsPagination']->url(2));

        $this->assertSame(15, $payload['backdropAssetsPagination']->total());
        $this->assertSame(2, $payload['backdropAssetsPagination']->currentPage());
        $this->assertCount(7, $payload['backdropAssetsPagination']->items());
        $this->assertSame('backdrops_page', $payload['backdropAssetsPagination']->getPageName());
        $this->assertStringContainsString('#title-media-backdrops', $payload['backdropAssetsPagination']->url(2));
    }

    /**
     * @return list<CatalogMediaAsset>
     */
    private function makeAssets(MediaKind $kind, int $count, string $prefix): array
    {
        $assets = [];

        for ($position = 1; $position <= $count; $position++) {
            $assets[] = $this->makeAsset($kind, $prefix, $position);
        }

        return $assets;
    }

    private function makeAsset(MediaKind $kind, string $prefix, int $position): CatalogMediaAsset
    {
        return CatalogMediaAsset::fromCatalog([
            'id' => $position,
            'kind' => $kind,
            'url' => sprintf('https://cdn.example.com/%s-%d.jpg', $prefix, $position),
            'alt_text' => sprintf('%s %d', $prefix, $position),
            'caption' => sprintf('%s caption %d', $prefix, $position),
            'position' => $position,
            'is_primary' => $position === 1,
        ]);
    }

    private function makeTrailer(int $position): TitleVideo
    {
        $videoType = new VideoType;
        $videoType->forceFill([
            'id' => 1,
            'name' => 'Trailer',
        ]);

        $trailer = new TitleVideo([
            'imdb_id' => 'vi'.str_pad((string) $position, 7, '0', STR_PAD_LEFT),
            'video_type_id' => 1,
            'name' => sprintf('Trailer %d', $position),
            'description' => sprintf('Trailer description %d', $position),
            'runtime_seconds' => 120,
            'position' => $position,
        ]);
        $trailer->setRelation('videoType', $videoType);

        return $trailer;
    }
}
