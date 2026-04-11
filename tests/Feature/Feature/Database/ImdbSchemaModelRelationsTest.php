<?php

namespace Tests\Feature\Feature\Database;

use App\Models\Country;
use App\Models\Genre;
use App\Models\Interest;
use App\Models\InterestCategory;
use App\Models\Language;
use App\Models\Movie;
use App\Models\NameBasic;
use App\Models\Person;
use App\Models\Profession;
use App\Models\Title;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class ImdbSchemaModelRelationsTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_movie_exposes_direct_catalog_relations(): void
    {
        $movie = new Movie;

        $this->assertInstanceOf(BelongsToMany::class, $movie->genres());
        $this->assertSame('movie_genres', $movie->genres()->getTable());
        $this->assertSame('movie_id', $movie->genres()->getForeignPivotKeyName());
        $this->assertSame('genre_id', $movie->genres()->getRelatedPivotKeyName());

        $this->assertInstanceOf(BelongsToMany::class, $movie->interests());
        $this->assertSame('movie_interests', $movie->interests()->getTable());
        $this->assertSame('interest_imdb_id', $movie->interests()->getRelatedPivotKeyName());

        $this->assertInstanceOf(BelongsToMany::class, $movie->countries());
        $this->assertSame('movie_origin_countries', $movie->countries()->getTable());
        $this->assertSame('country_code', $movie->countries()->getRelatedPivotKeyName());

        $this->assertInstanceOf(BelongsToMany::class, $movie->languages());
        $this->assertSame('movie_spoken_languages', $movie->languages()->getTable());
        $this->assertSame('language_code', $movie->languages()->getRelatedPivotKeyName());

        $this->assertInstanceOf(HasOne::class, $movie->movieRating());
        $this->assertSame('movie_ratings', $movie->movieRating()->getRelated()->getTable());
    }

    public function test_reference_models_expose_runtime_many_to_many_aliases(): void
    {
        $this->assertInstanceOf(BelongsToMany::class, (new Genre)->movies());

        $nameBasicProfessions = (new NameBasic)->professions();
        $this->assertInstanceOf(BelongsToMany::class, $nameBasicProfessions);
        $this->assertSame('name_basic_professions', $nameBasicProfessions->getTable());

        $professionNames = (new Profession)->nameBasics();
        $this->assertInstanceOf(BelongsToMany::class, $professionNames);
        $this->assertSame('name_basic_professions', $professionNames->getTable());

        $interestMovies = (new Interest)->movies();
        $this->assertInstanceOf(BelongsToMany::class, $interestMovies);
        $this->assertSame('movie_interests', $interestMovies->getTable());

        $interestCategories = (new Interest)->interestCategories();
        $this->assertInstanceOf(BelongsToMany::class, $interestCategories);
        $this->assertSame('interest_category_interests', $interestCategories->getTable());

        $similarInterests = (new Interest)->similarInterests();
        $this->assertInstanceOf(BelongsToMany::class, $similarInterests);
        $this->assertSame('interest_similar_interests', $similarInterests->getTable());
        $this->assertSame('similar_interest_imdb_id', $similarInterests->getRelatedPivotKeyName());

        $relatedInterests = (new Interest)->relatedInterests();
        $this->assertInstanceOf(BelongsToMany::class, $relatedInterests);
        $this->assertSame('interest_similar_interests', $relatedInterests->getTable());
        $this->assertSame('interest_imdb_id', $relatedInterests->getRelatedPivotKeyName());

        $this->assertInstanceOf(BelongsToMany::class, (new InterestCategory)->interests());
        $this->assertInstanceOf(BelongsToMany::class, (new Country)->movies());
        $this->assertInstanceOf(BelongsToMany::class, (new Language)->movies());
    }

    public function test_catalog_adapter_models_expose_direct_mysql_pivots(): void
    {
        $titleCountries = (new Title)->countries();
        $this->assertInstanceOf(BelongsToMany::class, $titleCountries);
        $this->assertSame('movie_origin_countries', $titleCountries->getTable());
        $this->assertSame('country_code', $titleCountries->getRelatedPivotKeyName());

        $titleLanguages = (new Title)->languages();
        $this->assertInstanceOf(BelongsToMany::class, $titleLanguages);
        $this->assertSame('movie_spoken_languages', $titleLanguages->getTable());
        $this->assertSame('language_code', $titleLanguages->getRelatedPivotKeyName());

        $titleInterests = (new Title)->interests();
        $this->assertInstanceOf(BelongsToMany::class, $titleInterests);
        $this->assertSame('movie_interests', $titleInterests->getTable());
        $this->assertSame('interest_imdb_id', $titleInterests->getRelatedPivotKeyName());

        $personProfessionTerms = (new Person)->professionTerms();
        $this->assertInstanceOf(BelongsToMany::class, $personProfessionTerms);
        $this->assertSame('name_basic_professions', $personProfessionTerms->getTable());
        $this->assertSame('profession_id', $personProfessionTerms->getRelatedPivotKeyName());
    }
}
