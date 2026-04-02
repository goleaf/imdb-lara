<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\TitleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as SupportCollection;

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
        'imdb_id',
        'name',
        'original_name',
        'slug',
        'sort_title',
        'title_type',
        'imdb_type',
        'release_year',
        'end_year',
        'release_date',
        'runtime_minutes',
        'runtime_seconds',
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
        'imdb_genres',
        'imdb_interests',
        'imdb_origin_countries',
        'imdb_spoken_languages',
        'imdb_payload',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'title_type' => TitleType::class,
            'release_date' => 'date',
            'imdb_genres' => 'array',
            'imdb_interests' => 'array',
            'imdb_origin_countries' => 'array',
            'imdb_spoken_languages' => 'array',
            'imdb_payload' => 'array',
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

    public function scopeWithoutEpisodes(Builder $query): Builder
    {
        return $query->where('title_type', '!=', TitleType::Episode);
    }

    public function scopePublishedCatalog(Builder $query): Builder
    {
        return $query->published()->withoutEpisodes();
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $titleQuery) use ($search): void {
            $titleQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('imdb_id', 'like', "%{$search}%")
                ->orWhere('original_name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('sort_title', 'like', "%{$search}%")
                ->orWhere('plot_outline', 'like', "%{$search}%")
                ->orWhere('synopsis', 'like', "%{$search}%")
                ->orWhere('search_keywords', 'like', "%{$search}%")
                ->orWhereHas('translations', function (Builder $translationQuery) use ($search): void {
                    $translationQuery
                        ->where('localized_title', 'like', "%{$search}%")
                        ->orWhere('localized_slug', 'like', "%{$search}%")
                        ->orWhere('localized_plot_outline', 'like', "%{$search}%")
                        ->orWhere('localized_synopsis', 'like', "%{$search}%");
                });
        });
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
        return $this->morphMany(MediaAsset::class, 'mediable')->ordered();
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
            ->ordered();
    }

    public function titleVideos(): MorphMany
    {
        return $this->morphMany(TitleVideo::class, 'mediable')
            ->whereIn('kind', [
                MediaKind::Trailer,
                MediaKind::Clip,
                MediaKind::Featurette,
            ])
            ->ordered();
    }

    public function statistic(): HasOne
    {
        return $this->hasOne(TitleStatistic::class);
    }

    public function imdbImport(): HasOne
    {
        return $this->hasOne(ImdbTitleImport::class, 'imdb_id', 'imdb_id');
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

    /**
     * @return array<string, mixed>|null
     */
    public function imdbPayloadSection(string $key): ?array
    {
        $payload = data_get($this->imdb_payload, $key);

        return is_array($payload) ? $payload : null;
    }

    public function typeLabel(): string
    {
        return $this->title_type->label();
    }

    public function typeIcon(): string
    {
        return $this->title_type->icon();
    }

    public function preferredPoster(): ?MediaAsset
    {
        /** @var iterable<array-key, mixed> $assets */
        $assets = $this->relationLoaded('mediaAssets')
            ? $this->mediaAssets
            : ($this->relationLoaded('titleImages') ? $this->titleImages : []);

        return MediaAsset::preferredFrom(
            $assets,
            MediaKind::Poster,
            MediaKind::Backdrop,
            MediaKind::Gallery,
            MediaKind::Still,
        );
    }

    public function preferredBackdrop(): ?MediaAsset
    {
        /** @var iterable<array-key, mixed> $assets */
        $assets = $this->relationLoaded('mediaAssets')
            ? $this->mediaAssets
            : ($this->relationLoaded('titleImages') ? $this->titleImages : []);

        return MediaAsset::preferredFrom(
            $assets,
            MediaKind::Backdrop,
            MediaKind::Poster,
            MediaKind::Gallery,
            MediaKind::Still,
        );
    }

    public function preferredDisplayImage(): ?MediaAsset
    {
        /** @var iterable<array-key, mixed> $assets */
        $assets = $this->relationLoaded('mediaAssets')
            ? $this->mediaAssets
            : ($this->relationLoaded('titleImages') ? $this->titleImages : []);

        return MediaAsset::preferredFrom(
            $assets,
            MediaKind::Still,
            MediaKind::Backdrop,
            MediaKind::Poster,
            MediaKind::Gallery,
        );
    }

    /**
     * @return EloquentCollection<int, Genre>
     */
    public function previewGenres(int $limit = 3): EloquentCollection
    {
        if (! $this->relationLoaded('genres')) {
            return new EloquentCollection;
        }

        /** @var EloquentCollection<int, Genre> $genres */
        $genres = $this->genres->take($limit);

        return $genres;
    }

    public function displayAverageRating(): ?float
    {
        if (! $this->relationLoaded('statistic') || ! $this->statistic?->average_rating) {
            return null;
        }

        return (float) $this->statistic->average_rating;
    }

    public function displayRatingCount(): int
    {
        if (! $this->relationLoaded('statistic') || ! $this->statistic) {
            return 0;
        }

        return (int) $this->statistic->rating_count;
    }

    public function displayReviewCount(): int
    {
        if ($this->relationLoaded('statistic') && $this->statistic) {
            return (int) $this->statistic->review_count;
        }

        return (int) ($this->published_reviews_count ?? 0);
    }

    public function originCountryCode(): ?string
    {
        if (! filled($this->origin_country)) {
            return null;
        }

        return str($this->origin_country)
            ->before(',')
            ->trim()
            ->upper()
            ->toString();
    }

    public function preferredVideo(): ?MediaAsset
    {
        /** @var iterable<array-key, mixed> $assets */
        $assets = $this->relationLoaded('titleVideos')
            ? $this->titleVideos
            : ($this->relationLoaded('mediaAssets') ? $this->mediaAssets : []);

        return MediaAsset::preferredFrom(
            $assets,
            MediaKind::Trailer,
            MediaKind::Featurette,
            MediaKind::Clip,
        );
    }

    public function summaryText(): ?string
    {
        $summary = $this->tagline ?: $this->synopsis ?: $this->plot_outline;

        return filled($summary) ? (string) $summary : null;
    }

    public function leadAwardNomination(): ?AwardNomination
    {
        if (! $this->relationLoaded('awardNominations')) {
            return null;
        }

        /** @var AwardNomination|null $nomination */
        $nomination = $this->awardNominations->sortByDesc('is_winner')->first();

        return $nomination;
    }

    /**
     * @return SupportCollection<string, EloquentCollection<int, MediaAsset>>
     */
    public function groupedMediaAssetsByKind(): SupportCollection
    {
        if (! $this->relationLoaded('mediaAssets')) {
            return collect();
        }

        return $this->mediaAssets->groupBy(fn (MediaAsset $mediaAsset): string => $mediaAsset->kind->value);
    }

    private static function statisticColumnSubquery(string $column): Builder
    {
        return TitleStatistic::query()
            ->select($column)
            ->whereColumn('title_statistics.title_id', 'titles.id')
            ->limit(1);
    }
}
