<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameTriviaSummary extends ImdbModel
{
    protected $table = 'name_trivia_summaries';

    protected $primaryKey = 'name_basic_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'total_count',
        'next_page_token',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'total_count' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }
}
