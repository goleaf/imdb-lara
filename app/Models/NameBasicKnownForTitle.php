<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameBasicKnownForTitle extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'name_basic_known_for_titles';

    protected $primaryKey = 'name_basic_id';

    public $incrementing = false;

    protected array $compositeKey = ['name_basic_id', 'title_basic_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'title_basic_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'title_basic_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }

    public function titleBasic(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'title_basic_id', 'id');
    }
}
