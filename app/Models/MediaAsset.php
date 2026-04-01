<?php

namespace App\Models;

use App\MediaKind;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'kind',
        'url',
        'alt_text',
        'caption',
        'width',
        'height',
        'is_primary',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'is_primary' => 'boolean',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
