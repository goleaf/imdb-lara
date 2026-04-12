<?php

namespace App\Models;

use App\Policies\PersonPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(PersonPolicy::class)]
class LocalPerson extends Person
{
    protected $table = 'people';

    public static function usesCatalogOnlySchema(): bool
    {
        return false;
    }

    public function getMorphClass(): string
    {
        return Person::class;
    }

    public function credits(): HasMany
    {
        return $this->hasMany(LocalCredit::class, 'person_id')->ordered();
    }

    public function professions(): HasMany
    {
        return $this->hasMany(LocalPersonProfession::class, 'person_id')->ordered();
    }
}
