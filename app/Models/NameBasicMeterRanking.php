<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameBasicMeterRanking extends ImdbModel
{
    protected $table = 'name_basic_meter_rankings';

    protected $primaryKey = 'name_basic_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'current_rank',
        'change_direction',
        'difference',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'current_rank' => 'integer',
            'difference' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }
}
