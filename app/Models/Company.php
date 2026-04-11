<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends ImdbModel
{
    protected $table = 'companies';

    protected $primaryKey = 'imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'imdb_id',
        'name',
    ];

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
        if (preg_match('/-(?P<imdb>co\d+)$/', (string) $value, $matches) === 1) {
            return $query->where('imdb_id', $matches['imdb']);
        }

        return $query->where('imdb_id', (string) $value);
    }

    public function movieCompanyCredits(): HasMany
    {
        return $this->hasMany(MovieCompanyCredit::class, 'company_imdb_id', 'imdb_id');
    }

    public function getSlugAttribute(): string
    {
        return Str::slug((string) ($this->name ?: $this->imdb_id)).'-'.$this->imdb_id;
    }
}
