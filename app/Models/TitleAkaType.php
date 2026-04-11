<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleAkaType extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'title_aka_types';

    protected $primaryKey = 'title_aka_id';

    public $incrementing = false;

    protected array $compositeKey = ['title_aka_id', 'aka_type_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_aka_id',
        'aka_type_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'title_aka_id' => 'integer',
            'aka_type_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function akaType(): BelongsTo
    {
        return $this->belongsTo(AkaType::class, 'aka_type_id', 'id');
    }

    public function titleAka(): BelongsTo
    {
        return $this->belongsTo(TitleAka::class, 'title_aka_id', 'id');
    }
}
