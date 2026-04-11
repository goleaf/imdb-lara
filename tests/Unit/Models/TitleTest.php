<?php

namespace Tests\Unit\Models;

use App\Actions\Admin\BuildAdminTitlesIndexQueryAction;
use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use App\Enums\TitleType as CatalogTitleType;
use App\Models\AkaType;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\CertificateAttribute;
use App\Models\CertificateRating;
use App\Models\Company;
use App\Models\CompanyCreditAttribute;
use App\Models\CompanyCreditCategory;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Genre;
use App\Models\Interest;
use App\Models\InterestCategory;
use App\Models\InterestCategoryInterest;
use App\Models\InterestPrimaryImage;
use App\Models\InterestSimilarInterest;
use App\Models\MediaAsset;
use App\Models\MovieAka;
use App\Models\MovieAkaAttribute;
use App\Models\MovieAkaType;
use App\Models\MovieAwardNominationNominee;
use App\Models\MovieAwardNominationSummary;
use App\Models\MovieAwardNominationTitle;
use App\Models\MovieBoxOffice;
use App\Models\MovieCertificate;
use App\Models\MovieCertificateAttribute;
use App\Models\MovieCertificateSummary;
use App\Models\MovieCompanyCredit;
use App\Models\MovieCompanyCreditAttribute;
use App\Models\MovieCompanyCreditCountry;
use App\Models\MovieCompanyCreditSummary;
use App\Models\MovieDirector;
use App\Models\MovieEpisode;
use App\Models\MovieEpisodeSummary;
use App\Models\MovieGenre;
use App\Models\MovieImageSummary;
use App\Models\MoviePlot;
use App\Models\Person;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        Model::unguard();
    }

    protected function tearDown(): void
    {
        Model::reguard();

        parent::tearDown();
    }

    public function test_title_type_normalizes_lowercase_remote_series_variants(): void
    {
        $series = new Title(['titletype' => 'tvseries']);
        $miniSeries = new Title(['titletype' => 'tvminiseries']);
        $pilot = new Title(['titletype' => 'tvpilot']);
        $shortSeries = new Title(['titletype' => 'tvshortseries']);

        $this->assertSame(CatalogTitleType::Series, $series->title_type);
        $this->assertSame(CatalogTitleType::MiniSeries, $miniSeries->title_type);
        $this->assertSame(CatalogTitleType::Series, $pilot->title_type);
        $this->assertSame(CatalogTitleType::Series, $shortSeries->title_type);
    }

    public function test_catalog_only_schema_is_auto_enabled_for_the_remote_imdb_connection(): void
    {
        config()->set('screenbase.catalog_only', false);
        config()->set('database.default', 'imdb_mysql');

        $this->assertTrue(Title::usesCatalogOnlySchema());
        $this->assertTrue(Person::usesCatalogOnlySchema());
    }

    public function test_admin_title_index_query_uses_movies_table_when_remote_imdb_connection_is_active(): void
    {
        config()->set('screenbase.catalog_only', false);
        config()->set('database.default', 'imdb_mysql');

        $sql = app(BuildAdminTitlesIndexQueryAction::class)->handle()->toSql();

        $this->assertStringContainsString('from `movies`', $sql);
        $this->assertStringNotContainsString('`titles`', $sql);
    }

    public function test_public_title_index_query_uses_movies_table_when_remote_imdb_connection_is_active(): void
    {
        config()->set('screenbase.catalog_only', false);
        config()->set('database.default', 'imdb_mysql');

        $sql = app(BuildPublicTitleIndexQueryAction::class)->handle([
            'sort' => 'popular',
            'types' => [CatalogTitleType::Movie->value],
        ])->toSql();

        $this->assertStringContainsString('from `movies`', $sql);
        $this->assertStringNotContainsString('`titles`', $sql);
    }

    public function test_display_rating_helpers_use_loaded_statistic_accessors(): void
    {
        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $statistic = new TitleStatistic([
            'movie_id' => 1,
            'aggregate_rating' => '8.70',
            'vote_count' => 2100000,
        ]);

        $title->setRelation('statistic', $statistic);

        $this->assertSame(8.7, $title->displayAverageRating());
        $this->assertSame(2100000, $title->displayRatingCount());
    }

    public function test_preferred_poster_tolerates_partial_loaded_media_assets(): void
    {
        $poster = new MediaAsset;
        $poster->forceFill([
            'mediable_type' => Title::class,
            'mediable_id' => 1,
            'kind' => 'poster',
            'url' => 'https://cdn.example.com/matrix-poster.jpg',
        ]);
        $poster->exists = true;

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('mediaAssets', new EloquentCollection([$poster]));

        $preferredPoster = $title->preferredPoster();

        $this->assertNotNull($preferredPoster);
        $this->assertSame('https://cdn.example.com/matrix-poster.jpg', $preferredPoster->url);
        $this->assertNull($preferredPoster->caption);
        $this->assertSame(0, $preferredPoster->position);
    }

    public function test_runtime_minutes_label_adds_hours_and_minutes_for_longer_titles(): void
    {
        $shortRuntimeTitle = new Title(['runtimeminutes' => 54]);
        $featureRuntimeTitle = new Title(['runtimeminutes' => 117]);

        $this->assertSame('54 min', $shortRuntimeTitle->runtimeMinutesLabel());
        $this->assertSame('117 min (1h 57min)', $featureRuntimeTitle->runtimeMinutesLabel());
    }

    public function test_summary_text_falls_back_to_loaded_plot_record(): void
    {
        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('plotRecord', new MoviePlot([
            'movie_id' => 1,
            'plot' => 'A hacker discovers the truth behind his reality.',
        ]));

        $this->assertSame(
            'A hacker discovers the truth behind his reality.',
            $title->summaryText(),
        );
    }

    public function test_resolved_movie_akas_returns_loaded_rows(): void
    {
        $firstMovieAka = new MovieAka([
            'movie_id' => 1,
            'text' => 'The Matrix',
            'country_code' => 'US',
            'language_code' => 'en',
            'position' => 1,
        ]);
        $firstMovieAka->setRawAttributes([
            'id' => 100,
            'movie_id' => 1,
            'text' => 'The Matrix',
            'country_code' => 'US',
            'language_code' => 'en',
            'position' => 1,
        ], sync: true);

        $duplicateMovieAka = new MovieAka([
            'movie_id' => 1,
            'text' => 'The Matrix',
            'country_code' => 'US',
            'language_code' => 'en',
            'position' => 1,
        ]);
        $duplicateMovieAka->setRawAttributes([
            'id' => 100,
            'movie_id' => 1,
            'text' => 'The Matrix',
            'country_code' => 'US',
            'language_code' => 'en',
            'position' => 1,
        ], sync: true);

        $secondMovieAka = new MovieAka([
            'movie_id' => 1,
            'text' => 'Matrix',
            'country_code' => 'GB',
            'language_code' => 'en',
            'position' => 2,
        ]);
        $secondMovieAka->setRawAttributes([
            'id' => 101,
            'movie_id' => 1,
            'text' => 'Matrix',
            'country_code' => 'GB',
            'language_code' => 'en',
            'position' => 2,
        ], sync: true);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieAkas', new EloquentCollection([$firstMovieAka, $duplicateMovieAka, $secondMovieAka]));

        $resolvedMovieAkas = $title->resolvedMovieAkas();

        $this->assertCount(2, $resolvedMovieAkas);
        $this->assertSame([100, 101], $resolvedMovieAkas->pluck('id')->all());
        $this->assertSame([1, 1], $resolvedMovieAkas->pluck('movie_id')->all());
        $this->assertSame(['The Matrix', 'Matrix'], $resolvedMovieAkas->pluck('text')->all());
        $this->assertSame(['US', 'GB'], $resolvedMovieAkas->pluck('country_code')->all());
        $this->assertSame(['en', 'en'], $resolvedMovieAkas->pluck('language_code')->all());
        $this->assertSame([1, 2], $resolvedMovieAkas->pluck('position')->all());
    }

    public function test_resolved_movie_aka_attributes_returns_loaded_bridge_rows(): void
    {
        $firstMovieAkaAttribute = new MovieAkaAttribute([
            'movie_aka_id' => 100,
            'aka_attribute_id' => 11,
            'position' => 1,
        ]);

        $duplicateMovieAkaAttribute = new MovieAkaAttribute([
            'movie_aka_id' => 100,
            'aka_attribute_id' => 11,
            'position' => 1,
        ]);

        $secondMovieAkaAttribute = new MovieAkaAttribute([
            'movie_aka_id' => 101,
            'aka_attribute_id' => 24,
            'position' => 2,
        ]);

        $firstMovieAka = new MovieAka([
            'id' => 100,
            'movie_id' => 1,
            'text' => 'The Matrix',
            'position' => 1,
        ]);
        $firstMovieAka->setRelation('movieAkaAttributes', new EloquentCollection([$firstMovieAkaAttribute]));

        $secondMovieAka = new MovieAka([
            'id' => 101,
            'movie_id' => 1,
            'text' => 'Matrix',
            'position' => 2,
        ]);
        $secondMovieAka->setRelation('movieAkaAttributes', new EloquentCollection([$duplicateMovieAkaAttribute, $secondMovieAkaAttribute]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieAkas', new EloquentCollection([$firstMovieAka, $secondMovieAka]));

        $resolvedMovieAkaAttributes = $title->resolvedMovieAkaAttributes();

        $this->assertCount(2, $resolvedMovieAkaAttributes);
        $this->assertSame([100, 101], $resolvedMovieAkaAttributes->pluck('movie_aka_id')->all());
        $this->assertSame([11, 24], $resolvedMovieAkaAttributes->pluck('aka_attribute_id')->all());
        $this->assertSame([1, 2], $resolvedMovieAkaAttributes->pluck('position')->all());
    }

    public function test_resolved_aka_types_flattens_loaded_movie_aka_types(): void
    {
        $festivalType = new AkaType;
        $festivalType->setRawAttributes([
            'id' => 11,
            'name' => 'festival',
        ], sync: true);

        $festivalDisplayType = new AkaType;
        $festivalDisplayType->setRawAttributes([
            'id' => 24,
            'name' => 'imdbDisplay',
        ], sync: true);

        $firstMovieAkaType = new MovieAkaType([
            'movie_aka_id' => 100,
            'aka_type_id' => 11,
            'position' => 1,
        ]);
        $firstMovieAkaType->setRelation('akaType', $festivalType);

        $duplicateMovieAkaType = new MovieAkaType([
            'movie_aka_id' => 101,
            'aka_type_id' => 11,
            'position' => 1,
        ]);
        $duplicateMovieAkaType->setRelation('akaType', $festivalType);

        $secondMovieAkaType = new MovieAkaType([
            'movie_aka_id' => 101,
            'aka_type_id' => 24,
            'position' => 2,
        ]);
        $secondMovieAkaType->setRelation('akaType', $festivalDisplayType);

        $firstMovieAka = new MovieAka([
            'movie_id' => 1,
            'text' => 'The Matrix',
            'position' => 1,
        ]);
        $firstMovieAka->setAttribute('id', 100);
        $firstMovieAka->setRelation('movieAkaTypes', new EloquentCollection([$firstMovieAkaType]));

        $secondMovieAka = new MovieAka([
            'movie_id' => 1,
            'text' => 'Matrix',
            'position' => 2,
        ]);
        $secondMovieAka->setAttribute('id', 101);
        $secondMovieAka->setRelation('movieAkaTypes', new EloquentCollection([$duplicateMovieAkaType, $secondMovieAkaType]));

        $title = new Title(['tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setAttribute('id', 1);
        $title->setRelation('movieAkas', new EloquentCollection([$firstMovieAka, $secondMovieAka]));

        $resolvedAkaTypes = $title->resolvedAkaTypes();

        $this->assertCount(2, $resolvedAkaTypes);
        $this->assertSame([11, 24], $resolvedAkaTypes->pluck('id')->all());
        $this->assertSame(['festival', 'imdbDisplay'], $resolvedAkaTypes->pluck('name')->all());
    }

    public function test_resolved_award_categories_flattens_loaded_award_nominations(): void
    {
        $bestPicture = new AwardCategory;
        $bestPicture->setRawAttributes([
            'id' => 9,
            'name' => 'Best Picture',
        ], sync: true);

        $bestEditing = new AwardCategory;
        $bestEditing->setRawAttributes([
            'id' => 15,
            'name' => 'Best Editing',
        ], sync: true);

        $firstNomination = new AwardNomination(['movie_id' => 1, 'award_category_id' => 9]);
        $firstNomination->setAttribute('id', 100);
        $firstNomination->setRelation('awardCategory', $bestPicture);

        $duplicateNomination = new AwardNomination(['movie_id' => 1, 'award_category_id' => 9]);
        $duplicateNomination->setAttribute('id', 101);
        $duplicateNomination->setRelation('awardCategory', $bestPicture);

        $secondNomination = new AwardNomination(['movie_id' => 1, 'award_category_id' => 15]);
        $secondNomination->setAttribute('id', 102);
        $secondNomination->setRelation('awardCategory', $bestEditing);

        $title = new Title(['tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setAttribute('id', 1);
        $title->setRelation('awardNominations', new EloquentCollection([$firstNomination, $duplicateNomination, $secondNomination]));

        $resolvedAwardCategories = $title->resolvedAwardCategories();

        $this->assertCount(2, $resolvedAwardCategories);
        $this->assertSame([9, 15], $resolvedAwardCategories->pluck('id')->all());
        $this->assertSame(['Best Picture', 'Best Editing'], $resolvedAwardCategories->pluck('name')->all());
    }

    public function test_resolved_award_events_flattens_loaded_award_nominations(): void
    {
        $oscars = new AwardEvent;
        $oscars->setRawAttributes([
            'imdb_id' => 'ev0000003',
            'name' => 'Academy Awards',
        ], sync: true);

        $baftas = new AwardEvent;
        $baftas->setRawAttributes([
            'imdb_id' => 'ev0000123',
            'name' => 'BAFTA Film Awards',
        ], sync: true);

        $firstNomination = new AwardNomination(['movie_id' => 1, 'event_imdb_id' => 'ev0000003']);
        $firstNomination->setAttribute('id', 100);
        $firstNomination->setRelation('awardEvent', $oscars);

        $duplicateNomination = new AwardNomination(['movie_id' => 1, 'event_imdb_id' => 'ev0000003']);
        $duplicateNomination->setAttribute('id', 101);
        $duplicateNomination->setRelation('awardEvent', $oscars);

        $secondNomination = new AwardNomination(['movie_id' => 1, 'event_imdb_id' => 'ev0000123']);
        $secondNomination->setAttribute('id', 102);
        $secondNomination->setRelation('awardEvent', $baftas);

        $title = new Title(['tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setAttribute('id', 1);
        $title->setRelation('awardNominations', new EloquentCollection([$firstNomination, $duplicateNomination, $secondNomination]));

        $resolvedAwardEvents = $title->resolvedAwardEvents();

        $this->assertCount(2, $resolvedAwardEvents);
        $this->assertSame(['ev0000003', 'ev0000123'], $resolvedAwardEvents->pluck('imdb_id')->all());
        $this->assertSame(['Academy Awards', 'BAFTA Film Awards'], $resolvedAwardEvents->pluck('name')->all());
    }

    public function test_resolved_movie_award_nomination_nominees_returns_loaded_bridge_rows(): void
    {
        $firstNominee = new MovieAwardNominationNominee([
            'movie_award_nomination_id' => 100,
            'name_basic_id' => 10,
            'position' => 1,
        ]);

        $duplicateNominee = new MovieAwardNominationNominee([
            'movie_award_nomination_id' => 100,
            'name_basic_id' => 10,
            'position' => 1,
        ]);

        $secondNominee = new MovieAwardNominationNominee([
            'movie_award_nomination_id' => 101,
            'name_basic_id' => 11,
            'position' => 2,
        ]);

        $firstNomination = new AwardNomination(['id' => 100, 'movie_id' => 1, 'position' => 1]);
        $firstNomination->setRelation('movieAwardNominationNominees', new EloquentCollection([$firstNominee]));

        $secondNomination = new AwardNomination(['id' => 101, 'movie_id' => 1, 'position' => 2]);
        $secondNomination->setRelation('movieAwardNominationNominees', new EloquentCollection([$duplicateNominee, $secondNominee]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('awardNominations', new EloquentCollection([$firstNomination, $secondNomination]));

        $resolvedNominees = $title->resolvedMovieAwardNominationNominees();

        $this->assertCount(2, $resolvedNominees);
        $this->assertSame([100, 101], $resolvedNominees->pluck('movie_award_nomination_id')->all());
        $this->assertSame([10, 11], $resolvedNominees->pluck('name_basic_id')->all());
        $this->assertSame([1, 2], $resolvedNominees->pluck('position')->all());
        $this->assertSame([100, 101], $resolvedNominees->map(fn (MovieAwardNominationNominee $nominee): ?int => $nominee->awardNomination?->id)->all());
    }

    public function test_resolved_movie_award_nominations_returns_loaded_rows(): void
    {
        $firstNomination = new AwardNomination([
            'movie_id' => 1,
            'event_imdb_id' => 'ev0000003',
            'award_category_id' => 12,
            'award_year' => 2000,
            'text' => 'Best Film Editing',
            'is_winner' => 1,
            'winner_rank' => 1,
            'position' => 1,
        ]);
        $firstNomination->setRawAttributes([
            'id' => 100,
            'movie_id' => 1,
            'event_imdb_id' => 'ev0000003',
            'award_category_id' => 12,
            'award_year' => 2000,
            'text' => 'Best Film Editing',
            'is_winner' => 1,
            'winner_rank' => 1,
            'position' => 1,
        ], sync: true);

        $duplicateNomination = new AwardNomination([
            'movie_id' => 1,
            'event_imdb_id' => 'ev0000003',
            'award_category_id' => 12,
            'award_year' => 2000,
            'text' => 'Best Film Editing',
            'is_winner' => 1,
            'winner_rank' => 1,
            'position' => 1,
        ]);
        $duplicateNomination->setRawAttributes([
            'id' => 100,
            'movie_id' => 1,
            'event_imdb_id' => 'ev0000003',
            'award_category_id' => 12,
            'award_year' => 2000,
            'text' => 'Best Film Editing',
            'is_winner' => 1,
            'winner_rank' => 1,
            'position' => 1,
        ], sync: true);

        $secondNomination = new AwardNomination([
            'movie_id' => 1,
            'event_imdb_id' => 'ev0000123',
            'award_category_id' => 14,
            'award_year' => 2000,
            'text' => 'Best Sound',
            'is_winner' => 0,
            'winner_rank' => null,
            'position' => 2,
        ]);
        $secondNomination->setRawAttributes([
            'id' => 101,
            'movie_id' => 1,
            'event_imdb_id' => 'ev0000123',
            'award_category_id' => 14,
            'award_year' => 2000,
            'text' => 'Best Sound',
            'is_winner' => 0,
            'winner_rank' => null,
            'position' => 2,
        ], sync: true);

        $title = new Title(['tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setAttribute('id', 1);
        $title->setRelation('awardNominations', new EloquentCollection([$firstNomination, $duplicateNomination, $secondNomination]));

        $resolvedNominations = $title->resolvedMovieAwardNominations();

        $this->assertCount(2, $resolvedNominations);
        $this->assertSame([100, 101], $resolvedNominations->pluck('id')->all());
        $this->assertSame([1, 1], $resolvedNominations->pluck('movie_id')->all());
        $this->assertSame(['ev0000003', 'ev0000123'], $resolvedNominations->pluck('event_imdb_id')->all());
        $this->assertSame([12, 14], $resolvedNominations->pluck('award_category_id')->all());
        $this->assertSame([2000, 2000], $resolvedNominations->pluck('award_year')->all());
        $this->assertSame(['Best Film Editing', 'Best Sound'], $resolvedNominations->pluck('text')->all());
        $this->assertSame([true, false], $resolvedNominations->pluck('is_winner')->all());
        $this->assertSame([1, null], $resolvedNominations->pluck('winner_rank')->all());
        $this->assertSame([1, 2], $resolvedNominations->pluck('position')->all());
    }

    public function test_resolved_movie_award_nomination_summaries_returns_loaded_rows(): void
    {
        $summary = new MovieAwardNominationSummary([
            'movie_id' => 1,
            'nomination_count' => 12,
            'win_count' => 4,
            'next_page_token' => 'award-page-2',
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('awardNominationSummary', $summary);

        $resolvedSummaries = $title->resolvedMovieAwardNominationSummaries();

        $this->assertCount(1, $resolvedSummaries);
        $this->assertSame([1], $resolvedSummaries->pluck('movie_id')->all());
        $this->assertSame([12], $resolvedSummaries->pluck('nomination_count')->all());
        $this->assertSame([4], $resolvedSummaries->pluck('win_count')->all());
        $this->assertSame(['award-page-2'], $resolvedSummaries->pluck('next_page_token')->all());
    }

    public function test_resolved_movie_certificate_summaries_returns_loaded_rows(): void
    {
        $summary = new MovieCertificateSummary([
            'movie_id' => 1,
            'total_count' => 3,
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('certificateSummary', $summary);

        $resolvedSummaries = $title->resolvedMovieCertificateSummaries();

        $this->assertCount(1, $resolvedSummaries);
        $this->assertSame([1], $resolvedSummaries->pluck('movie_id')->all());
        $this->assertSame([3], $resolvedSummaries->pluck('total_count')->all());
    }

    public function test_resolved_movie_certificates_returns_loaded_rows(): void
    {
        $firstCertificate = new MovieCertificate([
            'movie_id' => 1,
            'certificate_rating_id' => 4,
            'country_code' => 'IR',
            'position' => 1,
        ]);
        $firstCertificate->setRawAttributes([
            'id' => 50,
            'movie_id' => 1,
            'certificate_rating_id' => 4,
            'country_code' => 'IR',
            'position' => 1,
        ], sync: true);

        $duplicateCertificate = new MovieCertificate([
            'movie_id' => 1,
            'certificate_rating_id' => 4,
            'country_code' => 'IR',
            'position' => 2,
        ]);
        $duplicateCertificate->setRawAttributes([
            'id' => 51,
            'movie_id' => 1,
            'certificate_rating_id' => 4,
            'country_code' => 'IR',
            'position' => 2,
        ], sync: true);

        $secondCertificate = new MovieCertificate([
            'movie_id' => 1,
            'certificate_rating_id' => 4,
            'country_code' => 'IE',
            'position' => 3,
        ]);
        $secondCertificate->setRawAttributes([
            'id' => 52,
            'movie_id' => 1,
            'certificate_rating_id' => 4,
            'country_code' => 'IE',
            'position' => 3,
        ], sync: true);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('certificateRecords', new EloquentCollection([$firstCertificate, $duplicateCertificate, $secondCertificate]));

        $resolvedCertificates = $title->resolvedMovieCertificates();

        $this->assertCount(2, $resolvedCertificates);
        $this->assertSame([50, 52], $resolvedCertificates->pluck('id')->all());
        $this->assertSame([1, 1], $resolvedCertificates->pluck('movie_id')->all());
        $this->assertSame([4, 4], $resolvedCertificates->pluck('certificate_rating_id')->all());
        $this->assertSame(['IR', 'IE'], $resolvedCertificates->pluck('country_code')->all());
        $this->assertSame([1, 3], $resolvedCertificates->pluck('position')->all());
    }

    public function test_resolved_movie_award_nomination_titles_returns_loaded_bridge_rows(): void
    {
        $firstNominationTitle = new MovieAwardNominationTitle([
            'movie_award_nomination_id' => 100,
            'nominated_movie_id' => 10,
            'position' => 1,
        ]);

        $duplicateNominationTitle = new MovieAwardNominationTitle([
            'movie_award_nomination_id' => 100,
            'nominated_movie_id' => 10,
            'position' => 1,
        ]);

        $secondNominationTitle = new MovieAwardNominationTitle([
            'movie_award_nomination_id' => 101,
            'nominated_movie_id' => 11,
            'position' => 2,
        ]);

        $firstNomination = new AwardNomination(['id' => 100, 'movie_id' => 1, 'position' => 1]);
        $firstNomination->setRelation('movieAwardNominationTitles', new EloquentCollection([$firstNominationTitle]));

        $secondNomination = new AwardNomination(['id' => 101, 'movie_id' => 1, 'position' => 2]);
        $secondNomination->setRelation('movieAwardNominationTitles', new EloquentCollection([$duplicateNominationTitle, $secondNominationTitle]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('awardNominations', new EloquentCollection([$firstNomination, $secondNomination]));

        $resolvedTitles = $title->resolvedMovieAwardNominationTitles();

        $this->assertCount(2, $resolvedTitles);
        $this->assertSame([100, 101], $resolvedTitles->pluck('movie_award_nomination_id')->all());
        $this->assertSame([10, 11], $resolvedTitles->pluck('nominated_movie_id')->all());
        $this->assertSame([1, 2], $resolvedTitles->pluck('position')->all());
        $this->assertSame([100, 101], $resolvedTitles->map(fn (MovieAwardNominationTitle $nominationTitle): ?int => $nominationTitle->awardNomination?->id)->all());
    }

    public function test_resolved_certificate_attributes_flattens_loaded_certificate_relations(): void
    {
        $violence = new CertificateAttribute;
        $violence->setRawAttributes([
            'id' => 3,
            'name' => 'violence',
        ], sync: true);

        $language = new CertificateAttribute;
        $language->setRawAttributes([
            'id' => 7,
            'name' => 'language',
        ], sync: true);

        $firstMovieCertificateAttribute = new MovieCertificateAttribute([
            'movie_certificate_id' => 50,
            'certificate_attribute_id' => 3,
            'position' => 1,
        ]);
        $firstMovieCertificateAttribute->setRelation('certificateAttribute', $violence);

        $duplicateMovieCertificateAttribute = new MovieCertificateAttribute([
            'movie_certificate_id' => 51,
            'certificate_attribute_id' => 3,
            'position' => 2,
        ]);
        $duplicateMovieCertificateAttribute->setRelation('certificateAttribute', $violence);

        $secondMovieCertificateAttribute = new MovieCertificateAttribute([
            'movie_certificate_id' => 51,
            'certificate_attribute_id' => 7,
            'position' => 1,
        ]);
        $secondMovieCertificateAttribute->setRelation('certificateAttribute', $language);

        $firstCertificate = new MovieCertificate(['id' => 50, 'movie_id' => 1, 'position' => 1]);
        $firstCertificate->setRelation('movieCertificateAttributes', new EloquentCollection([$firstMovieCertificateAttribute]));

        $secondCertificate = new MovieCertificate(['id' => 51, 'movie_id' => 1, 'position' => 2]);
        $secondCertificate->setRelation('movieCertificateAttributes', new EloquentCollection([$duplicateMovieCertificateAttribute, $secondMovieCertificateAttribute]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('certificateRecords', new EloquentCollection([$firstCertificate, $secondCertificate]));

        $resolvedCertificateAttributes = $title->resolvedCertificateAttributes();

        $this->assertCount(2, $resolvedCertificateAttributes);
        $this->assertSame([3, 7], $resolvedCertificateAttributes->pluck('id')->all());
        $this->assertSame(['violence', 'language'], $resolvedCertificateAttributes->pluck('name')->all());
    }

    public function test_resolved_movie_certificate_attributes_returns_loaded_bridge_rows(): void
    {
        $firstMovieCertificateAttribute = new MovieCertificateAttribute([
            'movie_certificate_id' => 50,
            'certificate_attribute_id' => 3,
            'position' => 1,
        ]);
        $firstMovieCertificateAttribute->setRawAttributes([
            'movie_certificate_id' => 50,
            'certificate_attribute_id' => 3,
            'position' => 1,
        ], sync: true);

        $duplicateMovieCertificateAttribute = new MovieCertificateAttribute([
            'movie_certificate_id' => 50,
            'certificate_attribute_id' => 3,
            'position' => 1,
        ]);
        $duplicateMovieCertificateAttribute->setRawAttributes([
            'movie_certificate_id' => 50,
            'certificate_attribute_id' => 3,
            'position' => 1,
        ], sync: true);

        $secondMovieCertificateAttribute = new MovieCertificateAttribute([
            'movie_certificate_id' => 51,
            'certificate_attribute_id' => 7,
            'position' => 2,
        ]);
        $secondMovieCertificateAttribute->setRawAttributes([
            'movie_certificate_id' => 51,
            'certificate_attribute_id' => 7,
            'position' => 2,
        ], sync: true);

        $firstCertificate = new MovieCertificate(['id' => 50, 'movie_id' => 1, 'position' => 1]);
        $firstCertificate->setRelation('movieCertificateAttributes', new EloquentCollection([$firstMovieCertificateAttribute, $duplicateMovieCertificateAttribute]));

        $secondCertificate = new MovieCertificate(['id' => 51, 'movie_id' => 1, 'position' => 2]);
        $secondCertificate->setRelation('movieCertificateAttributes', new EloquentCollection([$secondMovieCertificateAttribute]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('certificateRecords', new EloquentCollection([$firstCertificate, $secondCertificate]));

        $resolvedMovieCertificateAttributes = $title->resolvedMovieCertificateAttributes();

        $this->assertCount(2, $resolvedMovieCertificateAttributes);
        $this->assertSame([50, 51], $resolvedMovieCertificateAttributes->pluck('movie_certificate_id')->all());
        $this->assertSame([3, 7], $resolvedMovieCertificateAttributes->pluck('certificate_attribute_id')->all());
        $this->assertSame([1, 2], $resolvedMovieCertificateAttributes->pluck('position')->all());
        $this->assertSame([50, 51], $resolvedMovieCertificateAttributes->map(fn (MovieCertificateAttribute $attribute): ?int => $attribute->movieCertificate?->id)->all());
    }

    public function test_resolved_certificate_ratings_flattens_loaded_certificate_relations(): void
    {
        $pg13 = new CertificateRating;
        $pg13->setRawAttributes([
            'id' => 4,
            'name' => 'PG-13',
        ], sync: true);

        $rRated = new CertificateRating;
        $rRated->setRawAttributes([
            'id' => 8,
            'name' => 'R',
        ], sync: true);

        $firstCertificate = new MovieCertificate(['id' => 50, 'movie_id' => 1, 'certificate_rating_id' => 4, 'position' => 1]);
        $firstCertificate->setRelation('certificateRating', $pg13);

        $duplicateCertificate = new MovieCertificate(['id' => 51, 'movie_id' => 1, 'certificate_rating_id' => 4, 'position' => 2]);
        $duplicateCertificate->setRelation('certificateRating', $pg13);

        $secondCertificate = new MovieCertificate(['id' => 52, 'movie_id' => 1, 'certificate_rating_id' => 8, 'position' => 3]);
        $secondCertificate->setRelation('certificateRating', $rRated);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('certificateRecords', new EloquentCollection([$firstCertificate, $duplicateCertificate, $secondCertificate]));

        $resolvedCertificateRatings = $title->resolvedCertificateRatings();

        $this->assertCount(2, $resolvedCertificateRatings);
        $this->assertSame([4, 8], $resolvedCertificateRatings->pluck('id')->all());
        $this->assertSame(['PG-13', 'R'], $resolvedCertificateRatings->pluck('name')->all());
    }

    public function test_resolved_companies_flattens_loaded_company_credit_relations(): void
    {
        $warnerBros = new Company;
        $warnerBros->setRawAttributes([
            'imdb_id' => 'co0002663',
            'name' => 'Warner Bros.',
        ], sync: true);

        $villageRoadshow = new Company;
        $villageRoadshow->setRawAttributes([
            'imdb_id' => 'co0046718',
            'name' => 'Village Roadshow Pictures',
        ], sync: true);

        $firstCompanyCredit = new MovieCompanyCredit([
            'id' => 50,
            'movie_id' => 1,
            'company_imdb_id' => 'co0002663',
            'position' => 1,
        ]);
        $firstCompanyCredit->setRelation('company', $warnerBros);

        $duplicateCompanyCredit = new MovieCompanyCredit([
            'id' => 51,
            'movie_id' => 1,
            'company_imdb_id' => 'co0002663',
            'position' => 2,
        ]);
        $duplicateCompanyCredit->setRelation('company', $warnerBros);

        $secondCompanyCredit = new MovieCompanyCredit([
            'id' => 52,
            'movie_id' => 1,
            'company_imdb_id' => 'co0046718',
            'position' => 3,
        ]);
        $secondCompanyCredit->setRelation('company', $villageRoadshow);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieCompanyCredits', new EloquentCollection([$firstCompanyCredit, $duplicateCompanyCredit, $secondCompanyCredit]));

        $resolvedCompanies = $title->resolvedCompanies();

        $this->assertCount(2, $resolvedCompanies);
        $this->assertSame(['co0002663', 'co0046718'], $resolvedCompanies->pluck('imdb_id')->all());
        $this->assertSame(['Warner Bros.', 'Village Roadshow Pictures'], $resolvedCompanies->pluck('name')->all());
    }

    public function test_resolved_movie_company_credits_returns_loaded_rows(): void
    {
        $firstCompanyCredit = new MovieCompanyCredit([
            'movie_id' => 1,
            'company_imdb_id' => 'co0002663',
            'company_credit_category_id' => 2,
            'start_year' => 1999,
            'end_year' => 1999,
            'position' => 1,
        ]);
        $firstCompanyCredit->setRawAttributes([
            'id' => 50,
            'movie_id' => 1,
            'company_imdb_id' => 'co0002663',
            'company_credit_category_id' => 2,
            'start_year' => 1999,
            'end_year' => 1999,
            'position' => 1,
        ], sync: true);

        $duplicateCompanyCredit = new MovieCompanyCredit([
            'movie_id' => 1,
            'company_imdb_id' => 'co0002663',
            'company_credit_category_id' => 2,
            'start_year' => 1999,
            'end_year' => 1999,
            'position' => 1,
        ]);
        $duplicateCompanyCredit->setRawAttributes([
            'id' => 50,
            'movie_id' => 1,
            'company_imdb_id' => 'co0002663',
            'company_credit_category_id' => 2,
            'start_year' => 1999,
            'end_year' => 1999,
            'position' => 1,
        ], sync: true);

        $secondCompanyCredit = new MovieCompanyCredit([
            'movie_id' => 1,
            'company_imdb_id' => 'co0046718',
            'company_credit_category_id' => 5,
            'start_year' => 1998,
            'end_year' => 2000,
            'position' => 2,
        ]);
        $secondCompanyCredit->setRawAttributes([
            'id' => 51,
            'movie_id' => 1,
            'company_imdb_id' => 'co0046718',
            'company_credit_category_id' => 5,
            'start_year' => 1998,
            'end_year' => 2000,
            'position' => 2,
        ], sync: true);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieCompanyCredits', new EloquentCollection([$firstCompanyCredit, $duplicateCompanyCredit, $secondCompanyCredit]));

        $resolvedMovieCompanyCredits = $title->resolvedMovieCompanyCredits();

        $this->assertCount(2, $resolvedMovieCompanyCredits);
        $this->assertSame([50, 51], $resolvedMovieCompanyCredits->pluck('id')->all());
        $this->assertSame([1, 1], $resolvedMovieCompanyCredits->pluck('movie_id')->all());
        $this->assertSame(['co0002663', 'co0046718'], $resolvedMovieCompanyCredits->pluck('company_imdb_id')->all());
        $this->assertSame([2, 5], $resolvedMovieCompanyCredits->pluck('company_credit_category_id')->all());
        $this->assertSame([1999, 1998], $resolvedMovieCompanyCredits->pluck('start_year')->all());
        $this->assertSame([1999, 2000], $resolvedMovieCompanyCredits->pluck('end_year')->all());
        $this->assertSame([1, 2], $resolvedMovieCompanyCredits->pluck('position')->all());
    }

    public function test_resolved_movie_directors_returns_loaded_rows(): void
    {
        $firstMovieDirector = new MovieDirector([
            'movie_id' => 1,
            'name_basic_id' => 10,
            'position' => 1,
        ]);

        $duplicateMovieDirector = new MovieDirector([
            'movie_id' => 1,
            'name_basic_id' => 10,
            'position' => 1,
        ]);

        $secondMovieDirector = new MovieDirector([
            'movie_id' => 1,
            'name_basic_id' => 11,
            'position' => 2,
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieDirectors', new EloquentCollection([$firstMovieDirector, $duplicateMovieDirector, $secondMovieDirector]));

        $resolvedMovieDirectors = $title->resolvedMovieDirectors();

        $this->assertCount(2, $resolvedMovieDirectors);
        $this->assertSame([1, 1], $resolvedMovieDirectors->pluck('movie_id')->all());
        $this->assertSame([10, 11], $resolvedMovieDirectors->pluck('name_basic_id')->all());
        $this->assertSame([1, 2], $resolvedMovieDirectors->pluck('position')->all());
    }

    public function test_resolved_company_credit_attributes_flattens_loaded_company_credit_relations(): void
    {
        $streaming = new CompanyCreditAttribute;
        $streaming->setRawAttributes([
            'id' => 3,
            'name' => 'streaming',
        ], sync: true);

        $imax = new CompanyCreditAttribute;
        $imax->setRawAttributes([
            'id' => 9,
            'name' => 'imax',
        ], sync: true);

        $firstCreditAttribute = new MovieCompanyCreditAttribute([
            'movie_company_credit_id' => 50,
            'company_credit_attribute_id' => 3,
            'position' => 1,
        ]);
        $firstCreditAttribute->setRelation('companyCreditAttribute', $streaming);

        $duplicateCreditAttribute = new MovieCompanyCreditAttribute([
            'movie_company_credit_id' => 51,
            'company_credit_attribute_id' => 3,
            'position' => 2,
        ]);
        $duplicateCreditAttribute->setRelation('companyCreditAttribute', $streaming);

        $secondCreditAttribute = new MovieCompanyCreditAttribute([
            'movie_company_credit_id' => 51,
            'company_credit_attribute_id' => 9,
            'position' => 1,
        ]);
        $secondCreditAttribute->setRelation('companyCreditAttribute', $imax);

        $firstCompanyCredit = new MovieCompanyCredit(['id' => 50, 'movie_id' => 1, 'position' => 1]);
        $firstCompanyCredit->setRelation('movieCompanyCreditAttributes', new EloquentCollection([$firstCreditAttribute]));

        $secondCompanyCredit = new MovieCompanyCredit(['id' => 51, 'movie_id' => 1, 'position' => 2]);
        $secondCompanyCredit->setRelation('movieCompanyCreditAttributes', new EloquentCollection([$duplicateCreditAttribute, $secondCreditAttribute]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieCompanyCredits', new EloquentCollection([$firstCompanyCredit, $secondCompanyCredit]));

        $resolvedCompanyCreditAttributes = $title->resolvedCompanyCreditAttributes();

        $this->assertCount(2, $resolvedCompanyCreditAttributes);
        $this->assertSame([3, 9], $resolvedCompanyCreditAttributes->pluck('id')->all());
        $this->assertSame(['streaming', 'imax'], $resolvedCompanyCreditAttributes->pluck('name')->all());
    }

    public function test_resolved_movie_company_credit_attributes_returns_loaded_bridge_rows(): void
    {
        $firstCreditAttribute = new MovieCompanyCreditAttribute([
            'movie_company_credit_id' => 50,
            'company_credit_attribute_id' => 3,
            'position' => 1,
        ]);

        $duplicateCreditAttribute = new MovieCompanyCreditAttribute([
            'movie_company_credit_id' => 50,
            'company_credit_attribute_id' => 3,
            'position' => 1,
        ]);

        $secondCreditAttribute = new MovieCompanyCreditAttribute([
            'movie_company_credit_id' => 51,
            'company_credit_attribute_id' => 9,
            'position' => 2,
        ]);

        $firstCompanyCredit = new MovieCompanyCredit(['id' => 50, 'movie_id' => 1, 'position' => 1]);
        $firstCompanyCredit->setRelation('movieCompanyCreditAttributes', new EloquentCollection([$firstCreditAttribute]));

        $secondCompanyCredit = new MovieCompanyCredit(['id' => 51, 'movie_id' => 1, 'position' => 2]);
        $secondCompanyCredit->setRelation('movieCompanyCreditAttributes', new EloquentCollection([$duplicateCreditAttribute, $secondCreditAttribute]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieCompanyCredits', new EloquentCollection([$firstCompanyCredit, $secondCompanyCredit]));

        $resolvedMovieCompanyCreditAttributes = $title->resolvedMovieCompanyCreditAttributes();

        $this->assertCount(2, $resolvedMovieCompanyCreditAttributes);
        $this->assertSame([50, 51], $resolvedMovieCompanyCreditAttributes->pluck('movie_company_credit_id')->all());
        $this->assertSame([3, 9], $resolvedMovieCompanyCreditAttributes->pluck('company_credit_attribute_id')->all());
        $this->assertSame([1, 2], $resolvedMovieCompanyCreditAttributes->pluck('position')->all());
        $this->assertSame([50, 51], $resolvedMovieCompanyCreditAttributes->map(fn (MovieCompanyCreditAttribute $attribute): ?int => $attribute->movieCompanyCredit?->id)->all());
    }

    public function test_resolved_movie_company_credit_countries_returns_loaded_bridge_rows(): void
    {
        $firstCreditCountry = new MovieCompanyCreditCountry([
            'movie_company_credit_id' => 50,
            'country_code' => 'US',
            'position' => 1,
        ]);

        $duplicateCreditCountry = new MovieCompanyCreditCountry([
            'movie_company_credit_id' => 50,
            'country_code' => 'US',
            'position' => 1,
        ]);

        $secondCreditCountry = new MovieCompanyCreditCountry([
            'movie_company_credit_id' => 51,
            'country_code' => 'GB',
            'position' => 2,
        ]);

        $firstCompanyCredit = new MovieCompanyCredit(['id' => 50, 'movie_id' => 1, 'position' => 1]);
        $firstCompanyCredit->setRelation('movieCompanyCreditCountries', new EloquentCollection([$firstCreditCountry]));

        $secondCompanyCredit = new MovieCompanyCredit(['id' => 51, 'movie_id' => 1, 'position' => 2]);
        $secondCompanyCredit->setRelation('movieCompanyCreditCountries', new EloquentCollection([$duplicateCreditCountry, $secondCreditCountry]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieCompanyCredits', new EloquentCollection([$firstCompanyCredit, $secondCompanyCredit]));

        $resolvedMovieCompanyCreditCountries = $title->resolvedMovieCompanyCreditCountries();

        $this->assertCount(2, $resolvedMovieCompanyCreditCountries);
        $this->assertSame([50, 51], $resolvedMovieCompanyCreditCountries->pluck('movie_company_credit_id')->all());
        $this->assertSame(['US', 'GB'], $resolvedMovieCompanyCreditCountries->pluck('country_code')->all());
        $this->assertSame([1, 2], $resolvedMovieCompanyCreditCountries->pluck('position')->all());
        $this->assertSame([50, 51], $resolvedMovieCompanyCreditCountries->map(fn (MovieCompanyCreditCountry $country): ?int => $country->movieCompanyCredit?->id)->all());
    }

    public function test_resolved_movie_company_credit_summaries_returns_loaded_rows(): void
    {
        $summary = new MovieCompanyCreditSummary([
            'movie_id' => 1,
            'total_count' => 7,
            'next_page_token' => 'cursor-2',
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('companyCreditSummary', $summary);

        $resolvedSummaries = $title->resolvedMovieCompanyCreditSummaries();

        $this->assertCount(1, $resolvedSummaries);
        $this->assertSame([1], $resolvedSummaries->pluck('movie_id')->all());
        $this->assertSame([7], $resolvedSummaries->pluck('total_count')->all());
        $this->assertSame(['cursor-2'], $resolvedSummaries->pluck('next_page_token')->all());
    }

    public function test_resolved_movie_episode_summaries_returns_loaded_rows(): void
    {
        $summary = new MovieEpisodeSummary([
            'movie_id' => 1,
            'total_count' => 12,
            'next_page_token' => 'cursor-episode-2',
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('episodeSummary', $summary);

        $resolvedSummaries = $title->resolvedMovieEpisodeSummaries();

        $this->assertCount(1, $resolvedSummaries);
        $this->assertSame([1], $resolvedSummaries->pluck('movie_id')->all());
        $this->assertSame([12], $resolvedSummaries->pluck('total_count')->all());
        $this->assertSame(['cursor-episode-2'], $resolvedSummaries->pluck('next_page_token')->all());
    }

    public function test_resolved_movie_episodes_returns_loaded_rows(): void
    {
        $firstMovieEpisode = new MovieEpisode([
            'episode_movie_id' => 101,
            'movie_id' => 1,
            'season' => 1,
            'episode_number' => 1,
            'release_year' => 1999,
            'release_month' => 3,
            'release_day' => 31,
        ]);

        $duplicateMovieEpisode = new MovieEpisode([
            'episode_movie_id' => 101,
            'movie_id' => 1,
            'season' => 1,
            'episode_number' => 1,
            'release_year' => 1999,
            'release_month' => 3,
            'release_day' => 31,
        ]);

        $secondMovieEpisode = new MovieEpisode([
            'episode_movie_id' => 102,
            'movie_id' => 1,
            'season' => 1,
            'episode_number' => 2,
            'release_year' => 1999,
            'release_month' => 4,
            'release_day' => 7,
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieEpisodes', new EloquentCollection([$firstMovieEpisode, $duplicateMovieEpisode, $secondMovieEpisode]));

        $resolvedMovieEpisodes = $title->resolvedMovieEpisodes();

        $this->assertCount(2, $resolvedMovieEpisodes);
        $this->assertSame([101, 102], $resolvedMovieEpisodes->pluck('episode_movie_id')->all());
        $this->assertSame([1, 1], $resolvedMovieEpisodes->pluck('movie_id')->all());
        $this->assertSame([1, 1], $resolvedMovieEpisodes->pluck('season')->all());
        $this->assertSame([1, 2], $resolvedMovieEpisodes->pluck('episode_number')->all());
        $this->assertSame([1999, 1999], $resolvedMovieEpisodes->pluck('release_year')->all());
        $this->assertSame([3, 4], $resolvedMovieEpisodes->pluck('release_month')->all());
        $this->assertSame([31, 7], $resolvedMovieEpisodes->pluck('release_day')->all());
    }

    public function test_resolved_movie_genres_returns_loaded_rows(): void
    {
        $firstMovieGenre = new MovieGenre([
            'movie_id' => 1,
            'genre_id' => 5,
            'position' => 1,
        ]);

        $duplicateMovieGenre = new MovieGenre([
            'movie_id' => 1,
            'genre_id' => 5,
            'position' => 1,
        ]);

        $secondMovieGenre = new MovieGenre([
            'movie_id' => 1,
            'genre_id' => 9,
            'position' => 2,
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieGenres', new EloquentCollection([$firstMovieGenre, $duplicateMovieGenre, $secondMovieGenre]));

        $resolvedMovieGenres = $title->resolvedMovieGenres();

        $this->assertCount(2, $resolvedMovieGenres);
        $this->assertSame([1, 1], $resolvedMovieGenres->pluck('movie_id')->all());
        $this->assertSame([5, 9], $resolvedMovieGenres->pluck('genre_id')->all());
        $this->assertSame([1, 2], $resolvedMovieGenres->pluck('position')->all());
    }

    public function test_resolved_movie_image_summaries_returns_loaded_rows(): void
    {
        $summary = new MovieImageSummary([
            'movie_id' => 1,
            'total_count' => 24,
            'next_page_token' => 'cursor-image-2',
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('imageSummary', $summary);

        $resolvedSummaries = $title->resolvedMovieImageSummaries();

        $this->assertCount(1, $resolvedSummaries);
        $this->assertSame([1], $resolvedSummaries->pluck('movie_id')->all());
        $this->assertSame([24], $resolvedSummaries->pluck('total_count')->all());
        $this->assertSame(['cursor-image-2'], $resolvedSummaries->pluck('next_page_token')->all());
    }

    public function test_resolved_company_credit_categories_flattens_loaded_company_credit_relations(): void
    {
        $distribution = new CompanyCreditCategory;
        $distribution->setRawAttributes([
            'id' => 2,
            'name' => 'distribution',
        ], sync: true);

        $production = new CompanyCreditCategory;
        $production->setRawAttributes([
            'id' => 5,
            'name' => 'production',
        ], sync: true);

        $firstCompanyCredit = new MovieCompanyCredit([
            'id' => 50,
            'movie_id' => 1,
            'company_credit_category_id' => 2,
            'position' => 1,
        ]);
        $firstCompanyCredit->setRelation('companyCreditCategory', $distribution);

        $duplicateCompanyCredit = new MovieCompanyCredit([
            'id' => 51,
            'movie_id' => 1,
            'company_credit_category_id' => 2,
            'position' => 2,
        ]);
        $duplicateCompanyCredit->setRelation('companyCreditCategory', $distribution);

        $secondCompanyCredit = new MovieCompanyCredit([
            'id' => 52,
            'movie_id' => 1,
            'company_credit_category_id' => 5,
            'position' => 3,
        ]);
        $secondCompanyCredit->setRelation('companyCreditCategory', $production);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('movieCompanyCredits', new EloquentCollection([$firstCompanyCredit, $duplicateCompanyCredit, $secondCompanyCredit]));

        $resolvedCompanyCreditCategories = $title->resolvedCompanyCreditCategories();

        $this->assertCount(2, $resolvedCompanyCreditCategories);
        $this->assertSame([2, 5], $resolvedCompanyCreditCategories->pluck('id')->all());
        $this->assertSame(['distribution', 'production'], $resolvedCompanyCreditCategories->pluck('name')->all());
    }

    public function test_resolved_countries_returns_loaded_country_rows(): void
    {
        $unitedStates = new Country;
        $unitedStates->setRawAttributes([
            'code' => 'US',
            'name' => 'United States',
        ], sync: true);

        $duplicateUnitedStates = new Country;
        $duplicateUnitedStates->setRawAttributes([
            'code' => 'US',
            'name' => 'United States',
        ], sync: true);

        $australia = new Country;
        $australia->setRawAttributes([
            'code' => 'AU',
            'name' => 'Australia',
        ], sync: true);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('countries', new EloquentCollection([$unitedStates, $duplicateUnitedStates, $australia]));

        $resolvedCountries = $title->resolvedCountries();

        $this->assertCount(2, $resolvedCountries);
        $this->assertSame(['US', 'AU'], $resolvedCountries->pluck('code')->all());
        $this->assertSame(['United States', 'Australia'], $resolvedCountries->pluck('name')->all());
    }

    public function test_resolved_currencies_returns_loaded_currency_rows(): void
    {
        $usd = new Currency;
        $usd->setRawAttributes([
            'code' => 'USD',
        ], sync: true);

        $aud = new Currency;
        $aud->setRawAttributes([
            'code' => 'AUD',
        ], sync: true);

        $boxOfficeRecord = new MovieBoxOffice([
            'movie_id' => 1,
            'production_budget_currency_code' => 'USD',
            'domestic_gross_currency_code' => 'USD',
            'opening_weekend_gross_currency_code' => 'AUD',
            'worldwide_gross_currency_code' => 'USD',
        ]);
        $boxOfficeRecord->setRelation('productionBudget', $usd);
        $boxOfficeRecord->setRelation('domesticGross', $usd);
        $boxOfficeRecord->setRelation('openingWeekendGross', $aud);
        $boxOfficeRecord->setRelation('worldwideGross', $usd);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('boxOfficeRecord', $boxOfficeRecord);

        $resolvedCurrencies = $title->resolvedCurrencies();

        $this->assertCount(2, $resolvedCurrencies);
        $this->assertSame(['USD', 'AUD'], $resolvedCurrencies->pluck('code')->all());
    }

    public function test_resolved_movie_box_office_rows_returns_loaded_row(): void
    {
        $boxOfficeRecord = new MovieBoxOffice([
            'movie_id' => 1,
            'domestic_gross_amount' => '171479930.00',
            'domestic_gross_currency_code' => 'USD',
            'worldwide_gross_amount' => '467222728.00',
            'worldwide_gross_currency_code' => 'USD',
            'opening_weekend_gross_amount' => '27788331.00',
            'opening_weekend_gross_currency_code' => 'USD',
            'opening_weekend_end_year' => 1999,
            'opening_weekend_end_month' => 4,
            'opening_weekend_end_day' => 4,
            'production_budget_amount' => '63000000.00',
            'production_budget_currency_code' => 'USD',
        ]);
        $boxOfficeRecord->setRawAttributes([
            'movie_id' => 1,
            'domestic_gross_amount' => '171479930.00',
            'domestic_gross_currency_code' => 'USD',
            'worldwide_gross_amount' => '467222728.00',
            'worldwide_gross_currency_code' => 'USD',
            'opening_weekend_gross_amount' => '27788331.00',
            'opening_weekend_gross_currency_code' => 'USD',
            'opening_weekend_end_year' => 1999,
            'opening_weekend_end_month' => 4,
            'opening_weekend_end_day' => 4,
            'production_budget_amount' => '63000000.00',
            'production_budget_currency_code' => 'USD',
        ], sync: true);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('boxOfficeRecord', $boxOfficeRecord);

        $resolvedBoxOfficeRows = $title->resolvedMovieBoxOfficeRows();

        $this->assertCount(1, $resolvedBoxOfficeRows);
        $this->assertSame([1], $resolvedBoxOfficeRows->pluck('movie_id')->all());
        $this->assertSame(['171479930.00'], $resolvedBoxOfficeRows->pluck('domestic_gross_amount')->all());
        $this->assertSame(['USD'], $resolvedBoxOfficeRows->pluck('domestic_gross_currency_code')->all());
        $this->assertSame(['467222728.00'], $resolvedBoxOfficeRows->pluck('worldwide_gross_amount')->all());
        $this->assertSame(['USD'], $resolvedBoxOfficeRows->pluck('worldwide_gross_currency_code')->all());
        $this->assertSame(['27788331.00'], $resolvedBoxOfficeRows->pluck('opening_weekend_gross_amount')->all());
        $this->assertSame(['USD'], $resolvedBoxOfficeRows->pluck('opening_weekend_gross_currency_code')->all());
        $this->assertSame([1999], $resolvedBoxOfficeRows->pluck('opening_weekend_end_year')->all());
        $this->assertSame([4], $resolvedBoxOfficeRows->pluck('opening_weekend_end_month')->all());
        $this->assertSame([4], $resolvedBoxOfficeRows->pluck('opening_weekend_end_day')->all());
        $this->assertSame(['63000000.00'], $resolvedBoxOfficeRows->pluck('production_budget_amount')->all());
        $this->assertSame(['USD'], $resolvedBoxOfficeRows->pluck('production_budget_currency_code')->all());
    }

    public function test_resolved_genres_returns_loaded_genre_rows(): void
    {
        $action = new Genre;
        $action->setRawAttributes([
            'id' => 1,
            'name' => 'Action',
        ], sync: true);

        $duplicateAction = new Genre;
        $duplicateAction->setRawAttributes([
            'id' => 1,
            'name' => 'Action',
        ], sync: true);

        $scienceFiction = new Genre;
        $scienceFiction->setRawAttributes([
            'id' => 9,
            'name' => 'Sci-Fi',
        ], sync: true);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('genres', new EloquentCollection([$action, $duplicateAction, $scienceFiction]));

        $resolvedGenres = $title->resolvedGenres();

        $this->assertCount(2, $resolvedGenres);
        $this->assertSame([1, 9], $resolvedGenres->pluck('id')->all());
        $this->assertSame(['Action', 'Sci-Fi'], $resolvedGenres->pluck('name')->all());
    }

    public function test_resolved_interest_categories_returns_loaded_interest_category_rows(): void
    {
        $themes = new InterestCategory;
        $themes->setRawAttributes([
            'id' => 3,
            'name' => 'Themes',
        ], sync: true);

        $keywords = new InterestCategory;
        $keywords->setRawAttributes([
            'id' => 8,
            'name' => 'Keywords',
        ], sync: true);

        $firstInterestCategoryInterest = new InterestCategoryInterest([
            'interest_category_id' => 3,
            'interest_imdb_id' => 'int0001',
            'position' => 1,
        ]);
        $firstInterestCategoryInterest->setRelation('interestCategory', $themes);

        $duplicateInterestCategoryInterest = new InterestCategoryInterest([
            'interest_category_id' => 3,
            'interest_imdb_id' => 'int0002',
            'position' => 2,
        ]);
        $duplicateInterestCategoryInterest->setRelation('interestCategory', $themes);

        $secondInterestCategoryInterest = new InterestCategoryInterest([
            'interest_category_id' => 8,
            'interest_imdb_id' => 'int0002',
            'position' => 1,
        ]);
        $secondInterestCategoryInterest->setRelation('interestCategory', $keywords);

        $firstInterest = new Interest([
            'imdb_id' => 'int0001',
            'name' => 'Cyberpunk',
        ]);
        $firstInterest->setRelation('interestCategoryInterests', new EloquentCollection([$firstInterestCategoryInterest]));

        $secondInterest = new Interest([
            'imdb_id' => 'int0002',
            'name' => 'Artificial Intelligence',
        ]);
        $secondInterest->setRelation('interestCategoryInterests', new EloquentCollection([$duplicateInterestCategoryInterest, $secondInterestCategoryInterest]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('interests', new EloquentCollection([$firstInterest, $secondInterest]));

        $resolvedInterestCategories = $title->resolvedInterestCategories();

        $this->assertCount(2, $resolvedInterestCategories);
        $this->assertSame([3, 8], $resolvedInterestCategories->pluck('id')->all());
        $this->assertSame(['Themes', 'Keywords'], $resolvedInterestCategories->pluck('name')->all());
    }

    public function test_resolved_interest_category_interests_returns_loaded_bridge_rows(): void
    {
        $firstInterestCategoryInterest = new InterestCategoryInterest([
            'interest_category_id' => 3,
            'interest_imdb_id' => 'int0001',
            'position' => 1,
        ]);

        $duplicateInterestCategoryInterest = new InterestCategoryInterest([
            'interest_category_id' => 3,
            'interest_imdb_id' => 'int0001',
            'position' => 1,
        ]);

        $secondInterestCategoryInterest = new InterestCategoryInterest([
            'interest_category_id' => 8,
            'interest_imdb_id' => 'int0002',
            'position' => 2,
        ]);

        $firstInterest = new Interest([
            'imdb_id' => 'int0001',
            'name' => 'Cyberpunk',
        ]);
        $firstInterest->setRelation('interestCategoryInterests', new EloquentCollection([$firstInterestCategoryInterest]));

        $secondInterest = new Interest([
            'imdb_id' => 'int0002',
            'name' => 'Artificial Intelligence',
        ]);
        $secondInterest->setRelation('interestCategoryInterests', new EloquentCollection([$duplicateInterestCategoryInterest, $secondInterestCategoryInterest]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('interests', new EloquentCollection([$firstInterest, $secondInterest]));

        $resolvedInterestCategoryInterests = $title->resolvedInterestCategoryInterests();

        $this->assertCount(2, $resolvedInterestCategoryInterests);
        $this->assertSame([3, 8], $resolvedInterestCategoryInterests->pluck('interest_category_id')->all());
        $this->assertSame(['int0001', 'int0002'], $resolvedInterestCategoryInterests->pluck('interest_imdb_id')->all());
        $this->assertSame([1, 2], $resolvedInterestCategoryInterests->pluck('position')->all());
    }

    public function test_resolved_interest_primary_images_returns_loaded_image_rows(): void
    {
        $firstInterestPrimaryImage = new InterestPrimaryImage([
            'interest_imdb_id' => 'int0001',
            'url' => 'https://images.example/cyberpunk.jpg',
            'width' => 640,
            'height' => 360,
            'type' => 'poster',
        ]);

        $duplicateInterestPrimaryImage = new InterestPrimaryImage([
            'interest_imdb_id' => 'int0001',
            'url' => 'https://images.example/cyberpunk.jpg',
            'width' => 640,
            'height' => 360,
            'type' => 'poster',
        ]);

        $secondInterestPrimaryImage = new InterestPrimaryImage([
            'interest_imdb_id' => 'int0002',
            'url' => 'https://images.example/ai.jpg',
            'width' => 800,
            'height' => 450,
            'type' => 'still',
        ]);

        $firstInterest = new Interest([
            'imdb_id' => 'int0001',
            'name' => 'Cyberpunk',
        ]);
        $firstInterest->setRelation('interestPrimaryImages', new EloquentCollection([$firstInterestPrimaryImage]));

        $secondInterest = new Interest([
            'imdb_id' => 'int0002',
            'name' => 'Artificial Intelligence',
        ]);
        $secondInterest->setRelation('interestPrimaryImages', new EloquentCollection([$duplicateInterestPrimaryImage, $secondInterestPrimaryImage]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('interests', new EloquentCollection([$firstInterest, $secondInterest]));

        $resolvedInterestPrimaryImages = $title->resolvedInterestPrimaryImages();

        $this->assertCount(2, $resolvedInterestPrimaryImages);
        $this->assertSame(['https://images.example/cyberpunk.jpg', 'https://images.example/ai.jpg'], $resolvedInterestPrimaryImages->pluck('url')->all());
        $this->assertSame([640, 800], $resolvedInterestPrimaryImages->pluck('width')->all());
        $this->assertSame([360, 450], $resolvedInterestPrimaryImages->pluck('height')->all());
        $this->assertSame(['poster', 'still'], $resolvedInterestPrimaryImages->pluck('type')->all());
    }

    public function test_resolved_interest_similar_interests_returns_loaded_bridge_rows(): void
    {
        $firstInterestSimilarInterest = new InterestSimilarInterest([
            'interest_imdb_id' => 'int0001',
            'similar_interest_imdb_id' => 'int0002',
            'position' => 1,
        ]);

        $duplicateInterestSimilarInterest = new InterestSimilarInterest([
            'interest_imdb_id' => 'int0001',
            'similar_interest_imdb_id' => 'int0002',
            'position' => 1,
        ]);

        $secondInterestSimilarInterest = new InterestSimilarInterest([
            'interest_imdb_id' => 'int0002',
            'similar_interest_imdb_id' => 'int0003',
            'position' => 2,
        ]);

        $firstInterest = new Interest([
            'imdb_id' => 'int0001',
            'name' => 'Cyberpunk',
        ]);
        $firstInterest->setRelation('interestSimilarInterests', new EloquentCollection([$firstInterestSimilarInterest]));

        $secondInterest = new Interest([
            'imdb_id' => 'int0002',
            'name' => 'Artificial Intelligence',
        ]);
        $secondInterest->setRelation('interestSimilarInterests', new EloquentCollection([$duplicateInterestSimilarInterest, $secondInterestSimilarInterest]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('interests', new EloquentCollection([$firstInterest, $secondInterest]));

        $resolvedInterestSimilarInterests = $title->resolvedInterestSimilarInterests();

        $this->assertCount(2, $resolvedInterestSimilarInterests);
        $this->assertSame(['int0001', 'int0002'], $resolvedInterestSimilarInterests->pluck('interest_imdb_id')->all());
        $this->assertSame(['int0002', 'int0003'], $resolvedInterestSimilarInterests->pluck('similar_interest_imdb_id')->all());
        $this->assertSame([1, 2], $resolvedInterestSimilarInterests->pluck('position')->all());
    }

    public function test_resolved_interests_returns_loaded_interest_rows(): void
    {
        $cyberpunk = new Interest([
            'imdb_id' => 'int0001',
            'name' => 'Cyberpunk',
            'description' => 'High-tech dystopian futures.',
            'is_subgenre' => true,
        ]);

        $duplicateCyberpunk = new Interest([
            'imdb_id' => 'int0001',
            'name' => 'Cyberpunk',
            'description' => 'High-tech dystopian futures.',
            'is_subgenre' => true,
        ]);

        $artificialIntelligence = new Interest([
            'imdb_id' => 'int0002',
            'name' => 'Artificial Intelligence',
            'description' => 'Stories about sentient machines.',
            'is_subgenre' => false,
        ]);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('interests', new EloquentCollection([$cyberpunk, $duplicateCyberpunk, $artificialIntelligence]));

        $resolvedInterests = $title->resolvedInterests();

        $this->assertCount(2, $resolvedInterests);
        $this->assertSame(['int0001', 'int0002'], $resolvedInterests->pluck('imdb_id')->all());
        $this->assertSame(['Cyberpunk', 'Artificial Intelligence'], $resolvedInterests->pluck('name')->all());
        $this->assertSame(['High-tech dystopian futures.', 'Stories about sentient machines.'], $resolvedInterests->pluck('description')->all());
        $this->assertSame([true, false], $resolvedInterests->pluck('is_subgenre')->all());
    }
}
