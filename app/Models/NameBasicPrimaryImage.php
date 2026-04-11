<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameBasicPrimaryImage extends ImdbModel
{
    protected $table = 'name_basic_primary_images';

    protected $primaryKey = 'name_basic_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'url',
        'width',
        'height',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }
}
