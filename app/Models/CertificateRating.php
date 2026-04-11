<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function movieCertificates(): HasMany
    {
        return $this->hasMany(MovieCertificate::class, 'certificate_rating_id', 'id');
    }
}
