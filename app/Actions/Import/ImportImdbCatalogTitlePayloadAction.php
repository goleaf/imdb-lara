<?php

namespace App\Actions\Import;

use App\Actions\Import\Concerns\ManagesImdbImportConcurrency;
use App\Models\AkaAttribute;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\CertificateAttribute;
use App\Models\CertificateRating;
use App\Models\Company;
use App\Models\CompanyCreditAttribute;
use App\Models\CompanyCreditCategory;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Genre;
use App\Models\Interest;
use App\Models\Language;
use App\Models\Movie;
use App\Models\MovieAka;
use App\Models\MovieAkaAttribute;
use App\Models\MovieAwardNomination;
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
use App\Models\MovieGenre;
use App\Models\MovieImage;
use App\Models\MovieImageSummary;
use App\Models\MovieInterest;
use App\Models\MovieMetacritic;
use App\Models\MovieOriginCountry;
use App\Models\MovieParentsGuideReview;
use App\Models\MovieParentsGuideSection;
use App\Models\MovieParentsGuideSeverityBreakdown;
use App\Models\MoviePlot;
use App\Models\MoviePrimaryImage;
use App\Models\MovieRating;
use App\Models\MovieReleaseDate;
use App\Models\MovieReleaseDateAttribute;
use App\Models\MovieReleaseDateSummary;
use App\Models\MovieSeason;
use App\Models\MovieSpokenLanguage;
use App\Models\MovieStar;
use App\Models\MovieVideo;
use App\Models\MovieVideoPrimaryImage;
use App\Models\MovieVideoSummary;
use App\Models\MovieWriter;
use App\Models\NameBasic;
use App\Models\NameBasicPrimaryImage;
use App\Models\NameBasicProfession;
use App\Models\NameCredit;
use App\Models\NameCreditCharacter;
use App\Models\ParentsGuideCategory;
use App\Models\ParentsGuideSeverityLevel;
use App\Models\Profession;
use App\Models\ReleaseDateAttribute;
use App\Models\TitleType;
use App\Models\VideoType;
use RuntimeException;

class ImportImdbCatalogTitlePayloadAction
{
    use ManagesImdbImportConcurrency;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): Movie
    {
        $titlePayload = is_array(data_get($payload, 'title')) ? data_get($payload, 'title') : $payload;

        if (! is_array($titlePayload)) {
            throw new RuntimeException('The IMDb title payload is missing the title object.');
        }

        $imdbId = $this->requiredImdbId($titlePayload);

        /** @var Movie $movie */
        $movie = $this->runLockedImport('title', $imdbId, function () use ($payload, $titlePayload): Movie {
            $movie = $this->upsertMovie($titlePayload);

            $this->syncGenres($movie, $this->normalizeStringList(data_get($titlePayload, 'genres')));
            $this->syncInterests($movie, $this->normalizeObjectList(data_get($titlePayload, 'interests')));
            $this->syncOriginCountries($movie, $this->normalizeObjectList(data_get($titlePayload, 'originCountries')));
            $this->syncSpokenLanguages($movie, $this->normalizeObjectList(data_get($titlePayload, 'spokenLanguages')));
            $this->syncRating($movie, is_array(data_get($titlePayload, 'rating')) ? data_get($titlePayload, 'rating') : []);
            $this->syncPlot($movie, $this->nullableString(data_get($titlePayload, 'plot')));
            $this->syncPrimaryImage($movie, is_array(data_get($titlePayload, 'primaryImage')) ? data_get($titlePayload, 'primaryImage') : []);
            $this->syncMetacritic($movie, is_array(data_get($titlePayload, 'metacritic')) ? data_get($titlePayload, 'metacritic') : [], true);
            $this->syncImages($movie, is_array(data_get($payload, 'images')) ? data_get($payload, 'images') : []);
            $this->syncReleaseDates($movie, is_array(data_get($payload, 'releaseDates')) ? data_get($payload, 'releaseDates') : []);
            $this->syncAkas($movie, is_array(data_get($payload, 'akas')) ? data_get($payload, 'akas') : []);
            $this->syncSeriesRows($movie, $titlePayload, $payload);
            $this->syncVideos($movie, is_array(data_get($payload, 'videos')) ? data_get($payload, 'videos') : []);
            $this->syncAwards($movie, is_array(data_get($payload, 'awardNominations')) ? data_get($payload, 'awardNominations') : []);
            $this->syncParentsGuide($movie, is_array(data_get($payload, 'parentsGuide')) ? data_get($payload, 'parentsGuide') : []);
            $this->syncCertificates($movie, is_array(data_get($payload, 'certificates')) ? data_get($payload, 'certificates') : []);
            $this->syncCompanyCredits($movie, is_array(data_get($payload, 'companyCredits')) ? data_get($payload, 'companyCredits') : []);
            $this->syncBoxOffice($movie, is_array(data_get($payload, 'boxOffice')) ? data_get($payload, 'boxOffice') : []);
            $this->syncCredits(
                $movie,
                $this->normalizeObjectList(data_get($payload, 'credits.credits')),
                $this->normalizeObjectList(data_get($titlePayload, 'directors')),
                $this->normalizeObjectList(data_get($titlePayload, 'writers')),
                $this->normalizeObjectList(data_get($titlePayload, 'stars')),
            );

            return $movie->fresh() ?? $movie;
        });

        return $movie;
    }

    /**
     * @param  array<string, mixed>  $titlePayload
     */
    private function upsertMovie(array $titlePayload): Movie
    {
        $imdbId = $this->requiredImdbId($titlePayload);
        $typeName = $this->nullableString(data_get($titlePayload, 'type'));
        $titleType = $typeName !== null
            ? TitleType::query()->firstOrCreate(['name' => $typeName])
            : null;

        $movie = Movie::query()
            ->where('tconst', $imdbId)
            ->orWhere('imdb_id', $imdbId)
            ->first() ?? new Movie;

        $movie->fill([
            'tconst' => $imdbId,
            'imdb_id' => $imdbId,
            'titletype' => $typeName,
            'primarytitle' => $this->nullableString(data_get($titlePayload, 'primaryTitle')),
            'originaltitle' => $this->nullableString(data_get($titlePayload, 'originalTitle'))
                ?? $this->nullableString(data_get($titlePayload, 'primaryTitle')),
            'isadult' => $this->nullableBool(data_get($titlePayload, 'isAdult')) ? 1 : 0,
            'startyear' => $this->nullableInt(data_get($titlePayload, 'startYear')),
            'endyear' => $this->nullableInt(data_get($titlePayload, 'endYear')),
            'runtimeminutes' => $this->runtimeMinutes($this->nullableInt(data_get($titlePayload, 'runtimeSeconds'))),
            'runtimeSeconds' => $this->nullableInt(data_get($titlePayload, 'runtimeSeconds')),
            'genres' => $this->commaSeparatedString($this->normalizeStringList(data_get($titlePayload, 'genres'))),
            'title_type_id' => $titleType?->getKey(),
        ]);
        $movie->save();

        return $movie;
    }

    /**
     * @param  list<string>  $genreNames
     */
    private function syncGenres(Movie $movie, array $genreNames): void
    {
        MovieGenre::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($genreNames as $index => $genreName) {
            $genre = Genre::query()->firstOrCreate(['name' => $genreName]);

            MovieGenre::query()->create([
                'movie_id' => $movie->getKey(),
                'genre_id' => $genre->getKey(),
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $interests
     */
    private function syncInterests(Movie $movie, array $interests): void
    {
        MovieInterest::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($interests as $index => $interestPayload) {
            $interest = $this->upsertInterestStub($interestPayload);

            if (! $interest instanceof Interest) {
                continue;
            }

            MovieInterest::query()->create([
                'movie_id' => $movie->getKey(),
                'interest_imdb_id' => $interest->getKey(),
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $countries
     */
    private function syncOriginCountries(Movie $movie, array $countries): void
    {
        MovieOriginCountry::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($countries as $index => $countryPayload) {
            $country = $this->upsertCountryLookup($countryPayload);

            if (! $country instanceof Country) {
                continue;
            }

            MovieOriginCountry::query()->create([
                'movie_id' => $movie->getKey(),
                'country_code' => $country->getKey(),
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $languages
     */
    private function syncSpokenLanguages(Movie $movie, array $languages): void
    {
        MovieSpokenLanguage::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($languages as $index => $languagePayload) {
            $language = $this->upsertLanguageLookup($languagePayload);

            if (! $language instanceof Language) {
                continue;
            }

            MovieSpokenLanguage::query()->create([
                'movie_id' => $movie->getKey(),
                'language_code' => $language->getKey(),
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $ratingPayload
     */
    private function syncRating(Movie $movie, array $ratingPayload): void
    {
        if ($ratingPayload === []) {
            return;
        }

        MovieRating::query()->updateOrCreate(
            ['movie_id' => $movie->getKey()],
            array_filter([
                'aggregate_rating' => $this->nullableFloat(data_get($ratingPayload, 'aggregateRating')),
                'vote_count' => $this->nullableInt(data_get($ratingPayload, 'voteCount')),
            ], fn (mixed $value): bool => $value !== null),
        );
    }

    private function syncPlot(Movie $movie, ?string $plot): void
    {
        if ($plot === null) {
            return;
        }

        MoviePlot::query()->updateOrCreate(
            ['movie_id' => $movie->getKey()],
            ['plot' => $plot],
        );
    }

    /**
     * @param  array<string, mixed>  $primaryImagePayload
     */
    private function syncPrimaryImage(Movie $movie, array $primaryImagePayload): void
    {
        $url = $this->nullableString(data_get($primaryImagePayload, 'url'));

        if ($url === null) {
            return;
        }

        MoviePrimaryImage::query()->updateOrCreate(
            ['movie_id' => $movie->getKey()],
            [
                'url' => $url,
                'width' => $this->nullableInt(data_get($primaryImagePayload, 'width')),
                'height' => $this->nullableInt(data_get($primaryImagePayload, 'height')),
                'type' => $this->nullableString(data_get($primaryImagePayload, 'type')),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $metacriticPayload
     */
    private function syncMetacritic(Movie $movie, array $metacriticPayload, bool $deleteWhenMissing = false): void
    {
        $attributes = array_filter([
            'url' => $this->nullableString(data_get($metacriticPayload, 'url')),
            'score' => $this->nullableInt(data_get($metacriticPayload, 'score')),
            'review_count' => $this->nullableInt(data_get($metacriticPayload, 'reviewCount')),
        ], fn (mixed $value): bool => $value !== null);

        if ($attributes === []) {
            if ($deleteWhenMissing) {
                MovieMetacritic::query()->where('movie_id', $movie->getKey())->delete();
            }

            return;
        }

        MovieMetacritic::query()->updateOrCreate(
            ['movie_id' => $movie->getKey()],
            $attributes,
        );
    }

    /**
     * @param  array<string, mixed>  $imagesPayload
     */
    private function syncImages(Movie $movie, array $imagesPayload): void
    {
        MovieImage::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($imagesPayload, 'images')) as $index => $imagePayload) {
            $url = $this->nullableString(data_get($imagePayload, 'url'));

            if ($url === null) {
                continue;
            }

            MovieImage::query()->create([
                'movie_id' => $movie->getKey(),
                'position' => $index + 1,
                'url' => $url,
                'width' => $this->nullableInt(data_get($imagePayload, 'width')),
                'height' => $this->nullableInt(data_get($imagePayload, 'height')),
                'type' => $this->nullableString(data_get($imagePayload, 'type')),
            ]);
        }

        if ($this->nullableInt(data_get($imagesPayload, 'totalCount')) !== null) {
            MovieImageSummary::query()->updateOrCreate(
                ['movie_id' => $movie->getKey()],
                [
                    'total_count' => $this->nullableInt(data_get($imagesPayload, 'totalCount')),
                    'next_page_token' => $this->nullableString(data_get($imagesPayload, 'nextPageToken')),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $releaseDatesPayload
     */
    private function syncReleaseDates(Movie $movie, array $releaseDatesPayload): void
    {
        MovieReleaseDate::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($releaseDatesPayload, 'releaseDates')) as $index => $releaseDatePayload) {
            $country = $this->upsertCountryLookup(data_get($releaseDatePayload, 'country'));

            if (! $country instanceof Country) {
                continue;
            }

            $releaseDate = MovieReleaseDate::query()->create([
                'movie_id' => $movie->getKey(),
                'country_code' => $country->getKey(),
                'release_year' => $this->nullableInt(data_get($releaseDatePayload, 'releaseDate.year')),
                'release_month' => $this->nullableInt(data_get($releaseDatePayload, 'releaseDate.month')),
                'release_day' => $this->nullableInt(data_get($releaseDatePayload, 'releaseDate.day')),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeStringList(data_get($releaseDatePayload, 'attributes')) as $attributeIndex => $attributeName) {
                $attribute = ReleaseDateAttribute::query()->firstOrCreate(['name' => $attributeName]);

                MovieReleaseDateAttribute::query()->create([
                    'movie_release_date_id' => $releaseDate->getKey(),
                    'release_date_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ]);
            }
        }

        if ($this->nullableString(data_get($releaseDatesPayload, 'nextPageToken')) !== null) {
            MovieReleaseDateSummary::query()->updateOrCreate(
                ['movie_id' => $movie->getKey()],
                ['next_page_token' => $this->nullableString(data_get($releaseDatesPayload, 'nextPageToken'))],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $akasPayload
     */
    private function syncAkas(Movie $movie, array $akasPayload): void
    {
        $existingIds = MovieAka::query()->where('movie_id', $movie->getKey())->pluck('id');

        if ($existingIds->isNotEmpty()) {
            MovieAkaAttribute::query()->whereIn('movie_aka_id', $existingIds)->delete();
        }

        MovieAka::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($akasPayload, 'akas')) as $index => $akaPayload) {
            $text = $this->nullableString(data_get($akaPayload, 'text'));

            if ($text === null) {
                continue;
            }

            $country = $this->upsertCountryLookup(data_get($akaPayload, 'country'));
            $language = $this->upsertLanguageLookup(data_get($akaPayload, 'language'));
            $movieAka = MovieAka::query()->create([
                'movie_id' => $movie->getKey(),
                'text' => $text,
                'country_code' => $country?->getKey(),
                'language_code' => $language?->getKey(),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeStringList(data_get($akaPayload, 'attributes')) as $attributeIndex => $attributeName) {
                $attribute = AkaAttribute::query()->firstOrCreate(['name' => $attributeName]);

                MovieAkaAttribute::query()->create([
                    'movie_aka_id' => $movieAka->getKey(),
                    'aka_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $titlePayload
     * @param  array<string, mixed>  $bundle
     */
    private function syncSeriesRows(Movie $movie, array $titlePayload, array $bundle): void
    {
        if ($this->nullableString(data_get($titlePayload, 'type')) === 'movie') {
            MovieEpisode::query()->where('movie_id', $movie->getKey())->delete();
            MovieSeason::query()->where('movie_id', $movie->getKey())->delete();

            return;
        }

        MovieEpisode::query()->where('movie_id', $movie->getKey())->delete();
        MovieSeason::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($bundle, 'seasons.seasons')) as $seasonPayload) {
            MovieSeason::query()->create([
                'movie_id' => $movie->getKey(),
                'season' => (string) (data_get($seasonPayload, 'season') ?? ''),
                'episode_count' => $this->nullableInt(data_get($seasonPayload, 'episodeCount')),
            ]);
        }

        foreach ($this->normalizeObjectList(data_get($bundle, 'episodes.episodes')) as $episodePayload) {
            $episodeMovie = $this->upsertMovieStub(data_get($episodePayload, 'title'));

            if (! $episodeMovie instanceof Movie) {
                $episodeId = $this->nullableString(data_get($episodePayload, 'id'));
                $episodeMovie = $episodeId !== null
                    ? $this->upsertMovieStub([
                        'id' => $episodeId,
                        'type' => 'tvEpisode',
                        'primaryTitle' => $episodeId,
                    ])
                    : null;
            }

            if (! $episodeMovie instanceof Movie) {
                continue;
            }

            MovieEpisode::query()->create([
                'episode_movie_id' => $episodeMovie->getKey(),
                'movie_id' => $movie->getKey(),
                'season' => (string) (data_get($episodePayload, 'season') ?? ''),
                'episode_number' => $this->nullableInt(data_get($episodePayload, 'episodeNumber')),
                'release_year' => $this->nullableInt(data_get($episodePayload, 'releaseDate.year')),
                'release_month' => $this->nullableInt(data_get($episodePayload, 'releaseDate.month')),
                'release_day' => $this->nullableInt(data_get($episodePayload, 'releaseDate.day')),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $videosPayload
     */
    private function syncVideos(Movie $movie, array $videosPayload): void
    {
        $videoIds = MovieVideo::query()
            ->where('movie_id', $movie->getKey())
            ->pluck('imdb_id');

        if ($videoIds->isNotEmpty()) {
            MovieVideoPrimaryImage::query()->whereIn('video_imdb_id', $videoIds)->delete();
        }

        MovieVideo::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($videosPayload, 'videos')) as $index => $videoPayload) {
            $videoId = $this->nullableString(data_get($videoPayload, 'id'));

            if ($videoId === null) {
                continue;
            }

            $videoTypeName = $this->nullableString(data_get($videoPayload, 'type'));
            $videoType = $videoTypeName !== null
                ? VideoType::query()->firstOrCreate(['name' => $videoTypeName])
                : null;

            MovieVideo::query()->create([
                'imdb_id' => $videoId,
                'movie_id' => $movie->getKey(),
                'video_type_id' => $videoType?->getKey(),
                'name' => $this->nullableString(data_get($videoPayload, 'name')),
                'description' => $this->nullableString(data_get($videoPayload, 'description')),
                'width' => $this->nullableInt(data_get($videoPayload, 'width')),
                'height' => $this->nullableInt(data_get($videoPayload, 'height')),
                'runtime_seconds' => $this->nullableInt(data_get($videoPayload, 'runtimeSeconds')),
                'position' => $index + 1,
            ]);

            $imageUrl = $this->nullableString(data_get($videoPayload, 'primaryImage.url'));

            if ($imageUrl !== null) {
                MovieVideoPrimaryImage::query()->create([
                    'video_imdb_id' => $videoId,
                    'url' => $imageUrl,
                    'width' => $this->nullableInt(data_get($videoPayload, 'primaryImage.width')),
                    'height' => $this->nullableInt(data_get($videoPayload, 'primaryImage.height')),
                    'type' => $this->nullableString(data_get($videoPayload, 'primaryImage.type')),
                ]);
            }
        }

        if ($this->nullableInt(data_get($videosPayload, 'totalCount')) !== null) {
            MovieVideoSummary::query()->updateOrCreate(
                ['movie_id' => $movie->getKey()],
                [
                    'total_count' => $this->nullableInt(data_get($videosPayload, 'totalCount')),
                    'next_page_token' => $this->nullableString(data_get($videosPayload, 'nextPageToken')),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $awardsPayload
     */
    private function syncAwards(Movie $movie, array $awardsPayload): void
    {
        $existingIds = MovieAwardNomination::query()->where('movie_id', $movie->getKey())->pluck('id');

        if ($existingIds->isNotEmpty()) {
            MovieAwardNominationNominee::query()->whereIn('movie_award_nomination_id', $existingIds)->delete();
            MovieAwardNominationTitle::query()->whereIn('movie_award_nomination_id', $existingIds)->delete();
        }

        MovieAwardNomination::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($awardsPayload, 'awardNominations')) as $index => $awardPayload) {
            $eventId = $this->nullableString(data_get($awardPayload, 'event.id'));
            $eventName = $this->nullableString(data_get($awardPayload, 'event.name'));
            $event = $eventId !== null
                ? AwardEvent::query()->firstOrCreate(
                    ['imdb_id' => $eventId],
                    ['name' => $eventName],
                )
                : null;
            $categoryName = $this->nullableString(data_get($awardPayload, 'category'));
            $category = $categoryName !== null
                ? AwardCategory::query()->firstOrCreate(['name' => $categoryName])
                : null;

            $nomination = MovieAwardNomination::query()->create([
                'movie_id' => $movie->getKey(),
                'event_imdb_id' => $event?->getKey(),
                'award_category_id' => $category?->getKey(),
                'award_year' => $this->nullableInt(data_get($awardPayload, 'year')),
                'text' => $this->nullableString(data_get($awardPayload, 'text')),
                'is_winner' => $this->nullableBool(data_get($awardPayload, 'isWinner')) ? 1 : 0,
                'winner_rank' => $this->nullableInt(data_get($awardPayload, 'winnerRank')),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeObjectList(data_get($awardPayload, 'nominees')) as $nomineeIndex => $nomineePayload) {
                $person = $this->upsertNameStub($nomineePayload, null);

                if (! $person instanceof NameBasic) {
                    continue;
                }

                MovieAwardNominationNominee::query()->create([
                    'movie_award_nomination_id' => $nomination->getKey(),
                    'name_basic_id' => $person->getKey(),
                    'position' => $nomineeIndex + 1,
                ]);
            }

            foreach ($this->normalizeObjectList(data_get($awardPayload, 'titles')) as $titleIndex => $titlePayload) {
                $nominatedMovie = $this->upsertMovieStub($titlePayload);

                if (! $nominatedMovie instanceof Movie) {
                    continue;
                }

                MovieAwardNominationTitle::query()->create([
                    'movie_award_nomination_id' => $nomination->getKey(),
                    'nominated_movie_id' => $nominatedMovie->getKey(),
                    'position' => $titleIndex + 1,
                ]);
            }
        }

        if (is_array(data_get($awardsPayload, 'stats'))) {
            MovieAwardNominationSummary::query()->updateOrCreate(
                ['movie_id' => $movie->getKey()],
                [
                    'nomination_count' => $this->nullableInt(data_get($awardsPayload, 'stats.nominations'))
                        ?? $this->nullableInt(data_get($awardsPayload, 'stats.nominationCount')),
                    'win_count' => $this->nullableInt(data_get($awardsPayload, 'stats.wins'))
                        ?? $this->nullableInt(data_get($awardsPayload, 'stats.winCount')),
                    'next_page_token' => $this->nullableString(data_get($awardsPayload, 'nextPageToken')),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $parentsGuidePayload
     */
    private function syncParentsGuide(Movie $movie, array $parentsGuidePayload): void
    {
        $existingIds = MovieParentsGuideSection::query()->where('movie_id', $movie->getKey())->pluck('id');

        if ($existingIds->isNotEmpty()) {
            MovieParentsGuideReview::query()->whereIn('movie_parents_guide_section_id', $existingIds)->delete();
            MovieParentsGuideSeverityBreakdown::query()->whereIn('movie_parents_guide_section_id', $existingIds)->delete();
        }

        MovieParentsGuideSection::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($parentsGuidePayload, 'parentsGuide')) as $index => $sectionPayload) {
            $categoryName = $this->nullableString(data_get($sectionPayload, 'category'));

            if ($categoryName === null) {
                continue;
            }

            $category = ParentsGuideCategory::query()->firstOrCreate(['code' => $categoryName]);
            $section = MovieParentsGuideSection::query()->create([
                'movie_id' => $movie->getKey(),
                'parents_guide_category_id' => $category->getKey(),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeObjectList(data_get($sectionPayload, 'severityBreakdowns')) as $severityIndex => $severityPayload) {
                $severityName = $this->nullableString(data_get($severityPayload, 'severityLevel'));

                if ($severityName === null) {
                    continue;
                }

                $severityLevel = ParentsGuideSeverityLevel::query()->firstOrCreate(['name' => $severityName]);

                MovieParentsGuideSeverityBreakdown::query()->create([
                    'movie_parents_guide_section_id' => $section->getKey(),
                    'parents_guide_severity_level_id' => $severityLevel->getKey(),
                    'vote_count' => $this->nullableInt(data_get($severityPayload, 'voteCount')) ?? 0,
                    'position' => $severityIndex + 1,
                ]);
            }

            foreach ($this->normalizeObjectList(data_get($sectionPayload, 'reviews')) as $reviewIndex => $reviewPayload) {
                $text = $this->nullableString(data_get($reviewPayload, 'text'));

                if ($text === null) {
                    continue;
                }

                MovieParentsGuideReview::query()->create([
                    'movie_parents_guide_section_id' => $section->getKey(),
                    'text' => $text,
                    'is_spoiler' => $this->nullableBool(data_get($reviewPayload, 'isSpoiler')) ? 1 : 0,
                    'position' => $reviewIndex + 1,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $certificatesPayload
     */
    private function syncCertificates(Movie $movie, array $certificatesPayload): void
    {
        $existingIds = MovieCertificate::query()->where('movie_id', $movie->getKey())->pluck('id');

        if ($existingIds->isNotEmpty()) {
            MovieCertificateAttribute::query()->whereIn('movie_certificate_id', $existingIds)->delete();
        }

        MovieCertificate::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($certificatesPayload, 'certificates')) as $index => $certificatePayload) {
            $ratingName = $this->nullableString(data_get($certificatePayload, 'rating'));
            $rating = $ratingName !== null
                ? CertificateRating::query()->firstOrCreate(['name' => $ratingName])
                : null;
            $country = $this->upsertCountryLookup(data_get($certificatePayload, 'country'));
            $certificate = MovieCertificate::query()->create([
                'movie_id' => $movie->getKey(),
                'certificate_rating_id' => $rating?->getKey(),
                'country_code' => $country?->getKey(),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeStringList(data_get($certificatePayload, 'attributes')) as $attributeIndex => $attributeName) {
                $attribute = CertificateAttribute::query()->firstOrCreate(['name' => $attributeName]);

                MovieCertificateAttribute::query()->create([
                    'movie_certificate_id' => $certificate->getKey(),
                    'certificate_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ]);
            }
        }

        if ($this->nullableInt(data_get($certificatesPayload, 'totalCount')) !== null) {
            MovieCertificateSummary::query()->updateOrCreate(
                ['movie_id' => $movie->getKey()],
                ['total_count' => $this->nullableInt(data_get($certificatesPayload, 'totalCount'))],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $companyCreditsPayload
     */
    private function syncCompanyCredits(Movie $movie, array $companyCreditsPayload): void
    {
        $existingIds = MovieCompanyCredit::query()->where('movie_id', $movie->getKey())->pluck('id');

        if ($existingIds->isNotEmpty()) {
            MovieCompanyCreditAttribute::query()->whereIn('movie_company_credit_id', $existingIds)->delete();
            MovieCompanyCreditCountry::query()->whereIn('movie_company_credit_id', $existingIds)->delete();
        }

        MovieCompanyCredit::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($companyCreditsPayload, 'companyCredits')) as $index => $creditPayload) {
            $companyId = $this->nullableString(data_get($creditPayload, 'company.id'));
            $company = $companyId !== null
                ? Company::query()->firstOrCreate(
                    ['imdb_id' => $companyId],
                    ['name' => $this->nullableString(data_get($creditPayload, 'company.name'))],
                )
                : null;
            $categoryName = $this->nullableString(data_get($creditPayload, 'category'));
            $category = $categoryName !== null
                ? CompanyCreditCategory::query()->firstOrCreate(['name' => $categoryName])
                : null;
            $movieCompanyCredit = MovieCompanyCredit::query()->create([
                'movie_id' => $movie->getKey(),
                'company_imdb_id' => $company?->getKey(),
                'company_credit_category_id' => $category?->getKey(),
                'start_year' => $this->nullableInt(data_get($creditPayload, 'yearsInvolved.startYear')),
                'end_year' => $this->nullableInt(data_get($creditPayload, 'yearsInvolved.endYear')),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeObjectList(data_get($creditPayload, 'countries')) as $countryIndex => $countryPayload) {
                $country = $this->upsertCountryLookup($countryPayload);

                if (! $country instanceof Country) {
                    continue;
                }

                MovieCompanyCreditCountry::query()->create([
                    'movie_company_credit_id' => $movieCompanyCredit->getKey(),
                    'country_code' => $country->getKey(),
                    'position' => $countryIndex + 1,
                ]);
            }

            foreach ($this->normalizeStringList(data_get($creditPayload, 'attributes')) as $attributeIndex => $attributeName) {
                $attribute = CompanyCreditAttribute::query()->firstOrCreate(['name' => $attributeName]);

                MovieCompanyCreditAttribute::query()->create([
                    'movie_company_credit_id' => $movieCompanyCredit->getKey(),
                    'company_credit_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ]);
            }
        }

        if ($this->nullableInt(data_get($companyCreditsPayload, 'totalCount')) !== null) {
            MovieCompanyCreditSummary::query()->updateOrCreate(
                ['movie_id' => $movie->getKey()],
                [
                    'total_count' => $this->nullableInt(data_get($companyCreditsPayload, 'totalCount')),
                    'next_page_token' => $this->nullableString(data_get($companyCreditsPayload, 'nextPageToken')),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $boxOfficePayload
     */
    private function syncBoxOffice(Movie $movie, array $boxOfficePayload): void
    {
        if ($boxOfficePayload === []) {
            return;
        }

        $this->firstOrCreateCurrency($this->nullableString(data_get($boxOfficePayload, 'domesticGross.currency')));
        $this->firstOrCreateCurrency($this->nullableString(data_get($boxOfficePayload, 'worldwideGross.currency')));
        $this->firstOrCreateCurrency($this->nullableString(data_get($boxOfficePayload, 'openingWeekendGross.currency')));
        $this->firstOrCreateCurrency($this->nullableString(data_get($boxOfficePayload, 'productionBudget.currency')));

        MovieBoxOffice::query()->updateOrCreate(
            ['movie_id' => $movie->getKey()],
            [
                'domestic_gross_amount' => $this->nullableFloat(data_get($boxOfficePayload, 'domesticGross.amount')),
                'domestic_gross_currency_code' => $this->nullableString(data_get($boxOfficePayload, 'domesticGross.currency')),
                'worldwide_gross_amount' => $this->nullableFloat(data_get($boxOfficePayload, 'worldwideGross.amount')),
                'worldwide_gross_currency_code' => $this->nullableString(data_get($boxOfficePayload, 'worldwideGross.currency')),
                'opening_weekend_gross_amount' => $this->nullableFloat(data_get($boxOfficePayload, 'openingWeekendGross.amount')),
                'opening_weekend_gross_currency_code' => $this->nullableString(data_get($boxOfficePayload, 'openingWeekendGross.currency')),
                'opening_weekend_end_year' => $this->nullableInt(data_get($boxOfficePayload, 'openingWeekendGross.endDate.year')),
                'opening_weekend_end_month' => $this->nullableInt(data_get($boxOfficePayload, 'openingWeekendGross.endDate.month')),
                'opening_weekend_end_day' => $this->nullableInt(data_get($boxOfficePayload, 'openingWeekendGross.endDate.day')),
                'production_budget_amount' => $this->nullableFloat(data_get($boxOfficePayload, 'productionBudget.amount')),
                'production_budget_currency_code' => $this->nullableString(data_get($boxOfficePayload, 'productionBudget.currency')),
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $credits
     * @param  list<array<string, mixed>>  $directors
     * @param  list<array<string, mixed>>  $writers
     * @param  list<array<string, mixed>>  $stars
     */
    private function syncCredits(Movie $movie, array $credits, array $directors, array $writers, array $stars): void
    {
        $persistedCreditIds = [];
        $creditRows = [];
        $creditCharacters = [];

        MovieDirector::query()->where('movie_id', $movie->getKey())->delete();
        MovieWriter::query()->where('movie_id', $movie->getKey())->delete();
        MovieStar::query()->where('movie_id', $movie->getKey())->delete();

        foreach ($this->collapseTitleCredits($credits) as $creditPayload) {
            $category = $this->nullableString(data_get($creditPayload, 'category'));
            $person = $this->upsertNameStub(data_get($creditPayload, 'name'), $category);

            if (! $person instanceof NameBasic) {
                continue;
            }

            $creditKey = $this->nameCreditKey($person->getKey(), $movie->getKey(), $category);

            $creditRows[$creditKey] = [
                'name_basic_id' => $person->getKey(),
                'movie_id' => $movie->getKey(),
                'category' => $category,
                'episode_count' => $this->nullableInt(data_get($creditPayload, 'episodeCount')),
                'position' => $this->nullableInt(data_get($creditPayload, 'position')),
            ];
            $creditCharacters[$creditKey] = $this->normalizeStringList(data_get($creditPayload, 'characters'));
        }

        if ($creditRows !== []) {
            NameCredit::query()->upsert(
                array_values($creditRows),
                ['name_basic_id', 'movie_id', 'category'],
                ['episode_count', 'position'],
            );
        }

        $persistedCredits = NameCredit::query()
            ->where('movie_id', $movie->getKey())
            ->get()
            ->keyBy(fn (NameCredit $credit): string => $this->nameCreditKey(
                (int) $credit->name_basic_id,
                (int) $credit->movie_id,
                $credit->category,
            ));

        foreach ($creditCharacters as $creditKey => $characters) {
            $credit = $persistedCredits->get($creditKey);

            if (! $credit instanceof NameCredit) {
                continue;
            }

            $persistedCreditIds[] = (int) $credit->getKey();
            $this->syncNameCreditCharacters($credit, $characters);
        }

        $staleCredits = NameCredit::query()->where('movie_id', $movie->getKey());

        if ($persistedCreditIds !== []) {
            $staleCredits->whereNotIn('id', $persistedCreditIds);
        }

        $staleCreditIds = $staleCredits->pluck('id');

        if ($staleCreditIds->isNotEmpty()) {
            NameCreditCharacter::query()->whereIn('name_credit_id', $staleCreditIds)->delete();
            NameCredit::query()->whereIn('id', $staleCreditIds)->delete();
        }

        foreach ($directors as $index => $directorPayload) {
            $person = $this->upsertNameStub($directorPayload, 'director');

            if (! $person instanceof NameBasic) {
                continue;
            }

            MovieDirector::query()->create([
                'movie_id' => $movie->getKey(),
                'name_basic_id' => $person->getKey(),
                'position' => $index + 1,
            ]);
        }

        foreach ($writers as $index => $writerPayload) {
            $person = $this->upsertNameStub($writerPayload, 'writer');

            if (! $person instanceof NameBasic) {
                continue;
            }

            MovieWriter::query()->create([
                'movie_id' => $movie->getKey(),
                'name_basic_id' => $person->getKey(),
                'position' => $index + 1,
            ]);
        }

        foreach ($stars as $index => $starPayload) {
            $person = $this->upsertNameStub($starPayload, 'actor');

            if (! $person instanceof NameBasic) {
                continue;
            }

            MovieStar::query()->create([
                'movie_id' => $movie->getKey(),
                'name_basic_id' => $person->getKey(),
                'ordering' => $index + 1,
                'category' => $this->nullableString(data_get($starPayload, 'primaryProfessions.0')),
                'job' => null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|mixed  $payload
     */
    private function upsertNameStub(mixed $payload, ?string $fallbackProfession): ?NameBasic
    {
        if (! is_array($payload)) {
            return null;
        }

        $imdbId = $this->nullableString(data_get($payload, 'id'));

        if ($imdbId === null) {
            return null;
        }

        $name = NameBasic::query()
            ->where('nconst', $imdbId)
            ->orWhere('imdb_id', $imdbId)
            ->first() ?? new NameBasic;

        $displayName = $this->nullableString(data_get($payload, 'displayName'))
            ?? $this->nullableString(data_get($payload, 'name'))
            ?? $imdbId;
        $primaryProfessions = $this->normalizeStringList(data_get($payload, 'primaryProfessions'));

        if ($primaryProfessions === [] && $fallbackProfession !== null) {
            $primaryProfessions = [$fallbackProfession];
        }

        $name->fill([
            'nconst' => $imdbId,
            'imdb_id' => $imdbId,
            'displayName' => $displayName,
            'primaryname' => $this->nullableString(data_get($payload, 'primaryName')) ?? $displayName,
            'biography' => $this->nullableString(data_get($payload, 'biography')) ?? $name->biography,
            'birthName' => $this->nullableString(data_get($payload, 'birthName')) ?? $name->birthName,
            'birthyear' => $this->nullableInt(data_get($payload, 'birthDate.year')) ?? $name->birthyear,
            'birthDate_year' => $this->nullableInt(data_get($payload, 'birthDate.year')) ?? $name->birthDate_year,
            'birthDate_month' => $this->nullableInt(data_get($payload, 'birthDate.month')) ?? $name->birthDate_month,
            'birthDate_day' => $this->nullableInt(data_get($payload, 'birthDate.day')) ?? $name->birthDate_day,
            'deathyear' => $this->nullableInt(data_get($payload, 'deathDate.year')) ?? $name->deathyear,
            'deathDate_year' => $this->nullableInt(data_get($payload, 'deathDate.year')) ?? $name->deathDate_year,
            'deathDate_month' => $this->nullableInt(data_get($payload, 'deathDate.month')) ?? $name->deathDate_month,
            'deathDate_day' => $this->nullableInt(data_get($payload, 'deathDate.day')) ?? $name->deathDate_day,
            'birthLocation' => $this->nullableString(data_get($payload, 'birthLocation')) ?? $name->birthLocation,
            'deathLocation' => $this->nullableString(data_get($payload, 'deathLocation')) ?? $name->deathLocation,
            'heightCm' => $this->nullableInt(data_get($payload, 'heightCm')) ?? $name->heightCm,
            'alternativeNames' => ($alternativeNames = $this->normalizeStringList(data_get($payload, 'alternativeNames'))) !== []
                ? $this->jsonEncode($alternativeNames)
                : $name->alternativeNames,
            'primaryprofession' => $primaryProfessions !== []
                ? implode(',', $primaryProfessions)
                : $name->primaryprofession,
            'primaryProfessions' => $primaryProfessions !== []
                ? $this->jsonEncode($primaryProfessions)
                : $name->primaryProfessions,
            'primaryImage_url' => $this->nullableString(data_get($payload, 'primaryImage.url')) ?? $name->primaryImage_url,
            'primaryImage_width' => $this->nullableInt(data_get($payload, 'primaryImage.width')) ?? $name->primaryImage_width,
            'primaryImage_height' => $this->nullableInt(data_get($payload, 'primaryImage.height')) ?? $name->primaryImage_height,
        ]);
        $name->save();

        if ($this->nullableString(data_get($payload, 'primaryImage.url')) !== null) {
            NameBasicPrimaryImage::query()->updateOrCreate(
                ['name_basic_id' => $name->getKey()],
                [
                    'url' => $this->nullableString(data_get($payload, 'primaryImage.url')),
                    'width' => $this->nullableInt(data_get($payload, 'primaryImage.width')),
                    'height' => $this->nullableInt(data_get($payload, 'primaryImage.height')),
                    'type' => $this->nullableString(data_get($payload, 'primaryImage.type')) ?? 'primary',
                ],
            );
        }

        if ($primaryProfessions !== []) {
            NameBasicProfession::query()->where('name_basic_id', $name->getKey())->delete();

            foreach ($primaryProfessions as $index => $professionName) {
                $profession = Profession::query()->firstOrCreate(['name' => $professionName]);

                NameBasicProfession::query()->create([
                    'name_basic_id' => $name->getKey(),
                    'profession_id' => $profession->getKey(),
                    'position' => $index + 1,
                ]);
            }
        }

        return $name;
    }

    /**
     * @param  array<string, mixed>|mixed  $payload
     */
    private function upsertInterestStub(mixed $payload): ?Interest
    {
        if (! is_array($payload)) {
            return null;
        }

        $imdbId = $this->nullableString(data_get($payload, 'id'));

        if ($imdbId === null) {
            return null;
        }

        $interest = Interest::query()->firstOrNew(['imdb_id' => $imdbId]);
        $interest->fill([
            'imdb_id' => $imdbId,
            'name' => $this->nullableString(data_get($payload, 'name')),
            'description' => $this->nullableString(data_get($payload, 'description')),
            'is_subgenre' => $this->nullableBool(data_get($payload, 'isSubgenre')) ?? false,
        ]);
        $interest->save();

        return $interest;
    }

    /**
     * @param  array<string, mixed>|mixed  $payload
     */
    private function upsertMovieStub(mixed $payload): ?Movie
    {
        if (! is_array($payload)) {
            return null;
        }

        $imdbId = $this->nullableString(data_get($payload, 'id'));

        if ($imdbId === null) {
            return null;
        }

        $movie = Movie::query()
            ->where('tconst', $imdbId)
            ->orWhere('imdb_id', $imdbId)
            ->first() ?? new Movie;

        $typeName = $this->nullableString(data_get($payload, 'type'));
        $titleType = $typeName !== null
            ? TitleType::query()->firstOrCreate(['name' => $typeName])
            : null;

        $movie->fill([
            'tconst' => $imdbId,
            'imdb_id' => $imdbId,
            'titletype' => $typeName,
            'primarytitle' => $this->nullableString(data_get($payload, 'primaryTitle')) ?? $movie->primarytitle,
            'originaltitle' => $this->nullableString(data_get($payload, 'originalTitle'))
                ?? $this->nullableString(data_get($payload, 'primaryTitle'))
                ?? $movie->originaltitle,
            'startyear' => $this->nullableInt(data_get($payload, 'startYear')) ?? $movie->startyear,
            'runtimeSeconds' => $this->nullableInt(data_get($payload, 'runtimeSeconds')) ?? $movie->runtimeSeconds,
            'runtimeminutes' => $this->runtimeMinutes($this->nullableInt(data_get($payload, 'runtimeSeconds'))) ?? $movie->runtimeminutes,
            'genres' => ($genres = $this->normalizeStringList(data_get($payload, 'genres'))) !== []
                ? $this->commaSeparatedString($genres)
                : $movie->genres,
            'title_type_id' => $titleType?->getKey() ?? $movie->title_type_id,
        ]);
        $movie->save();

        $this->syncGenres($movie, $this->normalizeStringList(data_get($payload, 'genres')));
        $this->syncRating($movie, is_array(data_get($payload, 'rating')) ? data_get($payload, 'rating') : []);
        $this->syncMetacritic($movie, is_array(data_get($payload, 'metacritic')) ? data_get($payload, 'metacritic') : []);

        return $movie;
    }

    /**
     * @param  array<string, mixed>|mixed  $payload
     */
    private function upsertCountryLookup(mixed $payload): ?Country
    {
        if (! is_array($payload)) {
            return null;
        }

        $code = $this->nullableString(data_get($payload, 'code'));

        if ($code === null) {
            return null;
        }

        return Country::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $this->nullableString(data_get($payload, 'name'))],
        );
    }

    /**
     * @param  array<string, mixed>|mixed  $payload
     */
    private function upsertLanguageLookup(mixed $payload): ?Language
    {
        if (! is_array($payload)) {
            return null;
        }

        $code = $this->nullableString(data_get($payload, 'code'));

        if ($code === null) {
            return null;
        }

        return Language::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $this->nullableString(data_get($payload, 'name'))],
        );
    }

    private function firstOrCreateCurrency(?string $code): void
    {
        if ($code === null) {
            return;
        }

        Currency::query()->firstOrCreate(['code' => $code]);
    }

    /**
     * @param  list<array<string, mixed>>  $credits
     * @return list<array<string, mixed>>
     */
    private function collapseTitleCredits(array $credits): array
    {
        $collapsedCredits = [];

        foreach ($credits as $index => $creditPayload) {
            $namePayload = data_get($creditPayload, 'name');

            if (! is_array($namePayload)) {
                continue;
            }

            $imdbId = $this->nullableString(data_get($namePayload, 'id'));

            if ($imdbId === null) {
                continue;
            }

            $category = $this->nullableString(data_get($creditPayload, 'category'));
            $creditKey = $this->nameCreditKey($imdbId, 'movie', $category);
            $episodeCount = $this->nullableInt(data_get($creditPayload, 'episodeCount'));
            $position = $index + 1;
            $characters = $this->normalizeStringList(data_get($creditPayload, 'characters'));

            if (! array_key_exists($creditKey, $collapsedCredits)) {
                $collapsedCredits[$creditKey] = [
                    'name' => $namePayload,
                    'category' => $category,
                    'episodeCount' => $episodeCount,
                    'position' => $position,
                    'characters' => $characters,
                ];

                continue;
            }

            $collapsedCredits[$creditKey]['episodeCount'] = $this->preferLargerInt(
                $collapsedCredits[$creditKey]['episodeCount'],
                $episodeCount,
            );
            $collapsedCredits[$creditKey]['position'] = min(
                $collapsedCredits[$creditKey]['position'],
                $position,
            );
            $collapsedCredits[$creditKey]['characters'] = $this->mergeUniqueStrings(
                $collapsedCredits[$creditKey]['characters'],
                $characters,
            );
        }

        return array_values($collapsedCredits);
    }

    /**
     * @param  list<string>  $characters
     */
    private function syncNameCreditCharacters(NameCredit $credit, array $characters): void
    {
        $persistedPositions = [];

        foreach ($characters as $index => $characterName) {
            $position = $index + 1;

            $character = NameCreditCharacter::query()->firstOrNew([
                'name_credit_id' => $credit->getKey(),
                'position' => $position,
            ]);

            $character->fill([
                'character_name' => $characterName,
            ]);
            $character->save();

            $persistedPositions[] = $position;
        }

        $staleCharacters = NameCreditCharacter::query()->where('name_credit_id', $credit->getKey());

        if ($persistedPositions !== []) {
            $staleCharacters->whereNotIn('position', $persistedPositions);
        }

        $staleCharacters->delete();
    }

    /**
     * @param  list<string>  $existingValues
     * @param  list<string>  $incomingValues
     * @return list<string>
     */
    private function mergeUniqueStrings(array $existingValues, array $incomingValues): array
    {
        return $this->normalizeStringList([
            ...$existingValues,
            ...$incomingValues,
        ]);
    }

    private function preferLargerInt(?int $existingValue, ?int $incomingValue): ?int
    {
        if ($existingValue === null) {
            return $incomingValue;
        }

        if ($incomingValue === null) {
            return $existingValue;
        }

        return max($existingValue, $incomingValue);
    }

    private function nameCreditKey(int|string $nameBasicId, int|string $movieId, ?string $category): string
    {
        return sprintf(
            '%s:%s:%s',
            (string) $nameBasicId,
            (string) $movieId,
            $category ?? '__null__',
        );
    }

    /**
     * @param  array<string, mixed>  $titlePayload
     */
    private function requiredImdbId(array $titlePayload): string
    {
        $imdbId = $this->nullableString(data_get($titlePayload, 'id'));

        if ($imdbId === null) {
            throw new RuntimeException('The IMDb title payload is missing an id.');
        }

        return $imdbId;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $value): array
    {
        if (! is_iterable($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return array_values($items);
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_iterable($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $items[] = $item;
        }

        return array_values(array_unique($items));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function nullableBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes' => true,
                '0', 'false', 'no' => false,
                default => null,
            };
        }

        return null;
    }

    private function runtimeMinutes(?int $runtimeSeconds): ?int
    {
        if ($runtimeSeconds === null) {
            return null;
        }

        return (int) floor($runtimeSeconds / 60);
    }

    /**
     * @param  list<string>  $values
     */
    private function commaSeparatedString(array $values): ?string
    {
        return $values === [] ? null : implode(',', $values);
    }

    /**
     * @param  list<string>  $values
     */
    private function jsonEncode(array $values): string
    {
        return json_encode(array_values($values), JSON_THROW_ON_ERROR);
    }
}
