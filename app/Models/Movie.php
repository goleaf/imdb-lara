<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Movie extends ImdbModel
{
    protected $table = 'movies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tconst',
        'titletype',
        'primarytitle',
        'originaltitle',
        'isadult',
        'startyear',
        'endyear',
        'runtimeminutes',
        'genres',
        'title_type_id',
        'imdb_id',
        'runtimeSeconds',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'isadult' => 'integer',
            'startyear' => 'integer',
            'endyear' => 'integer',
            'runtimeminutes' => 'integer',
            'title_type_id' => 'integer',
            'runtimeSeconds' => 'integer',
        ];
    }

    public function titleType(): BelongsTo
    {
        return $this->belongsTo(TitleType::class, 'title_type_id', 'id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'movie_genres', 'movie_id', 'genre_id', 'id', 'id');
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'movie_interests', 'movie_id', 'interest_imdb_id', 'id', 'imdb_id');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'movie_origin_countries', 'movie_id', 'country_code', 'id', 'code');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'movie_spoken_languages', 'movie_id', 'language_code', 'id', 'code');
    }

    public function movieAkas(): HasMany
    {
        return $this->hasMany(MovieAka::class, 'movie_id', 'id');
    }

    public function movieAwardNominations(): HasMany
    {
        return $this->hasMany(MovieAwardNomination::class, 'movie_id', 'id');
    }

    public function movieAwardNominationSummaries(): HasMany
    {
        return $this->hasMany(MovieAwardNominationSummary::class, 'movie_id', 'id');
    }

    public function movieAwardNominationTitles(): HasMany
    {
        return $this->hasMany(MovieAwardNominationTitle::class, 'nominated_movie_id', 'id');
    }

    public function movieBoxOffices(): HasMany
    {
        return $this->hasMany(MovieBoxOffice::class, 'movie_id', 'id');
    }

    public function movieCertificates(): HasMany
    {
        return $this->hasMany(MovieCertificate::class, 'movie_id', 'id');
    }

    public function movieCertificateSummaries(): HasMany
    {
        return $this->hasMany(MovieCertificateSummary::class, 'movie_id', 'id');
    }

    public function movieCompanyCredits(): HasMany
    {
        return $this->hasMany(MovieCompanyCredit::class, 'movie_id', 'id');
    }

    public function movieCompanyCreditSummaries(): HasMany
    {
        return $this->hasMany(MovieCompanyCreditSummary::class, 'movie_id', 'id');
    }

    public function movieDirectors(): HasMany
    {
        return $this->hasMany(MovieDirector::class, 'movie_id', 'id');
    }

    public function episodeMovieEpisodes(): HasMany
    {
        return $this->hasMany(MovieEpisode::class, 'episode_movie_id', 'id');
    }

    public function movieEpisodes(): HasMany
    {
        return $this->hasMany(MovieEpisode::class, 'movie_id', 'id');
    }

    public function movieEpisodeSummaries(): HasMany
    {
        return $this->hasMany(MovieEpisodeSummary::class, 'movie_id', 'id');
    }

    public function movieGenres(): HasMany
    {
        return $this->hasMany(MovieGenre::class, 'movie_id', 'id');
    }

    public function movieImages(): HasMany
    {
        return $this->hasMany(MovieImage::class, 'movie_id', 'id');
    }

    public function movieImageSummaries(): HasMany
    {
        return $this->hasMany(MovieImageSummary::class, 'movie_id', 'id');
    }

    public function movieInterests(): HasMany
    {
        return $this->hasMany(MovieInterest::class, 'movie_id', 'id');
    }

    public function movieMetacritics(): HasMany
    {
        return $this->hasMany(MovieMetacritic::class, 'movie_id', 'id');
    }

    public function movieOriginCountries(): HasMany
    {
        return $this->hasMany(MovieOriginCountry::class, 'movie_id', 'id');
    }

    public function movieParentsGuideSections(): HasMany
    {
        return $this->hasMany(MovieParentsGuideSection::class, 'movie_id', 'id');
    }

    public function moviePlots(): HasMany
    {
        return $this->hasMany(MoviePlot::class, 'movie_id', 'id');
    }

    public function moviePrimaryImages(): HasMany
    {
        return $this->hasMany(MoviePrimaryImage::class, 'movie_id', 'id');
    }

    public function movieRating(): HasOne
    {
        return $this->hasOne(MovieRating::class, 'movie_id', 'id');
    }

    public function movieReleaseDates(): HasMany
    {
        return $this->hasMany(MovieReleaseDate::class, 'movie_id', 'id');
    }

    public function movieReleaseDateSummaries(): HasMany
    {
        return $this->hasMany(MovieReleaseDateSummary::class, 'movie_id', 'id');
    }

    public function movieSeasons(): HasMany
    {
        return $this->hasMany(MovieSeason::class, 'movie_id', 'id');
    }

    public function movieSpokenLanguages(): HasMany
    {
        return $this->hasMany(MovieSpokenLanguage::class, 'movie_id', 'id');
    }

    public function movieStars(): HasMany
    {
        return $this->hasMany(MovieStar::class, 'movie_id', 'id');
    }

    public function movieVideos(): HasMany
    {
        return $this->hasMany(MovieVideo::class, 'movie_id', 'id');
    }

    public function movieVideoSummaries(): HasMany
    {
        return $this->hasMany(MovieVideoSummary::class, 'movie_id', 'id');
    }

    public function movieWriters(): HasMany
    {
        return $this->hasMany(MovieWriter::class, 'movie_id', 'id');
    }

    public function nameBasicKnownForTitles(): HasMany
    {
        return $this->hasMany(NameBasicKnownForTitle::class, 'title_basic_id', 'id');
    }

    public function nameCredits(): HasMany
    {
        return $this->hasMany(NameCredit::class, 'movie_id', 'id');
    }

    public function titleAkas(): HasMany
    {
        return $this->hasMany(TitleAka::class, 'titleid', 'tconst');
    }

    public function titleCrews(): HasMany
    {
        return $this->hasMany(TitleCrew::class, 'tconst', 'tconst');
    }

    public function parentTitleEpisodes(): HasMany
    {
        return $this->hasMany(TitleEpisode::class, 'parenttconst', 'tconst');
    }

    public function titleEpisodes(): HasMany
    {
        return $this->hasMany(TitleEpisode::class, 'tconst', 'tconst');
    }

    public function titlePrincipals(): HasMany
    {
        return $this->hasMany(TitlePrincipal::class, 'tconst', 'tconst');
    }

    public function titleRatings(): HasMany
    {
        return $this->hasMany(TitleRating::class, 'tconst', 'tconst');
    }
}
