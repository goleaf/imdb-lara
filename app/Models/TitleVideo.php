<?php

namespace App\Models;

use App\Enums\MediaKind;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleVideo extends CatalogMediaAsset
{
    protected $table = 'movie_videos';

    protected $primaryKey = 'imdb_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'imdb_id',
        'movie_id',
        'video_type_id',
        'name',
        'description',
        'width',
        'height',
        'runtime_seconds',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_id' => 'integer',
            'video_type_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'runtime_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function videoType(): BelongsTo
    {
        return $this->belongsTo(VideoType::class, 'video_type_id', 'id');
    }

    public function getKindAttribute(): MediaKind
    {
        $videoType = $this->relationLoaded('videoType')
            ? str((string) $this->videoType?->name)->lower()->toString()
            : null;

        return match ($videoType) {
            'clip', 'clips' => MediaKind::Clip,
            'featurette', 'featurettes' => MediaKind::Featurette,
            default => MediaKind::Trailer,
        };
    }

    public function getUrlAttribute(): ?string
    {
        if (! filled($this->imdb_id)) {
            return null;
        }

        return 'https://www.imdb.com/video/'.$this->imdb_id.'/';
    }

    public function getAltTextAttribute(): string
    {
        return filled($this->name) ? (string) $this->name : 'Title video';
    }

    public function getCaptionAttribute(): ?string
    {
        if (filled($this->description)) {
            return (string) $this->description;
        }

        return filled($this->name) ? (string) $this->name : null;
    }

    public function getDurationSecondsAttribute(): ?int
    {
        return $this->runtime_seconds ? (int) $this->runtime_seconds : null;
    }

    public function getIsPrimaryAttribute(): bool
    {
        return (int) ($this->position ?? 0) === 1;
    }

    public function getProviderAttribute(): string
    {
        return 'imdb';
    }

    public function getProviderKeyAttribute(): ?string
    {
        return filled($this->imdb_id) ? (string) $this->imdb_id : null;
    }
}
