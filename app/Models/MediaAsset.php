<?php

namespace App\Models;

use App\MediaKind;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    use SoftDeletes;

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
        'provider',
        'provider_key',
        'language',
        'duration_seconds',
        'metadata',
        'is_primary',
        'position',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'is_primary' => 'boolean',
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
