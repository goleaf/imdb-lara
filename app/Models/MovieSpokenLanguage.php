<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieSpokenLanguage extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_spoken_languages';

    protected $primaryKey = 'movie_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_id', 'language_code'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'language_code',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }
}
