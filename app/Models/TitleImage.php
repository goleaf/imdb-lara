<?php

namespace App\Models;

use App\Enums\MediaKind;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleImage extends CatalogMediaAsset
{
    protected $table = 'movie_images';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'position',
        'url',
        'width',
        'height',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'position' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function getKindAttribute(): MediaKind
    {
        return match ($this->type) {
            'backdrop', 'background' => MediaKind::Backdrop,
            'still_frame' => MediaKind::Still,
            'poster' => MediaKind::Poster,
            default => MediaKind::Gallery,
        };
    }

    public function getAltTextAttribute(): string
    {
        return 'Title image';
    }

    public function getIsPrimaryAttribute(): bool
    {
        return (int) ($this->position ?? 0) === 1;
    }
}
