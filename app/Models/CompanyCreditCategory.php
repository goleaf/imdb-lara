<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyCreditCategory extends ImdbModel
{
    protected $table = 'company_credit_categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function movieCompanyCredits(): HasMany
    {
        return $this->hasMany(MovieCompanyCredit::class, 'company_credit_category_id', 'id');
    }
}
