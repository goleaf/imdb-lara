<?php

namespace App\Models;

use App\Enums\MediaKind;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonImage extends CatalogMediaAsset
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
            'name_basic_id' => 'integer',
            'position' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'name_basic_id', 'id');
    }

    public function getKindAttribute(): MediaKind
    {
        return match ($this->type) {
            'still_frame' => MediaKind::Still,
            'poster', 'publicity', 'product' => MediaKind::Headshot,
            default => MediaKind::Gallery,
        };
    }

    public function getAltTextAttribute(): string
    {
        return 'Person image';
    }

    public function getIsPrimaryAttribute(): bool
    {
        return (int) ($this->position ?? 0) === 1;
    }
}
