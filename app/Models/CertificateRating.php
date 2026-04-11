<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CertificateRating extends ImdbModel
{
    protected $table = 'certificate_ratings';

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
        if (preg_match('/-cr(?P<id>\d+)$/', (string) $value, $matches) === 1) {
            return $query->whereKey((int) $matches['id']);
        }

        return $query->whereKey((int) $value);
    }

    public function movieCertificates(): HasMany
    {
        return $this->hasMany(MovieCertificate::class, 'certificate_rating_id', 'id');
    }

    public function getSlugAttribute(): string
    {
        return Str::slug((string) $this->name).'-cr'.$this->id;
    }
}
