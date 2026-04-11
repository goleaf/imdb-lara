<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieCompanyCreditCountry extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_company_credit_countries';

    protected $primaryKey = 'movie_company_credit_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_company_credit_id', 'country_code'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_company_credit_id',
        'country_code',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_company_credit_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function movieCompanyCredit(): BelongsTo
    {
        return $this->belongsTo(MovieCompanyCredit::class, 'movie_company_credit_id', 'id');
    }
}
