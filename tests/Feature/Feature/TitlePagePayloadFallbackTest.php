<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Models\Title;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitlePagePayloadFallbackTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_title_page_renders_when_optional_report_collections_are_missing_from_loader_payload(): void
    {
        $title = Title::query()->create([
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'titletype' => 'movie',
            'primarytitle' => 'The Matrix',
            'originaltitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
        ]);

        $payload = $this->titleDetailPayload($title);

        unset(
            $payload['certificateRatingRows'],
            $payload['companyRows'],
            $payload['movieCompanyCreditAttributeRows'],
        );

        $loadTitleDetails = Mockery::mock(LoadTitleDetailsAction::class);
        $loadTitleDetails
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn($payload);

        $this->app->instance(LoadTitleDetailsAction::class, $loadTitleDetails);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('The Matrix')
            ->assertSee('Overview');
    }

    /**
     * @return array<string, mixed>
     */
    private function titleDetailPayload(Title $title): array
    {
        return [
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
            'movieCompanyCreditCountryRows' => collect(),
            'movieCompanyCreditSummaryRows' => collect(),
            'movieDirectorRows' => collect(),
            'movieEpisodeRows' => collect(),
            'movieEpisodeSummaryRows' => collect(),
            'movieGenreRows' => collect(),
            'movieImageSummaryRows' => collect(),
            'certificateAttributeRows' => collect(),
            'certificateRatingRows' => collect(),
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
                title: $title->name,
                description: $title->meta_description,
                canonical: route('public.titles.show', $title),
            ),
        ];
    }
}
