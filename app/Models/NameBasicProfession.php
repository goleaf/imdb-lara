<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameBasicProfession extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'name_basic_professions';

    protected $primaryKey = 'name_basic_id';

    public $incrementing = false;

    protected array $compositeKey = ['name_basic_id', 'profession_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'profession_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'profession_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class, 'profession_id', 'id');
    }
}
