<?php

namespace App\Models;

use Database\Factories\CreditFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Credit extends Model
{
    /** @use HasFactory<CreditFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    public const CAST_CATEGORIES = [
        'actor',
        'actress',
        'archive_footage',
        'self',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_id',
        'person_id',
        'department',
        'job',
        'character_name',
        'billing_order',
        'is_principal',
        'person_profession_id',
        'episode_id',
        'credited_as',
        'imdb_source_group',
        'name_basic_id',
        'movie_id',
        'category',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'title_id' => 'integer',
            'person_id' => 'integer',
            'billing_order' => 'integer',
            'is_principal' => 'boolean',
            'person_profession_id' => 'integer',
            'episode_id' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(PersonProfession::class, 'person_profession_id');
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('credits.billing_order')
            ->orderBy('credits.id');
    }

    public function scopeCast(Builder $query): Builder
    {
        return $query->where('credits.department', 'Cast');
    }

    public function scopeCrew(Builder $query): Builder
    {
        return $query->where('credits.department', '!=', 'Cast');
    }

    public function scopeWithPersonPreview(Builder $query): Builder
    {
        return $query->with([
            'person' => fn ($personQuery) => $personQuery
                ->selectDirectoryColumns()
                ->withDirectoryRelations()
                ->withDirectoryMetrics(),
        ]);
    }

    public function getMovieIdAttribute(): int
    {
        return (int) $this->title_id;
    }

    public function getNameBasicIdAttribute(): int
    {
        return (int) $this->person_id;
    }

    public function getCategoryAttribute(): string
    {
        if (filled($this->imdb_source_group)) {
            return (string) $this->imdb_source_group;
        }

        return match ($this->department) {
            'Cast' => 'actor',
            'Directing' => 'director',
            'Writing' => 'writer',
            'Production' => 'producer',
            'Music' => 'composer',
            'Camera' => 'cinematographer',
            'Editing' => 'editor',
            default => Str::of((string) $this->job)->snake()->toString(),
        };
    }

    public function getPositionAttribute(mixed $value): int
    {
        return (int) ($this->billing_order ?? $value ?? 0);
    }

    public function setNameBasicIdAttribute(mixed $value): void
    {
        $this->attributes['person_id'] = $value;
    }

    public function setMovieIdAttribute(mixed $value): void
    {
        $this->attributes['title_id'] = $value;
    }

    public function setCategoryAttribute(?string $value): void
    {
        if (! filled($value)) {
            return;
        }

        $this->attributes['imdb_source_group'] = $value;
        $this->attributes['job'] = $this->attributes['job'] ?? Str::headline((string) $value);
        $this->attributes['department'] = match (Str::of($value)->replace('_', ' ')->lower()->toString()) {
            'actor', 'actress', 'archive footage', 'self' => 'Cast',
            'director' => 'Directing',
            'writer' => 'Writing',
            'producer', 'executive producer' => 'Production',
            'composer', 'soundtrack' => 'Music',
            'cinematographer' => 'Camera',
            'editor' => 'Editing',
            default => 'Crew',
        };
    }

    public function setPositionAttribute(mixed $value): void
    {
        $this->attributes['billing_order'] = $value;
    }
}
