<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovieCertificate extends ImdbModel
{
    protected $table = 'movie_certificates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'certificate_rating_id',
        'country_code',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'movie_id' => 'integer',
            'certificate_rating_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function certificateRating(): BelongsTo
    {
        return $this->belongsTo(CertificateRating::class, 'certificate_rating_id', 'id');
    }

    public function movieCertificateAttributes(): HasMany
    {
        return $this->hasMany(MovieCertificateAttribute::class, 'movie_certificate_id', 'id');
    }

    public function resolvedCountryLabel(): ?string
    {
        $fallbackName = $this->relationLoaded('country') ? $this->country?->name : null;

        return Country::labelForCode($this->country_code, $fallbackName);
    }

    public function resolvedRatingLabel(): ?string
    {
        if (! $this->relationLoaded('certificateRating') || ! $this->certificateRating instanceof CertificateRating) {
            return null;
        }

        return $this->certificateRating->resolvedLabel();
    }

    public function ratingDescription(): ?string
    {
        if (! $this->relationLoaded('certificateRating') || ! $this->certificateRating instanceof CertificateRating) {
            return null;
        }

        return $this->certificateRating->shortDescription();
    }
}
