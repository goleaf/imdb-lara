<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameTriviaEntry extends ImdbModel
{
    protected $table = 'name_trivia_entries';

    protected $primaryKey = 'imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'imdb_id',
        'name_basic_id',
        'text',
        'interest_count',
        'vote_count',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'interest_count' => 'integer',
            'vote_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }
}
