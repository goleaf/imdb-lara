<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieAwardNominationNominee extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_award_nomination_nominees';

    protected $primaryKey = 'movie_award_nomination_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_award_nomination_id', 'name_basic_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_award_nomination_id',
        'name_basic_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_award_nomination_id' => 'integer',
            'name_basic_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'name_basic_id', 'id');
    }

    public function movieAwardNomination(): BelongsTo
    {
        return $this->belongsTo(MovieAwardNomination::class, 'movie_award_nomination_id', 'id');
    }

    public function awardNomination(): BelongsTo
    {
        return $this->belongsTo(AwardNomination::class, 'movie_award_nomination_id', 'id');
    }
}
