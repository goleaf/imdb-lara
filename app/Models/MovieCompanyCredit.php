<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovieCompanyCredit extends ImdbModel
{
    protected $table = 'movie_company_credits';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'company_imdb_id',
        'company_credit_category_id',
        'start_year',
        'end_year',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'movie_id' => 'integer',
            'company_credit_category_id' => 'integer',
            'start_year' => 'integer',
            'end_year' => 'integer',
            'position' => 'integer',
        ];
    }

    public function companyCreditCategory(): BelongsTo
    {
        return $this->belongsTo(CompanyCreditCategory::class, 'company_credit_category_id', 'id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_imdb_id', 'imdb_id');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function movieCompanyCreditAttributes(): HasMany
    {
        return $this->hasMany(MovieCompanyCreditAttribute::class, 'movie_company_credit_id', 'id');
    }

    public function movieCompanyCreditCountries(): HasMany
    {
        return $this->hasMany(MovieCompanyCreditCountry::class, 'movie_company_credit_id', 'id');
    }

    public function activeYearsLabel(): ?string
    {
        if ($this->start_year !== null && $this->end_year !== null) {
            return $this->start_year === $this->end_year
                ? (string) $this->start_year
                : $this->start_year.'-'.$this->end_year;
        }

        if ($this->start_year !== null) {
            return 'Since '.$this->start_year;
        }

        if ($this->end_year !== null) {
            return 'Until '.$this->end_year;
        }

        return null;
    }
}
