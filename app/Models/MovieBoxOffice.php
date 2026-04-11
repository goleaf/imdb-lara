<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieBoxOffice extends ImdbModel
{
    protected $table = 'movie_box_office';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'domestic_gross_amount',
        'domestic_gross_currency_code',
        'worldwide_gross_amount',
        'worldwide_gross_currency_code',
        'opening_weekend_gross_amount',
        'opening_weekend_gross_currency_code',
        'opening_weekend_end_year',
        'opening_weekend_end_month',
        'opening_weekend_end_day',
        'production_budget_amount',
        'production_budget_currency_code',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'domestic_gross_amount' => 'decimal:2',
            'worldwide_gross_amount' => 'decimal:2',
            'opening_weekend_gross_amount' => 'decimal:2',
            'opening_weekend_end_year' => 'integer',
            'opening_weekend_end_month' => 'integer',
            'opening_weekend_end_day' => 'integer',
            'production_budget_amount' => 'decimal:2',
        ];
    }

    public function productionBudget(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'production_budget_currency_code', 'code');
    }

    public function domesticGross(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'domestic_gross_currency_code', 'code');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function openingWeekendGross(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'opening_weekend_gross_currency_code', 'code');
    }

    public function worldwideGross(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'worldwide_gross_currency_code', 'code');
    }
}
