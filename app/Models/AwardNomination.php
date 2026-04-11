<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardNomination extends Model
{
    protected $connection = 'imdb_mysql';

    protected $table = 'movie_award_nominations';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
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
            'movie_id' => 'integer',
            'award_category_id' => 'integer',
            'award_year' => 'integer',
            'is_winner' => 'boolean',
            'winner_rank' => 'integer',
            'position' => 'integer',
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
        if (preg_match('/-(?P<id>\d+)$/', (string) $value, $matches) === 1) {
            return $query->whereKey((int) $matches['id']);
        }

        return $query->whereKey((int) $value);
    }

    public function scopeForAwardCohort(Builder $query, self $awardNomination): Builder
    {
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
        return $this->belongsTo(AwardEvent::class, 'event_imdb_id', 'imdb_id');
    }

    public function awardCategory(): BelongsTo
    {
        return $this->belongsTo(AwardCategory::class, 'award_category_id', 'id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function people(): BelongsToMany
    {
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

    public function getSlugAttribute(): string
    {
        return 'award-nomination-'.$this->id;
    }
}
