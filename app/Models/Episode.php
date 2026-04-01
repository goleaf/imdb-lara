<?php

namespace App\Models;

use Database\Factories\EpisodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Episode extends Model
{
    /** @use HasFactory<EpisodeFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_id',
        'series_id',
        'season_id',
        'season_number',
        'episode_number',
        'absolute_number',
        'production_code',
        'aired_at',
    ];

    protected function casts(): array
    {
        return [
            'aired_at' => 'date',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'series_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class)->orderBy('billing_order');
    }
}
