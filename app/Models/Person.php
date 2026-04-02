<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as SupportCollection;

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
        'imdb_id',
        'name',
        'alternate_names',
        'imdb_alternative_names',
        'imdb_primary_professions',
        'imdb_payload',
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
            'imdb_alternative_names' => 'array',
            'imdb_primary_professions' => 'array',
            'imdb_payload' => 'array',
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
                ->orWhere('imdb_id', 'like', '%'.$search.'%')
                ->orWhere('slug', 'like', '%'.$search.'%')
                ->orWhere('alternate_names', 'like', '%'.$search.'%')
                ->orWhere('imdb_alternative_names', 'like', '%'.$search.'%')
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

    /**
     * @return array<string, mixed>|null
     */
    public function imdbPayloadSection(string $key): ?array
    {
        $payload = data_get($this->imdb_payload, $key);

        return is_array($payload) ? $payload : null;
    }

    /**
     * @return list<string>
     */
    public function resolvedAlternateNames(): array
    {
        $storedNames = preg_split('/\s*\|\s*/', $this->alternate_names ?? '') ?: [];

        return collect([
            ...$storedNames,
            ...(is_array($this->imdb_alternative_names) ? $this->imdb_alternative_names : []),
        ])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    public function preferredHeadshot(): ?MediaAsset
    {
        /** @var iterable<array-key, mixed> $assets */
        $assets = $this->relationLoaded('mediaAssets')
            ? $this->mediaAssets
            : ($this->relationLoaded('personImages') ? $this->personImages : []);

        return MediaAsset::preferredFrom(
            $assets,
            MediaKind::Headshot,
            MediaKind::Gallery,
            MediaKind::Still,
        );
    }

    /**
     * @return EloquentCollection<int, PersonProfession>
     */
    public function previewProfessions(int $limit = 2): EloquentCollection
    {
        if (! $this->relationLoaded('professions')) {
            return new EloquentCollection;
        }

        /** @var EloquentCollection<int, PersonProfession> $professions */
        $professions = $this->professions
            ->filter(fn (PersonProfession $profession): bool => filled($profession->profession))
            ->unique('profession')
            ->take($limit)
            ->values();

        return $professions;
    }

    /**
     * @return list<string>
     */
    public function professionLabels(int $limit = 2): array
    {
        return $this->previewProfessions($limit)
            ->pluck('profession')
            ->filter()
            ->values()
            ->all();
    }

    public function primaryProfessionLabel(): string
    {
        return $this->professionLabels(1)[0] ?? ($this->known_for_department ?: 'Screenbase profile');
    }

    public function secondaryProfessionLabel(): string
    {
        return collect($this->professionLabels())
            ->skip(1)
            ->implode(' · ');
    }

    public function summaryText(): ?string
    {
        $summary = $this->short_biography ?: $this->biography;

        return filled($summary) ? (string) $summary : null;
    }

    /**
     * @return SupportCollection<string, EloquentCollection<int, MediaAsset>>
     */
    public function groupedMediaAssetsByKind(): SupportCollection
    {
        if (! $this->relationLoaded('mediaAssets')) {
            return collect();
        }

        return $this->mediaAssets->groupBy(fn (MediaAsset $mediaAsset): string => $mediaAsset->kind->value);
    }
}
