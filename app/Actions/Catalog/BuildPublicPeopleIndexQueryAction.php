<?php

namespace App\Actions\Catalog;

use App\Enums\MediaKind;
use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicPeopleIndexQueryAction
{
    /**
     * @param  array{search?: string, profession?: string|null, sort?: string}  $filters
     */
    public function handle(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $profession = filled($filters['profession'] ?? null)
            ? (string) $filters['profession']
            : null;
        $sort = (string) ($filters['sort'] ?? 'popular');

        $query = Person::query()
            ->select([
                'id',
                'name',
                'alternate_names',
                'slug',
                'biography',
                'short_biography',
                'known_for_department',
                'birth_date',
                'birth_place',
                'nationality',
                'popularity_rank',
                'is_published',
            ])
            ->published()
            ->withCount(['credits', 'awardNominations'])
            ->with([
                'mediaAssets' => fn ($query) => $query
                    ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position', 'is_primary'])
                    ->where('kind', MediaKind::Headshot)
                    ->orderBy('position')
                    ->limit(1),
                'professions' => fn ($query) => $query
                    ->select(['id', 'person_id', 'department', 'profession', 'is_primary', 'sort_order'])
                    ->orderBy('sort_order'),
            ]);

        $query->matchingSearch($search);

        if ($profession) {
            $query->whereHas('professions', fn (Builder $builder) => $builder->where('profession', $profession));
        }

        return match ($sort) {
            'name' => $query->orderBy('name'),
            'credits' => $query->orderByDesc('credits_count')->orderBy('name'),
            'awards' => $query->orderByDesc('award_nominations_count')->orderBy('name'),
            default => $query->orderBy('popularity_rank')->orderBy('name'),
        };
    }
}
