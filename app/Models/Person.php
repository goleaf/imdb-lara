<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use GeneratesSlugs;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'biography',
        'known_for_department',
        'birth_date',
        'death_date',
        'birth_place',
        'popularity_rank',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'death_date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function mediaAssets(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable')->orderBy('position');
    }
}
