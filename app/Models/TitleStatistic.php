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
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
