<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\TitleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Title extends Model
{
    /** @use HasFactory<TitleFactory> */
    use GeneratesSlugs;

    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'original_name',
        'slug',
        'sort_title',
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
        'canonical_title_id',
        'meta_title',
        'meta_description',
        'search_keywords',
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

    public function scopeRatedAtLeast(Builder $query, int $minimumVotes = 1): Builder
    {
        return $query->whereHas(
            'statistic',
            fn (Builder $statisticQuery) => $statisticQuery->where('rating_count', '>=', $minimumVotes),
        );
    }

    public function scopeOrderByTopRated(Builder $query, int $minimumVotes = 1): Builder
    {
        return $query->ratedAtLeast($minimumVotes)
            ->orderByDesc(self::statisticColumnSubquery('average_rating'))
            ->orderByDesc(self::statisticColumnSubquery('rating_count'))
            ->orderBy('name');
    }

    public function scopeOrderByTrending(Builder $query): Builder
    {
        return $query->whereHas('statistic')
            ->orderByDesc(self::statisticColumnSubquery('watchlist_count'))
            ->orderByDesc(self::statisticColumnSubquery('review_count'))
            ->orderByDesc(self::statisticColumnSubquery('rating_count'))
            ->orderBy('popularity_rank')
            ->orderBy('name');
    }

    public function canonicalTitle(): BelongsTo
    {
        return $this->belongsTo(self::class, 'canonical_title_id');
    }

    public function alternateTitles(): HasMany
    {
        return $this->hasMany(self::class, 'canonical_title_id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('relationship', 'credited_as', 'is_primary', 'sort_order')
            ->withTimestamps();
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class)->orderBy('billing_order');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(TitleTranslation::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class, 'series_id')->orderBy('season_number');
    }

    public function seriesEpisodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'series_id')
            ->orderBy('season_number')
            ->orderBy('episode_number');
    }

    public function episodeMeta(): HasOne
    {
        return $this->hasOne(Episode::class);
    }

    public function mediaAssets(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable')->orderBy('position');
    }

    public function titleImages(): MorphMany
    {
        return $this->morphMany(TitleImage::class, 'mediable')
            ->whereIn('kind', [
                MediaKind::Poster,
                MediaKind::Backdrop,
                MediaKind::Gallery,
                MediaKind::Still,
            ])
            ->orderBy('position');
    }

    public function titleVideos(): MorphMany
    {
        return $this->morphMany(TitleVideo::class, 'mediable')
            ->whereIn('kind', [
                MediaKind::Trailer,
                MediaKind::Clip,
                MediaKind::Featurette,
            ])
            ->orderBy('position');
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

    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(TitleRelationship::class, 'from_title_id');
    }

    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(TitleRelationship::class, 'to_title_id');
    }

    public function awardNominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class);
    }

    public function contributions(): MorphMany
    {
        return $this->morphMany(Contribution::class, 'contributable');
    }

    private static function statisticColumnSubquery(string $column): Builder
    {
        return TitleStatistic::query()
            ->select($column)
            ->whereColumn('title_statistics.title_id', 'titles.id')
            ->limit(1);
    }
}
