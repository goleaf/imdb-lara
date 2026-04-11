<?php

namespace Tests\Unit\Models;

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
use App\Models\MovieCertificate;
use App\Models\MovieCertificateAttribute;
use App\Models\MovieCompanyCredit;
use App\Models\MovieCompanyCreditAttribute;
use App\Models\Title;
use App\Models\TitleAka;
use App\Models\TitleAkaType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_resolved_aka_types_flattens_loaded_title_aka_relations(): void
    {
        $officialType = new AkaType;
        $officialType->setRawAttributes(['id' => 11, 'name' => 'official'], sync: true);

        $workingType = new AkaType;
        $workingType->setRawAttributes(['id' => 24, 'name' => 'working'], sync: true);

        $firstTitleAkaType = new TitleAkaType(['title_aka_id' => 100, 'aka_type_id' => 11, 'position' => 1]);
        $firstTitleAkaType->setRelation('akaType', $officialType);

        $duplicateTitleAkaType = new TitleAkaType(['title_aka_id' => 101, 'aka_type_id' => 11, 'position' => 2]);
        $duplicateTitleAkaType->setRelation('akaType', $officialType);

        $secondTitleAkaType = new TitleAkaType(['title_aka_id' => 101, 'aka_type_id' => 24, 'position' => 1]);
        $secondTitleAkaType->setRelation('akaType', $workingType);

        $firstTitleAka = new TitleAka(['id' => 100, 'titleid' => 'tt0133093', 'ordering' => 1, 'title' => 'The Matrix']);
        $firstTitleAka->setRelation('titleAkaTypes', new EloquentCollection([$firstTitleAkaType]));

        $secondTitleAka = new TitleAka(['id' => 101, 'titleid' => 'tt0133093', 'ordering' => 2, 'title' => 'Matrix']);
        $secondTitleAka->setRelation('titleAkaTypes', new EloquentCollection([$duplicateTitleAkaType, $secondTitleAkaType]));

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('titleAkas', new EloquentCollection([$firstTitleAka, $secondTitleAka]));

        $resolvedAkaTypes = $title->resolvedAkaTypes();

        $this->assertCount(2, $resolvedAkaTypes);
        $this->assertSame([11, 24], $resolvedAkaTypes->pluck('id')->all());
        $this->assertSame(['official', 'working'], $resolvedAkaTypes->pluck('name')->all());
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

        $firstNomination = new AwardNomination(['id' => 100, 'movie_id' => 1, 'award_category_id' => 9]);
        $firstNomination->setRelation('awardCategory', $bestPicture);

        $duplicateNomination = new AwardNomination(['id' => 101, 'movie_id' => 1, 'award_category_id' => 9]);
        $duplicateNomination->setRelation('awardCategory', $bestPicture);

        $secondNomination = new AwardNomination(['id' => 102, 'movie_id' => 1, 'award_category_id' => 15]);
        $secondNomination->setRelation('awardCategory', $bestEditing);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
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

        $firstNomination = new AwardNomination(['id' => 100, 'movie_id' => 1, 'event_imdb_id' => 'ev0000003']);
        $firstNomination->setRelation('awardEvent', $oscars);

        $duplicateNomination = new AwardNomination(['id' => 101, 'movie_id' => 1, 'event_imdb_id' => 'ev0000003']);
        $duplicateNomination->setRelation('awardEvent', $oscars);

        $secondNomination = new AwardNomination(['id' => 102, 'movie_id' => 1, 'event_imdb_id' => 'ev0000123']);
        $secondNomination->setRelation('awardEvent', $baftas);

        $title = new Title(['id' => 1, 'tconst' => 'tt0133093', 'primarytitle' => 'The Matrix']);
        $title->setRelation('awardNominations', new EloquentCollection([$firstNomination, $duplicateNomination, $secondNomination]));

        $resolvedAwardEvents = $title->resolvedAwardEvents();

        $this->assertCount(2, $resolvedAwardEvents);
        $this->assertSame(['ev0000003', 'ev0000123'], $resolvedAwardEvents->pluck('imdb_id')->all());
        $this->assertSame(['Academy Awards', 'BAFTA Film Awards'], $resolvedAwardEvents->pluck('name')->all());
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
}
