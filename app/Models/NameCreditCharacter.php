<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameCreditCharacter extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'name_credit_characters';

    protected $primaryKey = 'name_credit_id';

    public $incrementing = false;

    protected array $compositeKey = ['name_credit_id', 'position'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_credit_id',
        'position',
        'character_name',
    ];

    protected function casts(): array
    {
        return [
            'name_credit_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nameCredit(): BelongsTo
    {
        return $this->belongsTo(NameCredit::class, 'name_credit_id', 'id');
    }
}
