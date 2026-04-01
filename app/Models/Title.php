<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlugs;
use App\TitleType;
use Database\Factories\TitleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Title extends Model
{
    /** @use HasFactory<TitleFactory> */
    use GeneratesSlugs;

    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'original_name',
        'slug',
        'title_type',
        'release_year',
        'end_year',
        'release_date',
        'runtime_minutes',
        'age_rating',
        'plot_outline',
        'synopsis',
        'tagline',
        'origin_country',
        'original_language',
        'popularity_rank',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'title_type' => TitleType::class,
            'release_date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeForType(Builder $query, TitleType $type): Builder
    {
        return $query->where('title_type', $type);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class)->orderBy('billing_order');
    }

    public function mediaAssets(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable')->orderBy('position');
    }

    public function statistic(): HasOne
    {
        return $this->hasOne(TitleStatistic::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function listItems(): HasMany
    {
        return $this->hasMany(ListItem::class);
    }
}
