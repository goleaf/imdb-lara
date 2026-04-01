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
        'metacritic_score',
        'metacritic_review_count',
        'rating_distribution',
        'review_count',
        'watchlist_count',
        'episodes_count',
        'awards_nominated_count',
        'awards_won_count',
    ];

    protected function casts(): array
    {
        return [
            'average_rating' => 'decimal:2',
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
        /** @var array<int|string, mixed>|null $distribution */
        $distribution = $this->rating_distribution;

        return self::normalizeRatingDistribution($distribution);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
