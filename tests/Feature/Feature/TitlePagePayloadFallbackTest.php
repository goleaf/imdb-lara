<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Public\TitlePage;
use App\Models\AkaType;
use App\Models\Title;
use Livewire\Livewire;
use Mockery;
use Tests\Concerns\BuildsCatalogTitleFixtures;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitlePagePayloadFallbackTest extends TestCase
{
    use BuildsCatalogTitleFixtures;
    use UsesCatalogOnlyApplication;

    public function test_title_page_renders_when_optional_report_collections_are_missing_from_loader_payload(): void
    {
        $title = $this->makeCatalogTitle(attributes: [
            'id' => 1,
            'imdb_id' => 'tt0133093',
            'name' => 'The Matrix',
            'original_name' => 'The Matrix',
            'slug' => 'the-matrix-tt0133093',
            'release_year' => 1999,
            'meta_description' => 'The Matrix catalog page.',
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

        Livewire::test(TitlePage::class, ['title' => $title])
            ->assertSee('The Matrix')
            ->assertSee('Overview');
    }

    public function test_title_page_renders_aka_type_entries_from_the_loader_payload(): void
    {
        $title = $this->makeCatalogTitle(attributes: [
            'id' => 1,
            'imdb_id' => 'tt0133093',
            'name' => 'The Matrix',
            'original_name' => 'The Matrix',
            'slug' => 'the-matrix-tt0133093',
            'release_year' => 1999,
            'meta_description' => 'The Matrix catalog page.',
        ]);

        $akaType = (new AkaType)->forceFill([
            'id' => 11,
            'name' => 'imdbDisplay',
        ]);
        $akaType->exists = true;

        $payload = [
            ...$this->titleDetailPayload($title),
            'akaTypeRows' => collect([$akaType]),
            'akaTypeEntries' => collect([
                [
                    'id' => 11,
                    'label' => 'Imdb Display',
                    'description' => 'Alternate-title classification attached to imported AKA rows.',
                    'linkedAkaCount' => 1,
                    'linkedAkas' => collect([
                        [
                            'text' => 'Matrix',
                            'meta' => 'United States · English',
                        ],
                    ]),
                ],
            ]),
        ];

        $loadTitleDetails = Mockery::mock(LoadTitleDetailsAction::class);
        $loadTitleDetails
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn($payload);

        $this->app->instance(LoadTitleDetailsAction::class, $loadTitleDetails);

        Livewire::test(TitlePage::class, ['title' => $title])
            ->assertSee('AKA types')
            ->assertSee('Imdb Display')
            ->assertSee('Alternate-title classification attached to imported AKA rows.')
            ->assertSee('Matrix');
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
            'akaTypeEntries' => collect(),
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
