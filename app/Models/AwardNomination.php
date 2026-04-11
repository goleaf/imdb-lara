<?php

namespace App\Models;

use Database\Factories\AwardNominationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;

class AwardNomination extends Model
{
    /** @use HasFactory<AwardNominationFactory> */
    use HasFactory;

    protected $fillable = [
        'award_event_id',
        'title_id',
        'person_id',
        'company_id',
        'episode_id',
        'credited_name',
        'details',
        'sort_order',
        'movie_id',
        'event_imdb_id',
        'award_category_id',
        'award_year',
        'text',
        'is_winner',
        'winner_rank',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'award_event_id' => 'integer',
            'title_id' => 'integer',
            'person_id' => 'integer',
            'company_id' => 'integer',
            'episode_id' => 'integer',
            'sort_order' => 'integer',
            'movie_id' => 'integer',
            'award_category_id' => 'integer',
            'award_year' => 'integer',
            'is_winner' => 'boolean',
            'winner_rank' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function usesCatalogOnlySchema(): bool
    {
        return Title::usesCatalogOnlySchema();
    }

    public function getTable(): string
    {
        return static::usesCatalogOnlySchema() ? 'movie_award_nominations' : 'award_nominations';
    }

    public function getConnectionName(): ?string
    {
        return static::usesCatalogOnlySchema() ? 'imdb_mysql' : null;
    }

    public function usesTimestamps(): bool
    {
        return static::usesCatalogOnlySchema() ? false : parent::usesTimestamps();
    }

    public function scopeForTitle(Builder $query, Title|int $title): Builder
    {
        $titleId = $title instanceof Title
            ? (int) $title->getKey()
            : $title;

        return $query->where(static::usesCatalogOnlySchema() ? 'movie_id' : 'title_id', $titleId);
    }

    public function scopeSelectTitleDetailColumns(Builder $query): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            return $query->select([
                'id',
                'movie_id',
                'event_imdb_id',
                'award_category_id',
                'award_year',
                'text',
                'is_winner',
                'winner_rank',
                'position',
            ]);
        }

        return $query->select([
            'id',
            'award_event_id',
            'award_category_id',
            'title_id',
            'person_id',
            'company_id',
            'episode_id',
            'credited_name',
            'details',
            'is_winner',
            'sort_order',
        ]);
    }

    public function scopeWithTitleDetailRelations(Builder $query): Builder
    {
        if (static::usesCatalogOnlySchema()) {
            return $query->with([
                'awardEvent:imdb_id,name',
                'awardCategory:id,name',
            ]);
        }

        return $query->with([
            'awardEvent:id,name,slug,award_id,year,edition,event_date,location',
            'awardCategory:id,name,slug,award_id,recipient_scope',
        ]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        if (! static::usesCatalogOnlySchema()) {
            return $query
                ->orderBy('sort_order')
                ->orderBy('id');
        }

        return $query
            ->orderBy('position')
            ->orderBy('id');
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
        if (preg_match('/-(?P<id>\d+)$/', (string) $value, $matches) === 1) {
            return $query->whereKey((int) $matches['id']);
        }

        return $query->whereKey((int) $value);
    }

    public function scopeForAwardCohort(Builder $query, self $awardNomination): Builder
    {
        if (! static::usesCatalogOnlySchema()) {
            return $query
                ->where('award_event_id', $awardNomination->award_event_id)
                ->where('award_category_id', $awardNomination->award_category_id);
        }

        return $query
            ->where('event_imdb_id', $awardNomination->event_imdb_id)
            ->where('award_category_id', $awardNomination->award_category_id)
            ->when(
                $awardNomination->award_year === null,
                fn (Builder $cohortQuery): Builder => $cohortQuery->whereNull('award_year'),
                fn (Builder $cohortQuery): Builder => $cohortQuery->where('award_year', $awardNomination->award_year),
            );
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(
            AwardEvent::class,
            static::usesCatalogOnlySchema() ? 'event_imdb_id' : 'award_event_id',
            static::usesCatalogOnlySchema() ? 'imdb_id' : 'id',
        );
    }

    public function awardCategory(): BelongsTo
    {
        return $this->belongsTo(AwardCategory::class, 'award_category_id', 'id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(
            Title::class,
            static::usesCatalogOnlySchema() ? 'movie_id' : 'title_id',
            'id',
        );
    }

    public function people(): Relation
    {
        if (! static::usesCatalogOnlySchema()) {
            return $this->belongsToMany(
                Person::class,
                'award_nominations',
                'id',
                'person_id',
                'id',
                'id',
            );
        }

        return $this->belongsToMany(Person::class, 'movie_award_nomination_nominees', 'movie_award_nomination_id', 'name_basic_id', 'id', 'id');
    }

    public function movieAwardNominationNominees(): HasMany
    {
        return $this->hasMany(MovieAwardNominationNominee::class, 'movie_award_nomination_id', 'id')
            ->orderBy('position');
    }

    public function movieAwardNominationTitles(): HasMany
    {
        return $this->hasMany(MovieAwardNominationTitle::class, 'movie_award_nomination_id', 'id')
            ->orderBy('position');
    }

    public function getPersonAttribute(): ?Person
    {
        if (! $this->relationLoaded('people')) {
            return null;
        }

        return $this->people->first();
    }

    public function getTitleIdAttribute(?int $value): ?int
    {
        return $value ?? (isset($this->attributes['movie_id']) ? (int) $this->attributes['movie_id'] : null);
    }

    public function getMovieIdAttribute(?int $value): ?int
    {
        return $value ?? (isset($this->attributes['title_id']) ? (int) $this->attributes['title_id'] : null);
    }

    public function getSortOrderAttribute(?int $value): ?int
    {
        return $value ?? (isset($this->attributes['position']) ? (int) $this->attributes['position'] : null);
    }

    public function getPositionAttribute(?int $value): ?int
    {
        return $value ?? (isset($this->attributes['sort_order']) ? (int) $this->attributes['sort_order'] : null);
    }

    public function getSlugAttribute(): string
    {
        return 'award-nomination-'.$this->id;
    }
}
