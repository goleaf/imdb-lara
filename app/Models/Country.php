<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends ImdbModel
{
    protected $table = 'countries';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_origin_countries', 'country_code', 'movie_id', 'code', 'id');
    }

    public function movieAkas(): HasMany
    {
        return $this->hasMany(MovieAka::class, 'country_code', 'code');
    }

    public function movieCertificates(): HasMany
    {
        return $this->hasMany(MovieCertificate::class, 'country_code', 'code');
    }

    public function movieCompanyCreditCountries(): HasMany
    {
        return $this->hasMany(MovieCompanyCreditCountry::class, 'country_code', 'code');
    }

    public function movieOriginCountries(): HasMany
    {
        return $this->hasMany(MovieOriginCountry::class, 'country_code', 'code');
    }

    public function movieReleaseDates(): HasMany
    {
        return $this->hasMany(MovieReleaseDate::class, 'country_code', 'code');
    }
}
