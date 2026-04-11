<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitlePrincipal extends ImdbModel
{
    protected $table = 'title_principals';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tconst',
        'ordering',
        'nconst',
        'category',
        'job',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'ordering' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'nconst', 'nconst');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'tconst', 'tconst');
    }
}
