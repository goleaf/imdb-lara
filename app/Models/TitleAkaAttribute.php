<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleAkaAttribute extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'title_aka_attributes';

    protected $primaryKey = 'title_aka_id';

    public $incrementing = false;

    protected array $compositeKey = ['title_aka_id', 'aka_attribute_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_aka_id',
        'aka_attribute_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'title_aka_id' => 'integer',
            'aka_attribute_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function akaAttribute(): BelongsTo
    {
        return $this->belongsTo(AkaAttribute::class, 'aka_attribute_id', 'id');
    }

    public function titleAka(): BelongsTo
    {
        return $this->belongsTo(TitleAka::class, 'title_aka_id', 'id');
    }
}
