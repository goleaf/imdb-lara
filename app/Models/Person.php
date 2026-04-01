<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use GeneratesSlugs;

    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'alternate_names',
        'slug',
        'biography',
        'short_biography',
        'known_for_department',
        'birth_date',
        'death_date',
        'birth_place',
        'death_place',
        'nationality',
        'popularity_rank',
        'meta_title',
        'meta_description',
        'search_keywords',
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

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $personQuery) use ($search): void {
            $personQuery
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('slug', 'like', '%'.$search.'%')
                ->orWhere('alternate_names', 'like', '%'.$search.'%')
                ->orWhere('search_keywords', 'like', '%'.$search.'%')
                ->orWhere('biography', 'like', '%'.$search.'%')
                ->orWhere('short_biography', 'like', '%'.$search.'%');
        });
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function professions(): HasMany
    {
        return $this->hasMany(PersonProfession::class)->orderBy('sort_order');
    }

    public function mediaAssets(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable')->ordered();
    }

    public function personImages(): MorphMany
    {
        return $this->morphMany(PersonImage::class, 'mediable')
            ->whereIn('kind', [
                MediaKind::Headshot,
                MediaKind::Gallery,
                MediaKind::Still,
            ])
            ->ordered();
    }

    public function awardNominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class);
    }

    public function contributions(): MorphMany
    {
        return $this->morphMany(Contribution::class, 'contributable');
    }
}
