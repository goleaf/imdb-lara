<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\LoadTitleMediaArchiveAction;
use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleMediaArchiveKind;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleMediaArchiveShellContractTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_image_archive_page_exposes_the_sheaf_summary_card_surface(): void
    {
        $title = $this->makeTitle('tt0133093', 'The Matrix', 'movie', 1999);
        $archiveKind = TitleMediaArchiveKind::Posters;

        $loadTitleMediaArchive = Mockery::mock(LoadTitleMediaArchiveAction::class);
        $loadTitleMediaArchive
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle, TitleMediaArchiveKind $resolvedArchiveKind): bool => $resolvedTitle->is($title) && $resolvedArchiveKind === $archiveKind)
            ->andReturn([
                'title' => $title,
                'archiveKind' => $archiveKind,
                'mediaCounts' => [
                    'posters' => 2,
                    'stills' => 0,
                    'backdrops' => 0,
                    'trailers' => 0,
                ],
                'archiveAssetCount' => 2,
                'archiveAssetsPagination' => new LengthAwarePaginator(
                    items: collect([
                        CatalogMediaAsset::fromCatalog([
                            'kind' => MediaKind::Poster,
                            'url' => 'https://example.com/poster-one.jpg',
                            'alt_text' => 'Poster One',
                        ]),
                        CatalogMediaAsset::fromCatalog([
                            'kind' => MediaKind::Poster,
                            'url' => 'https://example.com/poster-two.jpg',
                            'alt_text' => 'Poster Two',
                        ]),
                    ]),
                    total: 2,
                    perPage: 24,
                    currentPage: 1,
                    options: ['pageName' => 'page'],
                ),
                'trailerAssetsPagination' => null,
                'imageLightboxGroups' => [
                    'posters' => ['label' => 'Posters', 'items' => []],
                ],
                'trailerPreviewAsset' => null,
                'overviewHref' => route('public.titles.media', $title),
                'seo' => new PageSeoData(
                    title: $title->name.' '.$archiveKind->label(),
                    description: 'Browse '.$archiveKind->label().' from the media archive for '.$title->name.'.',
                    canonical: route('public.titles.media.archive', [
                        'title' => $title,
                        'archive' => $archiveKind->value,
                    ]),
                    openGraphType: 'article',
                ),
            ]);

        $this->app->instance(LoadTitleMediaArchiveAction::class, $loadTitleMediaArchive);

        $this->get(route('public.titles.media.archive', [
            'title' => $title,
            'archive' => $archiveKind->value,
        ]))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-media-archive-summary"')
            ->assertSee('Visible archive')
            ->assertSee('2 items');
    }

    public function test_trailer_archive_page_exposes_sheaf_badges_for_trailer_metadata(): void
    {
        $title = $this->makeTitle('tt0133093', 'The Matrix', 'movie', 1999);
        $archiveKind = TitleMediaArchiveKind::Trailers;
        $trailer = CatalogMediaAsset::fromCatalog([
            'kind' => MediaKind::Trailer,
            'name' => 'Official Trailer',
            'url' => 'https://example.com/trailer.mp4',
            'caption' => 'Official trailer',
            'alt_text' => 'Trailer frame',
            'duration_seconds' => 120,
            'published_at' => now()->setDate(2024, 3, 31)->startOfDay(),
        ]);

        $loadTitleMediaArchive = Mockery::mock(LoadTitleMediaArchiveAction::class);
        $loadTitleMediaArchive
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle, TitleMediaArchiveKind $resolvedArchiveKind): bool => $resolvedTitle->is($title) && $resolvedArchiveKind === $archiveKind)
            ->andReturn([
                'title' => $title,
                'archiveKind' => $archiveKind,
                'mediaCounts' => [
                    'posters' => 0,
                    'stills' => 0,
                    'backdrops' => 0,
                    'trailers' => 1,
                ],
                'archiveAssetCount' => 1,
                'archiveAssetsPagination' => null,
                'trailerAssetsPagination' => new LengthAwarePaginator(
                    items: collect([$trailer]),
                    total: 1,
                    perPage: 12,
                    currentPage: 1,
                    options: ['pageName' => 'page'],
                ),
                'imageLightboxGroups' => [],
                'trailerPreviewAsset' => $trailer,
                'overviewHref' => route('public.titles.media', $title),
                'seo' => new PageSeoData(
                    title: $title->name.' '.$archiveKind->label(),
                    description: 'Browse '.$archiveKind->label().' from the media archive for '.$title->name.'.',
                    canonical: route('public.titles.media.archive', [
                        'title' => $title,
                        'archive' => $archiveKind->value,
                    ]),
                    openGraphType: 'article',
                ),
            ]);

        $this->app->instance(LoadTitleMediaArchiveAction::class, $loadTitleMediaArchive);

        $this->get(route('public.titles.media.archive', [
            'title' => $title,
            'archive' => $archiveKind->value,
        ]))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-media-archive-summary"')
            ->assertSeeHtml('data-slot="title-media-trailer-meta"')
            ->assertSee('IMDb')
            ->assertSee('2 min')
            ->assertSee('Mar 31, 2024');
    }

    private function makeTitle(string $imdbId, string $name, string $type, int $year): Title
    {
        return Title::query()->create([
            'tconst' => $imdbId,
            'imdb_id' => $imdbId,
            'titletype' => $type,
            'primarytitle' => $name,
            'originaltitle' => $name,
            'isadult' => 0,
            'startyear' => $year,
        ]);
    }
}
