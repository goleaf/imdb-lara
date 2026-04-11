<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieCertificateSummary extends ImdbModel
{
    protected $table = 'movie_certificate_summaries';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'total_count',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'total_count' => 'integer',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
