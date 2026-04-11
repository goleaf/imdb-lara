<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameBasicAlternativeName extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'name_basic_alternative_names';

    protected $primaryKey = 'name_basic_id';

    public $incrementing = false;

    protected array $compositeKey = ['name_basic_id', 'alternative_name'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'alternative_name',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }
}
