<?php

namespace Tests\Feature\Feature;

use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Public\TitlePage;
use App\Models\AkaType;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Credit;
use App\Models\MovieAwardNominationTitle;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

    public function test_title_page_renders_director_entries_from_catalog_credit_rows(): void
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

        $director = new Person;
        $director->forceFill([
            'id' => 7,
            'imdb_id' => 'nm0005251',
            'name' => 'Lana Wachowski',
            'slug' => 'lana-wachowski-nm0005251',
            'short_biography' => 'Director and writer.',
            'is_published' => true,
            'imdb_primary_professions' => ['director', 'writer'],
        ]);
        $director->exists = true;
        $director->setRelation('professions', new EloquentCollection);

        $directorCredit = new Credit;
        $directorCredit->forceFill([
            'id' => 11,
            'movie_id' => $title->id,
            'name_basic_id' => $director->id,
            'category' => 'director',
            'position' => 1,
        ]);
        $directorCredit->exists = true;
        $directorCredit->setRelation('person', $director);
        $directorCredit->setRelation('nameCreditCharacters', new EloquentCollection);

        $payload = [
            ...$this->titleDetailPayload($title),
            'movieDirectorRows' => collect([$directorCredit]),
        ];

        $loadTitleDetails = Mockery::mock(LoadTitleDetailsAction::class);
        $loadTitleDetails
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn($payload);

        $this->app->instance(LoadTitleDetailsAction::class, $loadTitleDetails);

        Livewire::test(TitlePage::class, ['title' => $title])
            ->assertSee('Movie directors')
            ->assertSee('Lana Wachowski')
            ->assertSee('Director and writer.');
    }

    public function test_title_page_renders_award_nomination_title_entries_from_the_loader_payload(): void
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

        $linkedTitle = $this->makeCatalogTitle(attributes: [
            'id' => 2,
            'imdb_id' => 'tt0234215',
            'name' => 'The Matrix Reloaded',
            'original_name' => 'The Matrix Reloaded',
            'slug' => 'the-matrix-reloaded-tt0234215',
            'release_year' => 2003,
            'meta_description' => 'The Matrix Reloaded catalog page.',
        ]);

        $awardCategory = new AwardCategory;
        $awardCategory->forceFill([
            'id' => 9,
            'name' => 'Best Visual Effects',
        ]);
        $awardCategory->exists = true;

        $awardEvent = new AwardEvent;
        $awardEvent->forceFill([
            'id' => 14,
            'imdb_id' => 'ev0000001',
            'name' => 'Academy Awards',
        ]);
        $awardEvent->exists = true;

        $awardNomination = new AwardNomination;
        $awardNomination->forceFill([
            'id' => 21,
            'movie_id' => $title->id,
            'event_imdb_id' => $awardEvent->imdb_id,
            'award_category_id' => $awardCategory->id,
            'award_year' => 2004,
        ]);
        $awardNomination->exists = true;
        $awardNomination->setRelation('awardCategory', $awardCategory);
        $awardNomination->setRelation('awardEvent', $awardEvent);

        $nominationTitleRow = new MovieAwardNominationTitle;
        $nominationTitleRow->forceFill([
            'movie_award_nomination_id' => $awardNomination->id,
            'nominated_movie_id' => $linkedTitle->id,
            'position' => 1,
        ]);
        $nominationTitleRow->exists = true;
        $nominationTitleRow->setRelation('awardNomination', $awardNomination);
        $nominationTitleRow->setRelation('title', $linkedTitle);

        $payload = [
            ...$this->titleDetailPayload($title),
            'movieAwardNominationTitleRows' => collect([$nominationTitleRow]),
        ];

        $loadTitleDetails = Mockery::mock(LoadTitleDetailsAction::class);
        $loadTitleDetails
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn($payload);

        $this->app->instance(LoadTitleDetailsAction::class, $loadTitleDetails);

        Livewire::test(TitlePage::class, ['title' => $title])
            ->assertSee('Movie award nomination titles')
            ->assertSee('Best Visual Effects')
            ->assertSee('Academy Awards · 2004')
            ->assertSee('The Matrix Reloaded');
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
