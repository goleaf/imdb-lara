<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credit extends Model
{
    protected $connection = 'imdb_mysql';

    protected $table = 'name_credits';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'movie_id',
        'category',
        'episode_count',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'movie_id' => 'integer',
            'episode_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'movie_id', 'id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'name_basic_id', 'id');
    }

    public function getTitleIdAttribute(): int
    {
        return (int) $this->movie_id;
    }

    public function getPersonIdAttribute(): int
    {
        return (int) $this->name_basic_id;
    }

    public function getDepartmentAttribute(): string
    {
        return match ($this->category) {
            'actor', 'actress', 'archive_footage', 'self' => 'Cast',
            'director' => 'Directing',
            'writer' => 'Writing',
            'producer', 'executive' => 'Production',
            'composer', 'music_department', 'soundtrack' => 'Music',
            'cinematographer' => 'Camera',
            'editor' => 'Editing',
            'thanks' => 'Thanks',
            default => str((string) $this->category)->headline()->toString(),
        };
    }

    public function getJobAttribute(): string
    {
        return str((string) $this->category)->headline()->toString();
    }

    public function getCharacterNameAttribute(): ?string
    {
        return null;
    }

    public function getBillingOrderAttribute(): int
    {
        return (int) ($this->position ?? 0);
    }

    public function getIsPrincipalAttribute(): bool
    {
        return (int) ($this->position ?? 0) <= 5;
    }

    public function getCreditedAsAttribute(): ?string
    {
        return null;
    }

    public function getImdbSourceGroupAttribute(): ?string
    {
        return $this->category ? (string) $this->category : null;
    }
}
