<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use GeneratesSlugs;

    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'series_id',
        'name',
        'slug',
        'season_number',
        'summary',
        'release_year',
        'meta_title',
        'meta_description',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'series_id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }
}
