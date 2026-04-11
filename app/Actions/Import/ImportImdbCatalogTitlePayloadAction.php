<?php

namespace App\Actions\Import;

use App\Actions\Import\Concerns\BatchesCatalogImportLookups;
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
    use BatchesCatalogImportLookups;
    use ManagesImdbImportConcurrency;

    /**
     * @var array<string, Movie>
     */
    private array $movieStubCache = [];

    /**
     * @var array<string, NameBasic>
     */
    private array $nameStubCache = [];

    /**
     * @var array<string, Interest>
     */
    private array $interestStubCache = [];

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

        $this->resetImportCaches();

        try {
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
        } finally {
            $this->resetImportCaches();
        }
    }

    /**
     * @param  array<string, mixed>  $titlePayload
     */
    private function upsertMovie(array $titlePayload): Movie
    {
        $imdbId = $this->requiredImdbId($titlePayload);
        $typeName = $this->nullableString(data_get($titlePayload, 'type'));
        $titleType = $this->resolveTitleType($typeName);

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

        $genresByName = $this->resolveGenresByName($genreNames);
        $rows = [];

        foreach ($genreNames as $index => $genreName) {
            $genre = $genresByName[$genreName] ?? null;

            if (! $genre instanceof Genre) {
                continue;
            }

            $rows[] = [
                'movie_id' => $movie->getKey(),
                'genre_id' => $genre->getKey(),
                'position' => $index + 1,
            ];
        }

        if ($rows !== []) {
            MovieGenre::query()->insert($rows);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $interests
     */
    private function syncInterests(Movie $movie, array $interests): void
    {
        MovieInterest::query()->where('movie_id', $movie->getKey())->delete();

        $interestsByImdbId = $this->resolveInterestStubs($interests);
        $rows = [];

        foreach ($interests as $index => $interestPayload) {
            $interestId = $this->nullableString(data_get($interestPayload, 'id'));
            $interest = $interestId !== null
                ? ($interestsByImdbId[$interestId] ?? null)
                : null;

            if (! $interest instanceof Interest) {
                continue;
            }

            $rows[] = [
                'movie_id' => $movie->getKey(),
                'interest_imdb_id' => $interest->getKey(),
                'position' => $index + 1,
            ];
        }

        if ($rows !== []) {
            MovieInterest::query()->insert($rows);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $countries
     */
    private function syncOriginCountries(Movie $movie, array $countries): void
    {
        MovieOriginCountry::query()->where('movie_id', $movie->getKey())->delete();

        $countriesByCode = $this->resolveCountriesFromPayloads($countries);
        $rows = [];

        foreach ($countries as $index => $countryPayload) {
            $countryCode = $this->nullableString(data_get($countryPayload, 'code'));
            $country = $countryCode !== null
                ? ($countriesByCode[$countryCode] ?? null)
                : null;

            if (! $country instanceof Country) {
                continue;
            }

            $rows[] = [
                'movie_id' => $movie->getKey(),
                'country_code' => $country->getKey(),
                'position' => $index + 1,
            ];
        }

        if ($rows !== []) {
            MovieOriginCountry::query()->insert($rows);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $languages
     */
    private function syncSpokenLanguages(Movie $movie, array $languages): void
    {
        MovieSpokenLanguage::query()->where('movie_id', $movie->getKey())->delete();

        $languagesByCode = $this->resolveLanguagesFromPayloads($languages);
        $rows = [];

        foreach ($languages as $index => $languagePayload) {
            $languageCode = $this->nullableString(data_get($languagePayload, 'code'));
            $language = $languageCode !== null
                ? ($languagesByCode[$languageCode] ?? null)
                : null;

            if (! $language instanceof Language) {
                continue;
            }

            $rows[] = [
                'movie_id' => $movie->getKey(),
                'language_code' => $language->getKey(),
                'position' => $index + 1,
            ];
        }

        if ($rows !== []) {
            MovieSpokenLanguage::query()->insert($rows);
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

        $rows = [];

        foreach ($this->normalizeObjectList(data_get($imagesPayload, 'images')) as $index => $imagePayload) {
            $url = $this->nullableString(data_get($imagePayload, 'url'));

            if ($url === null) {
                continue;
            }

            $rows[] = [
                'movie_id' => $movie->getKey(),
                'position' => $index + 1,
                'url' => $url,
                'width' => $this->nullableInt(data_get($imagePayload, 'width')),
                'height' => $this->nullableInt(data_get($imagePayload, 'height')),
                'type' => $this->nullableString(data_get($imagePayload, 'type')),
            ];
        }

        if ($rows !== []) {
            MovieImage::query()->insert($rows);
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
        $existingIds = MovieReleaseDate::query()->where('movie_id', $movie->getKey())->pluck('id');

        if ($existingIds->isNotEmpty()) {
            MovieReleaseDateAttribute::query()->whereIn('movie_release_date_id', $existingIds)->delete();
        }

        MovieReleaseDate::query()->where('movie_id', $movie->getKey())->delete();

        $releaseDates = $this->normalizeObjectList(data_get($releaseDatesPayload, 'releaseDates'));
        $countriesByCode = $this->resolveCountriesFromPayloads(array_map(
            fn (array $releaseDatePayload): mixed => data_get($releaseDatePayload, 'country'),
            $releaseDates,
        ));
        $releaseDateAttributesByName = $this->resolveReleaseDateAttributesByName($this->flattenStringLists(
            array_map(
                fn (array $releaseDatePayload): array => $this->normalizeStringList(data_get($releaseDatePayload, 'attributes')),
                $releaseDates,
            ),
        ));
        $attributeRows = [];

        foreach ($releaseDates as $index => $releaseDatePayload) {
            $countryCode = $this->nullableString(data_get($releaseDatePayload, 'country.code'));
            $country = $countryCode !== null
                ? ($countriesByCode[$countryCode] ?? null)
                : null;

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
                $attribute = $releaseDateAttributesByName[$attributeName] ?? null;

                if (! $attribute instanceof ReleaseDateAttribute) {
                    continue;
                }

                $attributeRows[] = [
                    'movie_release_date_id' => $releaseDate->getKey(),
                    'release_date_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ];
            }
        }

        if ($attributeRows !== []) {
            MovieReleaseDateAttribute::query()->insert($attributeRows);
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

        $akas = $this->normalizeObjectList(data_get($akasPayload, 'akas'));
        $countriesByCode = $this->resolveCountriesFromPayloads(array_map(
            fn (array $akaPayload): mixed => data_get($akaPayload, 'country'),
            $akas,
        ));
        $languagesByCode = $this->resolveLanguagesFromPayloads(array_map(
            fn (array $akaPayload): mixed => data_get($akaPayload, 'language'),
            $akas,
        ));
        $akaAttributesByName = $this->resolveAkaAttributesByName($this->flattenStringLists(array_map(
            fn (array $akaPayload): array => $this->normalizeStringList(data_get($akaPayload, 'attributes')),
            $akas,
        )));
        $attributeRows = [];

        foreach ($akas as $index => $akaPayload) {
            $text = $this->nullableString(data_get($akaPayload, 'text'));

            if ($text === null) {
                continue;
            }

            $countryCode = $this->nullableString(data_get($akaPayload, 'country.code'));
            $languageCode = $this->nullableString(data_get($akaPayload, 'language.code'));
            $country = $countryCode !== null
                ? ($countriesByCode[$countryCode] ?? null)
                : null;
            $language = $languageCode !== null
                ? ($languagesByCode[$languageCode] ?? null)
                : null;
            $movieAka = MovieAka::query()->create([
                'movie_id' => $movie->getKey(),
                'text' => $text,
                'country_code' => $country?->getKey(),
                'language_code' => $language?->getKey(),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeStringList(data_get($akaPayload, 'attributes')) as $attributeIndex => $attributeName) {
                $attribute = $akaAttributesByName[$attributeName] ?? null;

                if (! $attribute instanceof AkaAttribute) {
                    continue;
                }

                $attributeRows[] = [
                    'movie_aka_id' => $movieAka->getKey(),
                    'aka_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ];
            }
        }

        if ($attributeRows !== []) {
            MovieAkaAttribute::query()->insert($attributeRows);
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

        $seasonRows = [];

        foreach ($this->normalizeObjectList(data_get($bundle, 'seasons.seasons')) as $seasonPayload) {
            $seasonRows[] = [
                'movie_id' => $movie->getKey(),
                'season' => (string) (data_get($seasonPayload, 'season') ?? ''),
                'episode_count' => $this->nullableInt(data_get($seasonPayload, 'episodeCount')),
            ];
        }

        if ($seasonRows !== []) {
            MovieSeason::query()->insert($seasonRows);
        }

        $episodePayloads = $this->normalizeObjectList(data_get($bundle, 'episodes.episodes'));
        $episodeStubPayloads = [];

        foreach ($episodePayloads as $episodePayload) {
            $titleStub = data_get($episodePayload, 'title');

            if (is_array($titleStub) && $this->nullableString(data_get($titleStub, 'id')) !== null) {
                $episodeStubPayloads[] = $titleStub;

                continue;
            }

            $episodeId = $this->nullableString(data_get($episodePayload, 'id'));

            if ($episodeId !== null) {
                $episodeStubPayloads[] = [
                    'id' => $episodeId,
                    'type' => 'tvEpisode',
                    'primaryTitle' => $episodeId,
                ];
            }
        }

        $episodeMoviesByImdbId = $this->preloadMovieStubs($episodeStubPayloads);
        $episodeRows = [];

        foreach ($episodePayloads as $episodePayload) {
            $episodeMovie = is_array(data_get($episodePayload, 'title'))
                ? ($episodeMoviesByImdbId[(string) data_get($episodePayload, 'title.id')] ?? null)
                : null;

            if (! $episodeMovie instanceof Movie) {
                $episodeId = $this->nullableString(data_get($episodePayload, 'id'));
                $episodeMovie = $episodeId !== null
                    ? ($episodeMoviesByImdbId[$episodeId] ?? null)
                    : null;
            }

            if (! $episodeMovie instanceof Movie) {
                continue;
            }

            $episodeRows[] = [
                'episode_movie_id' => $episodeMovie->getKey(),
                'movie_id' => $movie->getKey(),
                'season' => (string) (data_get($episodePayload, 'season') ?? ''),
                'episode_number' => $this->nullableInt(data_get($episodePayload, 'episodeNumber')),
                'release_year' => $this->nullableInt(data_get($episodePayload, 'releaseDate.year')),
                'release_month' => $this->nullableInt(data_get($episodePayload, 'releaseDate.month')),
                'release_day' => $this->nullableInt(data_get($episodePayload, 'releaseDate.day')),
            ];
        }

        if ($episodeRows !== []) {
            MovieEpisode::query()->insert($episodeRows);
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

        $videos = $this->normalizeObjectList(data_get($videosPayload, 'videos'));
        $videoTypesByName = $this->resolveVideoTypesByName(array_map(
            fn (array $videoPayload): ?string => $this->nullableString(data_get($videoPayload, 'type')),
            $videos,
        ));
        $videoRows = [];
        $primaryImageRows = [];

        foreach ($videos as $index => $videoPayload) {
            $videoId = $this->nullableString(data_get($videoPayload, 'id'));

            if ($videoId === null) {
                continue;
            }

            $videoTypeName = $this->nullableString(data_get($videoPayload, 'type'));
            $videoType = $videoTypeName !== null
                ? ($videoTypesByName[$videoTypeName] ?? null)
                : null;

            $videoRows[] = [
                'imdb_id' => $videoId,
                'movie_id' => $movie->getKey(),
                'video_type_id' => $videoType?->getKey(),
                'name' => $this->nullableString(data_get($videoPayload, 'name')),
                'description' => $this->nullableString(data_get($videoPayload, 'description')),
                'width' => $this->nullableInt(data_get($videoPayload, 'width')),
                'height' => $this->nullableInt(data_get($videoPayload, 'height')),
                'runtime_seconds' => $this->nullableInt(data_get($videoPayload, 'runtimeSeconds')),
                'position' => $index + 1,
            ];

            $imageUrl = $this->nullableString(data_get($videoPayload, 'primaryImage.url'));

            if ($imageUrl !== null) {
                $primaryImageRows[] = [
                    'video_imdb_id' => $videoId,
                    'url' => $imageUrl,
                    'width' => $this->nullableInt(data_get($videoPayload, 'primaryImage.width')),
                    'height' => $this->nullableInt(data_get($videoPayload, 'primaryImage.height')),
                    'type' => $this->nullableString(data_get($videoPayload, 'primaryImage.type')),
                ];
            }
        }

        if ($videoRows !== []) {
            MovieVideo::query()->insert($videoRows);
        }

        if ($primaryImageRows !== []) {
            MovieVideoPrimaryImage::query()->insert($primaryImageRows);
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

        $awardNominations = $this->normalizeObjectList(data_get($awardsPayload, 'awardNominations'));
        $awardEventsByImdbId = $this->resolveAwardEvents($awardNominations);
        $awardCategoriesByName = $this->resolveAwardCategoriesByName(array_map(
            fn (array $awardPayload): ?string => $this->nullableString(data_get($awardPayload, 'category')),
            $awardNominations,
        ));
        $nomineePayloads = [];
        $titlePayloads = [];

        foreach ($awardNominations as $awardPayload) {
            foreach ($this->normalizeObjectList(data_get($awardPayload, 'nominees')) as $nomineePayload) {
                $nomineePayloads[] = $nomineePayload;
            }

            foreach ($this->normalizeObjectList(data_get($awardPayload, 'titles')) as $titlePayload) {
                $titlePayloads[] = $titlePayload;
            }
        }

        $nomineesByImdbId = $this->preloadNameStubs($this->buildNameStubEntries($nomineePayloads, null));
        $titlesByImdbId = $this->preloadMovieStubs($titlePayloads);
        $nomineeRows = [];
        $titleRows = [];

        foreach ($awardNominations as $index => $awardPayload) {
            $eventId = $this->nullableString(data_get($awardPayload, 'event.id'));
            $categoryName = $this->nullableString(data_get($awardPayload, 'category'));
            $event = $eventId !== null ? ($awardEventsByImdbId[$eventId] ?? null) : null;
            $category = $categoryName !== null ? ($awardCategoriesByName[$categoryName] ?? null) : null;

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
                $nomineeId = $this->nullableString(data_get($nomineePayload, 'id'));
                $person = $nomineeId !== null
                    ? ($nomineesByImdbId[$nomineeId] ?? null)
                    : null;

                if (! $person instanceof NameBasic) {
                    continue;
                }

                $nomineeRows[] = [
                    'movie_award_nomination_id' => $nomination->getKey(),
                    'name_basic_id' => $person->getKey(),
                    'position' => $nomineeIndex + 1,
                ];
            }

            foreach ($this->normalizeObjectList(data_get($awardPayload, 'titles')) as $titleIndex => $titlePayload) {
                $titleId = $this->nullableString(data_get($titlePayload, 'id'));
                $nominatedMovie = $titleId !== null
                    ? ($titlesByImdbId[$titleId] ?? null)
                    : null;

                if (! $nominatedMovie instanceof Movie) {
                    continue;
                }

                $titleRows[] = [
                    'movie_award_nomination_id' => $nomination->getKey(),
                    'nominated_movie_id' => $nominatedMovie->getKey(),
                    'position' => $titleIndex + 1,
                ];
            }
        }

        if ($nomineeRows !== []) {
            MovieAwardNominationNominee::query()->insert($nomineeRows);
        }

        if ($titleRows !== []) {
            MovieAwardNominationTitle::query()->insert($titleRows);
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

        $parentsGuideSections = $this->normalizeObjectList(data_get($parentsGuidePayload, 'parentsGuide'));
        $categoriesByCode = $this->resolveParentsGuideCategories(array_map(
            fn (array $sectionPayload): ?string => $this->nullableString(data_get($sectionPayload, 'category')),
            $parentsGuideSections,
        ));
        $severityLevelsByName = $this->resolveParentsGuideSeverityLevels($this->flattenStringLists(array_map(
            fn (array $sectionPayload): array => array_values(array_filter(array_map(
                fn (array $severityPayload): ?string => $this->nullableString(data_get($severityPayload, 'severityLevel')),
                $this->normalizeObjectList(data_get($sectionPayload, 'severityBreakdowns')),
            ))),
            $parentsGuideSections,
        )));
        $breakdownRows = [];
        $reviewRows = [];

        foreach ($parentsGuideSections as $index => $sectionPayload) {
            $categoryName = $this->nullableString(data_get($sectionPayload, 'category'));

            if ($categoryName === null) {
                continue;
            }

            $category = $categoriesByCode[$categoryName] ?? null;

            if (! $category instanceof ParentsGuideCategory) {
                continue;
            }

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

                $severityLevel = $severityLevelsByName[$severityName] ?? null;

                if (! $severityLevel instanceof ParentsGuideSeverityLevel) {
                    continue;
                }

                $breakdownRows[] = [
                    'movie_parents_guide_section_id' => $section->getKey(),
                    'parents_guide_severity_level_id' => $severityLevel->getKey(),
                    'vote_count' => $this->nullableInt(data_get($severityPayload, 'voteCount')) ?? 0,
                    'position' => $severityIndex + 1,
                ];
            }

            foreach ($this->normalizeObjectList(data_get($sectionPayload, 'reviews')) as $reviewIndex => $reviewPayload) {
                $text = $this->nullableString(data_get($reviewPayload, 'text'));

                if ($text === null) {
                    continue;
                }

                $reviewRows[] = [
                    'movie_parents_guide_section_id' => $section->getKey(),
                    'text' => $text,
                    'is_spoiler' => $this->nullableBool(data_get($reviewPayload, 'isSpoiler')) ? 1 : 0,
                    'position' => $reviewIndex + 1,
                ];
            }
        }

        if ($breakdownRows !== []) {
            MovieParentsGuideSeverityBreakdown::query()->insert($breakdownRows);
        }

        if ($reviewRows !== []) {
            MovieParentsGuideReview::query()->insert($reviewRows);
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

        $certificates = $this->normalizeObjectList(data_get($certificatesPayload, 'certificates'));
        $certificatesWithRatings = array_values(array_filter(
            $certificates,
            fn (array $certificatePayload): bool => $this->nullableString(data_get($certificatePayload, 'rating')) !== null,
        ));
        $certificateRatingsByName = $this->resolveCertificateRatingsByName(array_map(
            fn (array $certificatePayload): ?string => $this->nullableString(data_get($certificatePayload, 'rating')),
            $certificatesWithRatings,
        ));
        $countriesByCode = $this->resolveCountriesFromPayloads(array_map(
            fn (array $certificatePayload): mixed => data_get($certificatePayload, 'country'),
            $certificatesWithRatings,
        ));
        $certificateAttributesByName = $this->resolveCertificateAttributesByName($this->flattenStringLists(array_map(
            fn (array $certificatePayload): array => $this->normalizeStringList(data_get($certificatePayload, 'attributes')),
            $certificatesWithRatings,
        )));
        $attributeRows = [];

        foreach ($certificates as $index => $certificatePayload) {
            $ratingName = $this->nullableString(data_get($certificatePayload, 'rating'));
            $countryCode = $this->nullableString(data_get($certificatePayload, 'country.code'));
            $rating = $this->resolveCertificateRatingModel($ratingName, $certificateRatingsByName);
            $country = $countryCode !== null
                ? ($countriesByCode[$countryCode] ?? null)
                : null;

            if (! $rating instanceof CertificateRating) {
                continue;
            }

            $certificate = MovieCertificate::query()->create([
                'movie_id' => $movie->getKey(),
                'certificate_rating_id' => $rating?->getKey(),
                'country_code' => $country?->getKey(),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeStringList(data_get($certificatePayload, 'attributes')) as $attributeIndex => $attributeName) {
                $attribute = $this->resolveCertificateAttributeModel($attributeName, $certificateAttributesByName);

                $attributeRows[] = [
                    'movie_certificate_id' => $certificate->getKey(),
                    'certificate_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ];
            }
        }

        if ($attributeRows !== []) {
            MovieCertificateAttribute::query()->insert($attributeRows);
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

        $companyCredits = $this->normalizeObjectList(data_get($companyCreditsPayload, 'companyCredits'));
        $companiesByImdbId = $this->resolveCompanies($companyCredits);
        $companyCreditCategoriesByName = $this->resolveCompanyCreditCategoriesByName(array_map(
            fn (array $creditPayload): ?string => $this->nullableString(data_get($creditPayload, 'category')),
            $companyCredits,
        ));
        $countriesByCode = $this->resolveCountriesFromPayloads($this->flattenObjectLists(array_map(
            fn (array $creditPayload): array => $this->normalizeObjectList(data_get($creditPayload, 'countries')),
            $companyCredits,
        )));
        $companyCreditAttributesByName = $this->resolveCompanyCreditAttributesByName($this->flattenStringLists(array_map(
            fn (array $creditPayload): array => $this->normalizeStringList(data_get($creditPayload, 'attributes')),
            $companyCredits,
        )));
        $countryRows = [];
        $attributeRows = [];

        foreach ($companyCredits as $index => $creditPayload) {
            $companyId = $this->nullableString(data_get($creditPayload, 'company.id'));
            $categoryName = $this->nullableString(data_get($creditPayload, 'category'));
            $company = $companyId !== null ? ($companiesByImdbId[$companyId] ?? null) : null;
            $category = $categoryName !== null
                ? ($companyCreditCategoriesByName[$categoryName] ?? null)
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
                $countryCode = $this->nullableString(data_get($countryPayload, 'code'));
                $country = $countryCode !== null
                    ? ($countriesByCode[$countryCode] ?? null)
                    : null;

                if (! $country instanceof Country) {
                    continue;
                }

                $countryRows[] = [
                    'movie_company_credit_id' => $movieCompanyCredit->getKey(),
                    'country_code' => $country->getKey(),
                    'position' => $countryIndex + 1,
                ];
            }

            foreach ($this->normalizeStringList(data_get($creditPayload, 'attributes')) as $attributeIndex => $attributeName) {
                $attribute = $companyCreditAttributesByName[$attributeName] ?? null;

                if (! $attribute instanceof CompanyCreditAttribute) {
                    continue;
                }

                $attributeRows[] = [
                    'movie_company_credit_id' => $movieCompanyCredit->getKey(),
                    'company_credit_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ];
            }
        }

        $countryRows = $this->deduplicateBridgeRows($countryRows, [
            'movie_company_credit_id',
            'country_code',
        ]);
        $attributeRows = $this->deduplicateBridgeRows($attributeRows, [
            'movie_company_credit_id',
            'company_credit_attribute_id',
        ]);

        if ($countryRows !== []) {
            MovieCompanyCreditCountry::query()->insert($countryRows);
        }

        if ($attributeRows !== []) {
            MovieCompanyCreditAttribute::query()->insert($attributeRows);
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
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $keyColumns
     * @return list<array<string, mixed>>
     */
    private function deduplicateBridgeRows(array $rows, array $keyColumns): array
    {
        $rowsByKey = [];

        foreach ($rows as $row) {
            $rowKey = collect($keyColumns)
                ->map(fn (string $keyColumn): string => (string) ($row[$keyColumn] ?? ''))
                ->implode('|');

            if (! array_key_exists($rowKey, $rowsByKey)) {
                $rowsByKey[$rowKey] = $row;

                continue;
            }

            $existingPosition = $rowsByKey[$rowKey]['position'] ?? null;
            $incomingPosition = $row['position'] ?? null;

            if (is_int($existingPosition) && is_int($incomingPosition)) {
                $rowsByKey[$rowKey]['position'] = min($existingPosition, $incomingPosition);
            } elseif ($existingPosition === null && $incomingPosition !== null) {
                $rowsByKey[$rowKey]['position'] = $incomingPosition;
            }
        }

        return array_values($rowsByKey);
    }

    /**
     * @param  array<string, mixed>  $boxOfficePayload
     */
    private function syncBoxOffice(Movie $movie, array $boxOfficePayload): void
    {
        if ($boxOfficePayload === []) {
            return;
        }

        $this->resolveCurrenciesByCode([
            $this->nullableString(data_get($boxOfficePayload, 'domesticGross.currency')),
            $this->nullableString(data_get($boxOfficePayload, 'worldwideGross.currency')),
            $this->nullableString(data_get($boxOfficePayload, 'openingWeekendGross.currency')),
            $this->nullableString(data_get($boxOfficePayload, 'productionBudget.currency')),
        ]);

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
        $directorRows = [];
        $writerRows = [];
        $starRows = [];

        MovieDirector::query()->where('movie_id', $movie->getKey())->delete();
        MovieWriter::query()->where('movie_id', $movie->getKey())->delete();
        MovieStar::query()->where('movie_id', $movie->getKey())->delete();

        $this->preloadNameStubs([
            ...$this->buildNameStubEntries(array_map(
                fn (array $creditPayload): mixed => data_get($creditPayload, 'name'),
                $credits,
            ), fn (array $creditPayload): ?string => $this->nullableString(data_get($creditPayload, 'category'))),
            ...$this->buildNameStubEntries($directors, 'director'),
            ...$this->buildNameStubEntries($writers, 'writer'),
            ...$this->buildNameStubEntries($stars, 'actor'),
        ]);

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

        $characterRows = [];

        foreach ($creditCharacters as $creditKey => $characters) {
            $credit = $persistedCredits->get($creditKey);

            if (! $credit instanceof NameCredit) {
                continue;
            }

            $persistedCreditIds[] = (int) $credit->getKey();

            foreach ($characters as $index => $characterName) {
                $characterRows[] = [
                    'name_credit_id' => $credit->getKey(),
                    'position' => $index + 1,
                    'character_name' => $characterName,
                ];
            }
        }

        if ($persistedCreditIds !== []) {
            NameCreditCharacter::query()->whereIn('name_credit_id', $persistedCreditIds)->delete();
        }

        if ($characterRows !== []) {
            NameCreditCharacter::query()->insert($characterRows);
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

            $directorRows[] = [
                'movie_id' => $movie->getKey(),
                'name_basic_id' => $person->getKey(),
                'position' => $index + 1,
            ];
        }

        foreach ($writers as $index => $writerPayload) {
            $person = $this->upsertNameStub($writerPayload, 'writer');

            if (! $person instanceof NameBasic) {
                continue;
            }

            $writerRows[] = [
                'movie_id' => $movie->getKey(),
                'name_basic_id' => $person->getKey(),
                'position' => $index + 1,
            ];
        }

        foreach ($stars as $index => $starPayload) {
            $person = $this->upsertNameStub($starPayload, 'actor');

            if (! $person instanceof NameBasic) {
                continue;
            }

            $starRows[] = [
                'movie_id' => $movie->getKey(),
                'name_basic_id' => $person->getKey(),
                'ordering' => $index + 1,
                'category' => $this->nullableString(data_get($starPayload, 'primaryProfessions.0')),
                'job' => null,
            ];
        }

        if ($directorRows !== []) {
            MovieDirector::query()->insert($directorRows);
        }

        if ($writerRows !== []) {
            MovieWriter::query()->insert($writerRows);
        }

        if ($starRows !== []) {
            MovieStar::query()->insert($starRows);
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

        $name = $this->nameStubCache[$imdbId] ?? new NameBasic;

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
        $this->nameStubCache[$imdbId] = $name;

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
            $professionsByName = $this->resolveProfessionsByName($primaryProfessions);
            $professionRows = [];

            foreach ($primaryProfessions as $index => $professionName) {
                $profession = $professionsByName[$professionName] ?? null;

                if (! $profession instanceof Profession) {
                    continue;
                }

                $professionRows[] = [
                    'name_basic_id' => $name->getKey(),
                    'profession_id' => $profession->getKey(),
                    'position' => $index + 1,
                ];
            }

            if ($professionRows !== []) {
                NameBasicProfession::query()->insert($professionRows);
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

        return $this->resolveInterestStubs([$payload])[$imdbId] ?? null;
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

        $movie = $this->movieStubCache[$imdbId] ?? new Movie;

        $typeName = $this->nullableString(data_get($payload, 'type'));
        $titleType = $this->resolveTitleType($typeName);

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
        $this->movieStubCache[$imdbId] = $movie;

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

        return $this->resolveCountriesFromPayloads([$payload])[$code] ?? null;
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

        return $this->resolveLanguagesFromPayloads([$payload])[$code] ?? null;
    }

    private function firstOrCreateCurrency(?string $code): void
    {
        if ($code === null) {
            return;
        }

        $this->resolveCurrenciesByCode([$code]);
    }

    private function resetImportCaches(): void
    {
        $this->resetBatchedLookupCache();
        $this->movieStubCache = [];
        $this->nameStubCache = [];
        $this->interestStubCache = [];
    }

    private function resolveTitleType(?string $typeName): ?TitleType
    {
        if ($typeName === null) {
            return null;
        }

        return $this->batchLookupModels(
            TitleType::class,
            'name',
            [$typeName => ['name' => $typeName]],
        )[$typeName] ?? null;
    }

    /**
     * @param  list<string>  $genreNames
     * @return array<string, Genre>
     */
    private function resolveGenresByName(array $genreNames): array
    {
        return $this->batchLookupModels(Genre::class, 'name', $this->stringLookupRows($genreNames));
    }

    /**
     * @param  list<string>  $professionNames
     * @return array<string, Profession>
     */
    private function resolveProfessionsByName(array $professionNames): array
    {
        return $this->batchLookupModels(Profession::class, 'name', $this->stringLookupRows($professionNames));
    }

    /**
     * @param  list<array<string, mixed>>  $interestPayloads
     * @return array<string, Interest>
     */
    private function resolveInterestStubs(array $interestPayloads): array
    {
        $rowsByImdbId = [];

        foreach ($interestPayloads as $interestPayload) {
            $imdbId = $this->nullableString(data_get($interestPayload, 'id'));

            if ($imdbId === null) {
                continue;
            }

            $rowsByImdbId[$imdbId] = [
                'imdb_id' => $imdbId,
                'name' => $this->nullableString(data_get($interestPayload, 'name')),
                'description' => $this->nullableString(data_get($interestPayload, 'description')),
                'is_subgenre' => $this->nullableBool(data_get($interestPayload, 'isSubgenre')) ?? false,
            ];
        }

        $models = $this->batchUpsertModels(
            Interest::class,
            'imdb_id',
            $rowsByImdbId,
            ['name', 'description', 'is_subgenre'],
        );

        $this->interestStubCache = array_replace($this->interestStubCache, $models);

        return $models;
    }

    /**
     * @param  list<mixed>  $countryPayloads
     * @return array<string, Country>
     */
    private function resolveCountriesFromPayloads(array $countryPayloads): array
    {
        $rowsByCode = [];

        foreach ($countryPayloads as $countryPayload) {
            if (! is_array($countryPayload)) {
                continue;
            }

            $code = $this->nullableString(data_get($countryPayload, 'code'));

            if ($code === null) {
                continue;
            }

            $rowsByCode[$code] = [
                'code' => $code,
                'name' => $this->nullableString(data_get($countryPayload, 'name')),
            ];
        }

        return $this->batchUpsertModels(Country::class, 'code', $rowsByCode, ['name']);
    }

    /**
     * @param  list<mixed>  $languagePayloads
     * @return array<string, Language>
     */
    private function resolveLanguagesFromPayloads(array $languagePayloads): array
    {
        $rowsByCode = [];

        foreach ($languagePayloads as $languagePayload) {
            if (! is_array($languagePayload)) {
                continue;
            }

            $code = $this->nullableString(data_get($languagePayload, 'code'));

            if ($code === null) {
                continue;
            }

            $rowsByCode[$code] = [
                'code' => $code,
                'name' => $this->nullableString(data_get($languagePayload, 'name')),
            ];
        }

        return $this->batchUpsertModels(Language::class, 'code', $rowsByCode, ['name']);
    }

    /**
     * @param  list<string|null>  $codes
     * @return array<string, Currency>
     */
    private function resolveCurrenciesByCode(array $codes): array
    {
        $rowsByCode = [];

        foreach ($codes as $code) {
            if ($code === null) {
                continue;
            }

            $rowsByCode[$code] = ['code' => $code];
        }

        return $this->batchLookupModels(Currency::class, 'code', $rowsByCode);
    }

    /**
     * @param  list<string>  $attributeNames
     * @return array<string, ReleaseDateAttribute>
     */
    private function resolveReleaseDateAttributesByName(array $attributeNames): array
    {
        return $this->batchLookupModels(
            ReleaseDateAttribute::class,
            'name',
            $this->stringLookupRows($attributeNames),
        );
    }

    /**
     * @param  list<string>  $attributeNames
     * @return array<string, AkaAttribute>
     */
    private function resolveAkaAttributesByName(array $attributeNames): array
    {
        return $this->batchLookupModels(
            AkaAttribute::class,
            'name',
            $this->stringLookupRows($attributeNames),
        );
    }

    /**
     * @param  list<string|null>  $videoTypeNames
     * @return array<string, VideoType>
     */
    private function resolveVideoTypesByName(array $videoTypeNames): array
    {
        return $this->batchLookupModels(
            VideoType::class,
            'name',
            $this->stringLookupRows(array_values(array_filter($videoTypeNames))),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $awardNominations
     * @return array<string, AwardEvent>
     */
    private function resolveAwardEvents(array $awardNominations): array
    {
        $rowsByImdbId = [];

        foreach ($awardNominations as $awardPayload) {
            $imdbId = $this->nullableString(data_get($awardPayload, 'event.id'));

            if ($imdbId === null) {
                continue;
            }

            $rowsByImdbId[$imdbId] = [
                'imdb_id' => $imdbId,
                'name' => $this->nullableString(data_get($awardPayload, 'event.name')),
            ];
        }

        return $this->batchUpsertModels(AwardEvent::class, 'imdb_id', $rowsByImdbId, ['name']);
    }

    /**
     * @param  list<string|null>  $categoryNames
     * @return array<string, AwardCategory>
     */
    private function resolveAwardCategoriesByName(array $categoryNames): array
    {
        return $this->batchLookupModels(
            AwardCategory::class,
            'name',
            $this->stringLookupRows(array_values(array_filter($categoryNames))),
        );
    }

    /**
     * @param  list<string|null>  $categoryCodes
     * @return array<string, ParentsGuideCategory>
     */
    private function resolveParentsGuideCategories(array $categoryCodes): array
    {
        return $this->batchLookupModels(
            ParentsGuideCategory::class,
            'code',
            $this->stringLookupRows(array_values(array_filter($categoryCodes)), 'code'),
        );
    }

    /**
     * @param  list<string>  $severityNames
     * @return array<string, ParentsGuideSeverityLevel>
     */
    private function resolveParentsGuideSeverityLevels(array $severityNames): array
    {
        return $this->batchLookupModels(
            ParentsGuideSeverityLevel::class,
            'name',
            $this->stringLookupRows($severityNames),
        );
    }

    /**
     * @param  list<string|null>  $ratingNames
     * @return array<string, CertificateRating>
     */
    private function resolveCertificateRatingsByName(array $ratingNames): array
    {
        return $this->batchLookupModels(
            CertificateRating::class,
            'name',
            $this->stringLookupRows(array_values(array_filter($ratingNames))),
        );
    }

    /**
     * @param  list<string>  $attributeNames
     * @return array<string, CertificateAttribute>
     */
    private function resolveCertificateAttributesByName(array $attributeNames): array
    {
        return $this->batchLookupModels(
            CertificateAttribute::class,
            'name',
            $this->stringLookupRows($attributeNames),
        );
    }

    /**
     * @param  array<string, CertificateRating>  $certificateRatingsByName
     */
    private function resolveCertificateRatingModel(?string $ratingName, array &$certificateRatingsByName): ?CertificateRating
    {
        if ($ratingName === null) {
            return null;
        }

        $rating = $certificateRatingsByName[$ratingName] ?? null;

        if ($rating instanceof CertificateRating) {
            return $rating;
        }

        $rating = CertificateRating::query()->firstOrCreate(['name' => $ratingName]);
        $certificateRatingsByName[$ratingName] = $rating;

        return $rating;
    }

    /**
     * @param  array<string, CertificateAttribute>  $certificateAttributesByName
     */
    private function resolveCertificateAttributeModel(string $attributeName, array &$certificateAttributesByName): CertificateAttribute
    {
        $attribute = $certificateAttributesByName[$attributeName] ?? null;

        if ($attribute instanceof CertificateAttribute) {
            return $attribute;
        }

        $attribute = CertificateAttribute::query()->firstOrCreate(['name' => $attributeName]);
        $certificateAttributesByName[$attributeName] = $attribute;

        return $attribute;
    }

    /**
     * @param  list<array<string, mixed>>  $companyCredits
     * @return array<string, Company>
     */
    private function resolveCompanies(array $companyCredits): array
    {
        $rowsByImdbId = [];

        foreach ($companyCredits as $creditPayload) {
            $imdbId = $this->nullableString(data_get($creditPayload, 'company.id'));

            if ($imdbId === null) {
                continue;
            }

            $rowsByImdbId[$imdbId] = [
                'imdb_id' => $imdbId,
                'name' => $this->nullableString(data_get($creditPayload, 'company.name')),
            ];
        }

        return $this->batchUpsertModels(Company::class, 'imdb_id', $rowsByImdbId, ['name']);
    }

    /**
     * @param  list<string|null>  $categoryNames
     * @return array<string, CompanyCreditCategory>
     */
    private function resolveCompanyCreditCategoriesByName(array $categoryNames): array
    {
        return $this->batchLookupModels(
            CompanyCreditCategory::class,
            'name',
            $this->stringLookupRows(array_values(array_filter($categoryNames))),
        );
    }

    /**
     * @param  list<string>  $attributeNames
     * @return array<string, CompanyCreditAttribute>
     */
    private function resolveCompanyCreditAttributesByName(array $attributeNames): array
    {
        return $this->batchLookupModels(
            CompanyCreditAttribute::class,
            'name',
            $this->stringLookupRows($attributeNames),
        );
    }

    /**
     * @param  list<array{payload: array<string, mixed>, fallback_profession: ?string}>  $entries
     * @return array<string, NameBasic>
     */
    private function preloadNameStubs(array $entries): array
    {
        $ids = [];

        foreach ($entries as $entry) {
            $imdbId = $this->nullableString(data_get($entry, 'payload.id'));

            if ($imdbId !== null) {
                $ids[$imdbId] = $imdbId;
            }
        }

        $this->warmNameStubCache(array_values($ids));

        $models = [];

        foreach ($entries as $entry) {
            $model = $this->upsertNameStub($entry['payload'], $entry['fallback_profession']);

            if ($model instanceof NameBasic) {
                $models[(string) $model->nconst] = $model;
            }
        }

        return $models;
    }

    /**
     * @param  list<array<string, mixed>>  $payloads
     * @return array<string, Movie>
     */
    private function preloadMovieStubs(array $payloads): array
    {
        $payloadsById = [];

        foreach ($payloads as $payload) {
            $imdbId = $this->nullableString(data_get($payload, 'id'));

            if ($imdbId === null) {
                continue;
            }

            $payloadsById[$imdbId] = $payload;
        }

        $this->warmMovieStubCache(array_keys($payloadsById));

        $models = [];

        foreach ($payloadsById as $imdbId => $payload) {
            $model = $this->upsertMovieStub($payload);

            if ($model instanceof Movie) {
                $models[$imdbId] = $model;
            }
        }

        return $models;
    }

    /**
     * @param  list<string>  $imdbIds
     */
    private function warmMovieStubCache(array $imdbIds): void
    {
        $missingIds = array_values(array_diff($imdbIds, array_keys($this->movieStubCache)));

        if ($missingIds === []) {
            return;
        }

        Movie::query()
            ->where(fn ($query) => $query->whereIn('tconst', $missingIds)->orWhereIn('imdb_id', $missingIds))
            ->get()
            ->each(function (Movie $movie): void {
                foreach (array_unique(array_filter([(string) $movie->tconst, (string) $movie->imdb_id])) as $imdbId) {
                    $this->movieStubCache[$imdbId] = $movie;
                }
            });
    }

    /**
     * @param  list<string>  $imdbIds
     */
    private function warmNameStubCache(array $imdbIds): void
    {
        $missingIds = array_values(array_diff($imdbIds, array_keys($this->nameStubCache)));

        if ($missingIds === []) {
            return;
        }

        NameBasic::query()
            ->where(fn ($query) => $query->whereIn('nconst', $missingIds)->orWhereIn('imdb_id', $missingIds))
            ->get()
            ->each(function (NameBasic $name): void {
                foreach (array_unique(array_filter([(string) $name->nconst, (string) $name->imdb_id])) as $imdbId) {
                    $this->nameStubCache[$imdbId] = $name;
                }
            });
    }

    /**
     * @param  list<mixed>  $payloads
     * @param  callable(array<string, mixed>): ?string|string|null  $fallbackProfession
     * @return list<array{payload: array<string, mixed>, fallback_profession: ?string}>
     */
    private function buildNameStubEntries(array $payloads, callable|string|null $fallbackProfession): array
    {
        $entries = [];

        foreach ($payloads as $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $entries[] = [
                'payload' => $payload,
                'fallback_profession' => is_callable($fallbackProfession)
                    ? $fallbackProfession($payload)
                    : $fallbackProfession,
            ];
        }

        return $entries;
    }

    /**
     * @param  list<list<string>>  $stringLists
     * @return list<string>
     */
    private function flattenStringLists(array $stringLists): array
    {
        $flattened = [];

        foreach ($stringLists as $stringList) {
            foreach ($stringList as $value) {
                $flattened[] = $value;
            }
        }

        return $this->normalizeStringList($flattened);
    }

    /**
     * @param  list<list<array<string, mixed>>>  $objectLists
     * @return list<array<string, mixed>>
     */
    private function flattenObjectLists(array $objectLists): array
    {
        $flattened = [];

        foreach ($objectLists as $objectList) {
            foreach ($objectList as $value) {
                $flattened[] = $value;
            }
        }

        return $this->normalizeObjectList($flattened);
    }

    /**
     * @param  list<string>  $values
     * @return array<string, array<string, string>>
     */
    private function stringLookupRows(array $values, string $column = 'name'): array
    {
        $rows = [];

        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $rows[$value] = [$column => $value];
        }

        return $rows;
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
