<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends ImdbModel
{
    protected $table = 'currencies';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
    ];

    public function productionBudgetMovieBoxOffices(): HasMany
    {
        return $this->hasMany(MovieBoxOffice::class, 'production_budget_currency_code', 'code');
    }

    public function domesticGrossMovieBoxOffices(): HasMany
    {
        return $this->hasMany(MovieBoxOffice::class, 'domestic_gross_currency_code', 'code');
    }

    public function openingWeekendGrossMovieBoxOffices(): HasMany
    {
        return $this->hasMany(MovieBoxOffice::class, 'opening_weekend_gross_currency_code', 'code');
    }

    public function worldwideGrossMovieBoxOffices(): HasMany
    {
        return $this->hasMany(MovieBoxOffice::class, 'worldwide_gross_currency_code', 'code');
    }
}
