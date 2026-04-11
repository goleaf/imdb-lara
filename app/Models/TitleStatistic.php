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
        'movie_id',
        'rating_count',
        'vote_count',
        'average_rating',
        'aggregate_rating',
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
            'movie_id' => 'integer',
            'average_rating' => 'decimal:2',
            'aggregate_rating' => 'decimal:2',
            'rating_count' => 'integer',
            'vote_count' => 'integer',
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

    protected static function booted(): void
    {
        static::saving(function (self $statistic): void {
            if (! Title::usesCatalogOnlySchema()) {
                return;
            }

            $statistic->attributes['movie_id'] = $statistic->attributes['movie_id'] ?? $statistic->attributes['title_id'] ?? null;
            $statistic->attributes['vote_count'] = $statistic->attributes['vote_count'] ?? $statistic->attributes['rating_count'] ?? null;
            $statistic->attributes['aggregate_rating'] = $statistic->attributes['aggregate_rating'] ?? $statistic->attributes['average_rating'] ?? null;

            foreach ([
                'title_id',
                'rating_count',
                'average_rating',
                'review_count',
                'watchlist_count',
                'episodes_count',
                'awards_nominated_count',
                'awards_won_count',
                'metacritic_score',
                'metacritic_review_count',
            ] as $attribute) {
                unset($statistic->attributes[$attribute]);
            }
        });
    }

    public function getTable(): string
    {
        return Title::usesCatalogOnlySchema() ? 'movie_ratings' : parent::getTable();
    }

    public function getConnectionName(): ?string
    {
        return Title::usesCatalogOnlySchema() ? 'imdb_mysql' : parent::getConnectionName();
    }

    public function usesTimestamps(): bool
    {
        return Title::usesCatalogOnlySchema() ? false : parent::usesTimestamps();
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
        return $this->belongsTo(Title::class, Title::usesCatalogOnlySchema() ? 'movie_id' : 'title_id', 'id');
    }

    public function getTitleIdAttribute(): int
    {
        return (int) ($this->attributes['title_id'] ?? $this->attributes['movie_id'] ?? 0);
    }

    public function getMovieIdAttribute(): int
    {
        return (int) ($this->attributes['movie_id'] ?? $this->attributes['title_id'] ?? 0);
    }

    public function getRatingCountAttribute(): int
    {
        return (int) ($this->attributes['rating_count'] ?? $this->attributes['vote_count'] ?? 0);
    }

    public function getVoteCountAttribute(): int
    {
        return (int) ($this->attributes['vote_count'] ?? $this->attributes['rating_count'] ?? 0);
    }

    public function getAverageRatingAttribute(): ?float
    {
        $rating = $this->attributes['average_rating'] ?? $this->attributes['aggregate_rating'] ?? null;

        return $rating !== null ? (float) $rating : null;
    }

    public function getAggregateRatingAttribute(): ?float
    {
        $rating = $this->attributes['aggregate_rating'] ?? $this->attributes['average_rating'] ?? null;

        return $rating !== null ? (float) $rating : null;
    }
}
