<?php

namespace App\Actions\Catalog;

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
            ->selectDirectoryColumns()
            ->published()
            ->withDirectoryMetrics()
            ->withDirectoryRelations();

        $query->matchingSearch($search);

        if ($profession !== null) {
            $query->inProfession($profession);
        }

        $nameColumn = Person::catalogColumn('name');

        if (Person::usesCatalogOnlySchema()) {
            return match ($sort) {
                'name' => $query->orderBy($nameColumn),
                'credits' => $query->orderByDesc('credits_count')->orderBy($nameColumn),
                'awards' => $query->orderByDesc('credits_count')->orderBy($nameColumn),
                default => $query
                    ->orderBy('popularity_rank')
                    ->orderByDesc('credits_count')
                    ->orderBy($nameColumn),
            };
        }

        return match ($sort) {
            'name' => $query->orderBy($nameColumn),
            'credits' => $query->orderByDesc('credits_count')->orderBy($nameColumn),
            'awards' => $query->orderByDesc('award_nominations_count')->orderByDesc('credits_count')->orderBy($nameColumn),
            default => $query
                ->orderBy('popularity_rank')
                ->orderByDesc('award_nominations_count')
                ->orderByDesc('credits_count')
                ->orderBy($nameColumn),
        };
    }
}
