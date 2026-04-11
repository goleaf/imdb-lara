<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieCompanyCreditAttribute extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_company_credit_attributes';

    protected $primaryKey = 'movie_company_credit_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_company_credit_id', 'company_credit_attribute_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_company_credit_id',
        'company_credit_attribute_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_company_credit_id' => 'integer',
            'company_credit_attribute_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function companyCreditAttribute(): BelongsTo
    {
        return $this->belongsTo(CompanyCreditAttribute::class, 'company_credit_attribute_id', 'id');
    }

    public function movieCompanyCredit(): BelongsTo
    {
        return $this->belongsTo(MovieCompanyCredit::class, 'movie_company_credit_id', 'id');
    }
}
