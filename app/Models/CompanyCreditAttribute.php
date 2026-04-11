<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getRouteKey(): string
    {
        return $this->slug;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if (preg_match('/-cca(?P<id>\d+)$/', (string) $value, $matches) === 1) {
            return $query->where('id', (int) $matches['id']);
        }

        return $query->where('id', (int) $value);
    }

    public function movieCompanyCreditAttributes(): HasMany
    {
        return $this->hasMany(MovieCompanyCreditAttribute::class, 'company_credit_attribute_id', 'id');
    }

    public function getSlugAttribute(): string
    {
        return Str::slug((string) $this->name).'-cca'.$this->id;
    }
}
