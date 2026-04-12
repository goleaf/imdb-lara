<?php

namespace App\Models;

use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
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

    protected function casts(): array
    {
        return [
            'series_id' => 'integer',
            'season_number' => 'integer',
            'release_year' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getRouteKey(): string
    {
        return $this->slug;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field !== null) {
            return $query->where($field, $value);
        }

        $series = request()->route('series');

        if ($series instanceof Title) {
            $query->where('series_id', $series->getKey());
        }

        return $query->where(function ($seasonQuery) use ($value): void {
            $seasonQuery->where('slug', (string) $value);

            if (is_numeric($value)) {
                $seasonQuery->orWhere($this->qualifyColumn($this->getKeyName()), (int) $value);
            }
        });
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(LocalTitle::class, 'series_id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)
            ->orderBy('episode_number')
            ->orderBy('id');
    }
}
