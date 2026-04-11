<?php

namespace Tests\Feature\Feature\Seo;

use App\Actions\Catalog\LoadTitleCastAction;
use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Actions\Catalog\LoadTitleMediaArchiveAction;
use App\Actions\Catalog\LoadTitleMediaGalleryAction;
use App\Actions\Home\GetLatestTrailerTitlesAction;
use App\Actions\Seo\PageSeoData;
use App\Enums\TitleMediaArchiveKind;
use App\Enums\TitleType;
use App\Models\Title;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CatalogPageMetadataTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_title_pages_emit_canonical_and_open_graph_metadata_for_the_local_catalog(): void
    {
        $title = $this->makeTitle('tt0133093', 'The Matrix', 'movie', 1999);
        $expectedOpenGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        $this->mockTitleDetailLoader($title);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('<title>'.e($title->meta_title).' · Screenbase</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('public.titles.show', $title).'">', false)
            ->assertSee('<meta property="og:type" content="'.$expectedOpenGraphType.'">', false);
    }

    public function test_trailers_page_emits_catalog_only_metadata(): void
    {
        $getLatestTrailerTitles = Mockery::mock(GetLatestTrailerTitlesAction::class);
        $getLatestTrailerTitles
            ->shouldReceive('query')
            ->once()
            ->andReturn(
                Title::query()
                    ->selectCatalogCardColumns()
                    ->publishedCatalog()
                    ->whereKey(-1)
            );

        $this->app->instance(GetLatestTrailerTitlesAction::class, $getLatestTrailerTitles);

        $this->get(route('public.trailers.latest'))
            ->assertOk()
            ->assertSee('<title>Trailers · Screenbase</title>', false)
            ->assertSee('<meta name="description" content="Browse trailer-linked titles, clips, and featurettes from the imported Screenbase catalog.">', false)
            ->assertSee('<link rel="canonical" href="'.route('public.trailers.latest').'">', false);
    }

    public function test_title_media_page_emits_canonical_and_open_graph_metadata(): void
    {
        $title = $this->makeTitle('tt0133093', 'The Matrix', 'movie', 1999);
        $expectedOpenGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        $loadTitleMediaGallery = Mockery::mock(LoadTitleMediaGalleryAction::class);
        $loadTitleMediaGallery
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn([
                'title' => $title,
                'poster' => null,
                'backdrop' => null,
                'viewerAsset' => null,
                'featuredTrailer' => null,
                'posterAssets' => collect(),
                'stillAssets' => collect(),
                'backdropAssets' => collect(),
                'posterAssetsPagination' => new LengthAwarePaginator([], 0, 8, 1, ['pageName' => 'posters_page']),
                'stillAssetsPagination' => new LengthAwarePaginator([], 0, 8, 1, ['pageName' => 'stills_page']),
                'backdropAssetsPagination' => new LengthAwarePaginator([], 0, 8, 1, ['pageName' => 'backdrops_page']),
                'imageLightboxGroups' => [
                    'posters' => ['label' => 'Posters', 'items' => []],
                    'stills' => ['label' => 'Stills', 'items' => []],
                    'backdrops' => ['label' => 'Backdrops', 'items' => []],
                ],
                'trailerAssets' => collect(),
                'viewerStripAssets' => collect(),
                'leadTrailer' => null,
                'trailerArchive' => collect(),
                'totalImageAssets' => 0,
                'heroCopy' => 'A premium catalog view of posters, stills, backdrops, and trailers attached to this title.',
                'viewerKindLabel' => 'Gallery viewer',
                'featuredTrailerLabel' => null,
                'featuredTrailerDuration' => null,
                'seo' => new PageSeoData(
                    title: $title->name.' Media Gallery',
                    description: 'Browse posters, stills, backdrops, and trailers for '.$title->name.'.',
                    canonical: route('public.titles.media', $title),
                    openGraphType: $expectedOpenGraphType,
                ),
            ]);

        $this->app->instance(LoadTitleMediaGalleryAction::class, $loadTitleMediaGallery);

        $this->get(route('public.titles.media', $title))
            ->assertOk()
            ->assertSee('<title>'.e($title->name.' Media Gallery').' · Screenbase</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('public.titles.media', $title).'">', false)
            ->assertSee('<meta property="og:type" content="'.$expectedOpenGraphType.'">', false);
    }

    public function test_title_media_archive_page_emits_canonical_metadata(): void
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
                    'posters' => 0,
                    'stills' => 0,
                    'backdrops' => 0,
                    'trailers' => 0,
                ],
                'archiveAssetCount' => 0,
                'archiveAssetsPagination' => new LengthAwarePaginator([], 0, 24, 1, ['pageName' => 'page']),
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
                    paginationPageName: 'page',
                ),
            ]);

        $this->app->instance(LoadTitleMediaArchiveAction::class, $loadTitleMediaArchive);

        $this->get(route('public.titles.media.archive', [
            'title' => $title,
            'archive' => $archiveKind->value,
        ]))
            ->assertOk()
            ->assertSee('<title>'.e($title->name.' '.$archiveKind->label()).' · Screenbase</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('public.titles.media.archive', [
                'title' => $title,
                'archive' => $archiveKind->value,
            ]).'">', false)
            ->assertSee('<meta property="og:type" content="article">', false);
    }

    public function test_title_cast_page_emits_canonical_and_open_graph_metadata(): void
    {
        $title = $this->makeTitle('tt0133093', 'The Matrix', 'movie', 1999);
        $expectedOpenGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        $emptyPaginator = new LengthAwarePaginator([], 0, 24);

        $loadTitleCast = Mockery::mock(LoadTitleCastAction::class);
        $loadTitleCast
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn([
                'title' => $title,
                'poster' => null,
                'backdrop' => null,
                'castCredits' => $emptyPaginator,
                'crewCredits' => $emptyPaginator,
                'castPageCredits' => collect(),
                'crewPageCredits' => collect(),
                'castBillingGroups' => collect(),
                'crewGroups' => collect(),
                'leadCrewGroups' => collect(),
                'technicalCrewGroups' => collect(),
                'castCount' => 0,
                'crewCount' => 0,
                'leadCrewCount' => 0,
                'technicalCrewCount' => 0,
                'seo' => new PageSeoData(
                    title: $title->name.' Full Cast',
                    description: 'Browse the full cast and crew list for '.$title->name.'.',
                    canonical: route('public.titles.cast', $title),
                    openGraphType: $expectedOpenGraphType,
                ),
            ]);

        $this->app->instance(LoadTitleCastAction::class, $loadTitleCast);

        $this->get(route('public.titles.cast', $title))
            ->assertOk()
            ->assertSee('<title>'.e($title->name.' Full Cast').' · Screenbase</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('public.titles.cast', $title).'">', false)
            ->assertSee('<meta property="og:type" content="'.$expectedOpenGraphType.'">', false);
    }

    private function mockTitleDetailLoader(Title $title): void
    {
        $loadTitleDetails = Mockery::mock(LoadTitleDetailsAction::class);
        $loadTitleDetails
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn([
                'title' => $title,
                'poster' => null,
                'backdrop' => null,
                'primaryVideo' => null,
                'galleryAssets' => collect(),
                'castPreview' => collect(),
                'crewGroups' => collect(),
                'movieAkaRows' => collect(),
                'movieAkaAttributeRows' => collect(),
                'akaAttributeRows' => collect(),
                'akaAttributeEntries' => collect(),
                'akaTypeRows' => collect(),
                'awardCategoryRows' => collect(),
                'awardEventRows' => collect(),
                'movieAwardNominationRows' => collect(),
                'movieAwardNominationNomineeRows' => collect(),
                'movieAwardNominationTitleRows' => collect(),
                'movieAwardNominationSummaryRows' => collect(),
                'movieCertificateRows' => collect(),
                'movieCertificateSummaryRows' => collect(),
                'movieCertificateAttributeRows' => collect(),
                'movieCompanyCreditRows' => collect(),
                'movieCompanyCreditAttributeRows' => collect(),
                'movieCompanyCreditAttributeEntries' => collect(),
                'movieCompanyCreditCountryRows' => collect(),
                'movieCompanyCreditSummaryRows' => collect(),
                'movieDirectorRows' => collect(),
                'movieEpisodeRows' => collect(),
                'movieEpisodeSummaryRows' => collect(),
                'movieGenreRows' => collect(),
                'movieImageSummaryRows' => collect(),
                'certificateAttributeRows' => collect(),
                'certificateRatingRows' => collect(),
                'certificateAttributeEntries' => collect(),
                'certificateRatingEntries' => collect(),
                'companyRows' => collect(),
                'companyCreditAttributeRows' => collect(),
                'companyCreditCategoryRows' => collect(),
                'movieBoxOfficeRows' => collect(),
                'currencyRows' => collect(),
                'countryRows' => collect(),
                'genreRows' => collect(),
                'genreEntries' => collect(),
                'interestRows' => collect(),
                'interestCategoryRows' => collect(),
                'interestCategoryEntries' => collect(),
                'interestPrimaryImageRows' => collect(),
                'interestSimilarInterestRows' => collect(),
                'detailItems' => collect(),
                'certificateItems' => collect(),
                'awardHighlights' => collect(),
                'relatedTitles' => collect(),
                'seasonNavigation' => collect(),
                'seasons' => collect(),
                'latestSeason' => null,
                'latestSeasonEpisodes' => collect(),
                'topRatedEpisodes' => collect(),
                'countries' => collect(),
                'languages' => collect(),
                'interestHighlights' => collect(),
                'archiveLinks' => collect(),
                'shareModalId' => 'title-share-'.$title->id,
                'shareUrl' => route('public.titles.show', $title),
                'isSeriesLike' => false,
                'ratingCount' => 0,
                'heroStats' => collect(),
                'seo' => new PageSeoData(
                    title: $title->meta_title ?: $title->name,
                    description: $title->meta_description,
                    canonical: route('public.titles.show', $title),
                    openGraphType: in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
                        ? 'video.tv_show'
                        : 'video.movie',
                ),
            ]);

        $this->app->instance(LoadTitleDetailsAction::class, $loadTitleDetails);
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
