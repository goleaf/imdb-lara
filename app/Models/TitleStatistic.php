<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleStatistic extends Model
{
    protected $connection = 'imdb_mysql';

    protected $table = 'movie_ratings';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'aggregate_rating',
        'vote_count',
        'rating_distribution',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'aggregate_rating' => 'decimal:2',
            'vote_count' => 'integer',
            'rating_distribution' => 'array',
        ];
    }

    /**
     * @param  array<int|string, mixed>|null  $distribution
     * @return array<string, int>
     */
    public static function normalizeRatingDistribution(?array $distribution = null): array
    {
        $distribution ??= [];

        return collect(range(10, 1))
            ->mapWithKeys(function (int $score) use ($distribution): array {
                return [
                    (string) $score => max(0, (int) ($distribution[(string) $score] ?? $distribution[$score] ?? 0)),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function normalizedRatingDistribution(): array
    {
        $distribution = $this->getAttribute('rating_distribution');

        return self::normalizeRatingDistribution(
            is_array($distribution) ? $distribution : null,
        );
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function getTitleIdAttribute(): int
    {
        return (int) $this->movie_id;
    }

    public function getRatingCountAttribute(): int
    {
        return (int) ($this->vote_count ?? 0);
    }

    public function getAverageRatingAttribute(): ?float
    {
        return $this->aggregate_rating !== null ? (float) $this->aggregate_rating : null;
    }

    public function getReviewCountAttribute(): int
    {
        return 0;
    }

    public function getWatchlistCountAttribute(): int
    {
        return 0;
    }

    public function getEpisodesCountAttribute(): int
    {
        return 0;
    }

    public function getAwardsNominatedCountAttribute(): int
    {
        return 0;
    }

    public function getAwardsWonCountAttribute(): int
    {
        return 0;
    }
}
