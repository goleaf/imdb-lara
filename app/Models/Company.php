<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function movieCompanyCredits(): HasMany
    {
        return $this->hasMany(MovieCompanyCredit::class, 'company_imdb_id', 'imdb_id');
    }
}
