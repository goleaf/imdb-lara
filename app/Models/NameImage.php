<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameImage extends ImdbModel
{
    protected $table = 'name_images';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'position',
        'url',
        'width',
        'height',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name_basic_id' => 'integer',
            'position' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }
}
