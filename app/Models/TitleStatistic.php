<?php

namespace App\Models;

use Database\Factories\TitleStatisticFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleStatistic extends Model
{
    /** @use HasFactory<TitleStatisticFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_id',
        'rating_count',
        'average_rating',
        'rating_distribution',
        'review_count',
        'watchlist_count',
        'episodes_count',
        'awards_nominated_count',
        'awards_won_count',
        'metacritic_score',
        'metacritic_review_count',
    ];

    protected function casts(): array
    {
        return [
            'title_id' => 'integer',
            'average_rating' => 'decimal:2',
            'rating_count' => 'integer',
            'rating_distribution' => 'array',
            'review_count' => 'integer',
            'watchlist_count' => 'integer',
            'episodes_count' => 'integer',
            'awards_nominated_count' => 'integer',
            'awards_won_count' => 'integer',
            'metacritic_score' => 'integer',
            'metacritic_review_count' => 'integer',
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

        return self::normalizeRatingDistribution(is_array($distribution) ? $distribution : null);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function getMovieIdAttribute(): int
    {
        return (int) $this->title_id;
    }

    public function getVoteCountAttribute(): int
    {
        return (int) $this->rating_count;
    }

    public function getAggregateRatingAttribute(): ?float
    {
        return $this->average_rating !== null ? (float) $this->average_rating : null;
    }
}
