<?php

namespace Tests\Unit\Actions\Catalog;

use App\Actions\Catalog\LoadTitleMediaArchiveAction;
use App\Enums\MediaKind;
use App\Enums\TitleMediaArchiveKind;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use App\Models\TitleVideo;
use App\Models\VideoType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class LoadTitleMediaArchiveActionTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_it_builds_a_paginated_image_archive_payload_with_current_page_lightbox_items(): void
    {
        $this->app['request']->query->replace([
            'page' => 2,
        ]);

        $title = $this->makeTitle();
        $title->setRelation('statistic', null);
        $title->setRelation('plotRecord', null);
        $title->setRelation('primaryImageRecord', null);
        $title->setRelation('titleImages', new EloquentCollection([
            ...$this->makeAssets(MediaKind::Poster, 25, 'poster'),
            ...$this->makeAssets(MediaKind::Still, 7, 'still'),
            ...$this->makeAssets(MediaKind::Backdrop, 5, 'backdrop'),
        ]));
        $title->setRelation('titleVideos', new EloquentCollection([
            $this->makeTrailer(1),
            $this->makeTrailer(2),
            $this->makeTrailer(3),
        ]));

        $payload = app(LoadTitleMediaArchiveAction::class)->handle($title, TitleMediaArchiveKind::Posters);

        $this->assertSame(25, $payload['archiveAssetCount']);
        $this->assertSame([
            'posters' => 25,
            'stills' => 7,
            'backdrops' => 5,
            'trailers' => 3,
        ], $payload['mediaCounts']);
        $this->assertNull($payload['trailerAssetsPagination']);
        $this->assertSame(24, $payload['archiveAssetsPagination']?->perPage());
        $this->assertSame(2, $payload['archiveAssetsPagination']?->currentPage());
        $this->assertSame(25, $payload['archiveAssetsPagination']?->total());
        $this->assertCount(1, $payload['archiveAssetsPagination']?->items() ?? []);
        $this->assertSame('page', $payload['archiveAssetsPagination']?->getPageName());
        $this->assertStringContainsString('#title-media-archive-posters', $payload['archiveAssetsPagination']?->url(2) ?? '');
        $this->assertSame('Posters', $payload['imageLightboxGroups']['posters']['label']);
        $this->assertCount(1, $payload['imageLightboxGroups']['posters']['items']);
        $this->assertSame('Spider-Man: Into the Spider-Verse Posters - Page 2 · Screenbase', $payload['seo']->documentTitle(request()));
    }

    public function test_it_builds_a_paginated_trailer_archive_payload(): void
    {
        $this->app['request']->query->replace([
            'page' => 1,
        ]);

        $title = $this->makeTitle();
        $title->setRelation('statistic', null);
        $title->setRelation('plotRecord', null);
        $title->setRelation('primaryImageRecord', null);
        $title->setRelation('titleImages', new EloquentCollection([
            ...$this->makeAssets(MediaKind::Poster, 2, 'poster'),
        ]));
        $title->setRelation('titleVideos', new EloquentCollection([
            $this->makeTrailer(1),
            $this->makeTrailer(2),
            $this->makeTrailer(3),
            $this->makeTrailer(4),
        ]));

        $payload = app(LoadTitleMediaArchiveAction::class)->handle($title, TitleMediaArchiveKind::Trailers);

        $this->assertSame(4, $payload['archiveAssetCount']);
        $this->assertNull($payload['archiveAssetsPagination']);
        $this->assertSame(12, $payload['trailerAssetsPagination']?->perPage());
        $this->assertSame(4, $payload['trailerAssetsPagination']?->total());
        $this->assertCount(4, $payload['trailerAssetsPagination']?->items() ?? []);
        $this->assertSame([], $payload['imageLightboxGroups']);
        $this->assertNotNull($payload['trailerPreviewAsset']);
        $this->assertSame('Spider-Man: Into the Spider-Verse Trailers · Screenbase', $payload['seo']->documentTitle(request()));
    }

    private function makeTitle(): Title
    {
        return new Title([
            'tconst' => 'tt4633694',
            'imdb_id' => 'tt4633694',
            'titletype' => 'movie',
            'primarytitle' => 'Spider-Man: Into the Spider-Verse',
            'originaltitle' => 'Spider-Man: Into the Spider-Verse',
            'isadult' => 0,
            'startyear' => 2018,
        ]);
    }

    /**
     * @return list<CatalogMediaAsset>
     */
    private function makeAssets(MediaKind $kind, int $count, string $prefix): array
    {
        $assets = [];

        for ($position = 1; $position <= $count; $position++) {
            $assets[] = CatalogMediaAsset::fromCatalog([
                'id' => $position,
                'kind' => $kind,
                'url' => sprintf('https://cdn.example.com/%s-%d.jpg', $prefix, $position),
                'alt_text' => sprintf('%s %d', $prefix, $position),
                'caption' => sprintf('%s caption %d', $prefix, $position),
                'position' => $position,
                'is_primary' => $position === 1,
            ]);
        }

        return $assets;
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
