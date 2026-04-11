<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function getPersonAttribute(): ?Person
    {
        if (! $this->relationLoaded('people')) {
            return null;
        }

        return $this->people->first();
    }
}
