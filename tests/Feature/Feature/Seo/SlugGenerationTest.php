<?php

namespace Tests\Feature\Feature\Seo;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Actions\Catalog\GetFeaturedInterestCategoriesAction;
use App\Actions\Catalog\LoadPersonDetailsAction;
use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Models\Genre;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;
use Mockery;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class SlugGenerationTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_titles_and_people_expose_slugged_route_keys_from_local_identifiers(): void
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

        $person = Person::query()->create([
            'nconst' => 'nm0000206',
            'imdb_id' => 'nm0000206',
            'primaryname' => 'Keanu Reeves',
            'displayName' => 'Keanu Reeves',
        ]);

        $this->assertSame(
            Str::slug($title->name).'-'.($title->tconst ?: $title->imdb_id ?: $title->id),
            $title->slug,
        );
        $this->assertSame(
            Str::slug($person->name).'-'.($person->nconst ?: $person->imdb_id ?: $person->id),
            $person->slug,
        );
    }

    public function test_public_catalog_routes_resolve_local_records_by_slug(): void
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

        $person = Person::query()->create([
            'nconst' => 'nm0000206',
            'imdb_id' => 'nm0000206',
            'primaryname' => 'Keanu Reeves',
            'displayName' => 'Keanu Reeves',
        ]);

        $genre = Genre::query()->create([
            'id' => 1,
            'name' => 'Science Fiction',
        ]);

        $this->mockCatalogRouteDependencies($title, $person);

        $this->assertStringEndsWith('-g'.$genre->id, $genre->slug);

        $this->get(route('public.titles.show', ['title' => $title->slug]))
            ->assertOk()
            ->assertSee($title->name);

        $this->get(route('public.people.show', ['person' => $person->slug]))
            ->assertOk()
            ->assertSee($person->name);

        $this->get(route('public.genres.show', ['genre' => $genre->slug]))
            ->assertOk()
            ->assertSee($genre->name);
    }

    private function mockCatalogRouteDependencies(Title $title, Person $person): void
    {
        $loadTitleDetails = Mockery::mock(LoadTitleDetailsAction::class);
        $loadTitleDetails
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Title $resolvedTitle): bool => $resolvedTitle->is($title))
            ->andReturn($this->titleDetailPayload($title));

        $loadPersonDetails = Mockery::mock(LoadPersonDetailsAction::class);
        $loadPersonDetails
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Person $resolvedPerson): bool => $resolvedPerson->is($person))
            ->andReturn($this->personDetailPayload($person));

        $buildPublicTitleIndexQuery = Mockery::mock(BuildPublicTitleIndexQueryAction::class);
        $buildPublicTitleIndexQuery
            ->shouldReceive('handle')
            ->once()
            ->andReturnUsing(fn (): Builder => Title::query()
                ->selectCatalogCardColumns()
                ->publishedCatalog()
                ->whereKey(-1));

        $getFeaturedInterestCategories = Mockery::mock(GetFeaturedInterestCategoriesAction::class);
        $getFeaturedInterestCategories
            ->shouldReceive('handle')
            ->once()
            ->with(4, null)
            ->andReturn(new EloquentCollection);

        $this->app->instance(LoadTitleDetailsAction::class, $loadTitleDetails);
        $this->app->instance(LoadPersonDetailsAction::class, $loadPersonDetails);
        $this->app->instance(BuildPublicTitleIndexQueryAction::class, $buildPublicTitleIndexQuery);
        $this->app->instance(GetFeaturedInterestCategoriesAction::class, $getFeaturedInterestCategories);
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

    /**
     * @return array<string, mixed>
     */
    private function personDetailPayload(Person $person): array
    {
        return [
            'person' => $person,
            'headshot' => null,
            'photoGallery' => collect(),
            'alternateNames' => collect(),
            'alternativeNameRows' => collect(),
            'professionLabels' => collect(),
            'biographyIntro' => null,
            'detailItems' => collect(),
            'knownForTitles' => collect(),
            'frequentCollaborators' => collect(),
            'relatedTitles' => collect(),
            'careerProfileItems' => collect(),
            'creditDepartmentHighlights' => collect(),
            'titleFormatHighlights' => collect(),
            'awardHighlights' => collect(),
            'awardWins' => 0,
            'awardNominationsCount' => 0,
            'publishedCreditCount' => 0,
            'heroProfileItems' => collect(),
            'biographyParagraphs' => collect(),
            'seo' => new PageSeoData(
                title: $person->name,
                description: $person->meta_description,
                canonical: route('public.people.show', $person),
                openGraphType: 'profile',
            ),
        ];
    }
}
