<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyCreditAttribute extends ImdbModel
{
    protected $table = 'company_credit_attributes';

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

    public function movieCompanyCreditAttributes(): HasMany
    {
        return $this->hasMany(MovieCompanyCreditAttribute::class, 'company_credit_attribute_id', 'id');
    }
}
