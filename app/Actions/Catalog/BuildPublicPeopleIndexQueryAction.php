<?php

namespace App\Actions\Catalog;

use App\Models\NameBasicMeterRanking;
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
            ->addSelect([
                'popularity_rank' => NameBasicMeterRanking::query()
                    ->select('current_rank')
                    ->whereColumn('name_basic_meter_rankings.name_basic_id', 'name_basics.id')
                    ->limit(1),
            ])
            ->published()
            ->withDirectoryMetrics()
            ->withDirectoryRelations();

        $query->matchingSearch($search);

        if ($profession !== null) {
            $query->inProfession($profession);
        }

        return match ($sort) {
            'name' => $query->orderBy('displayName')->orderBy('primaryname'),
            'credits' => $query->orderByDesc('credits_count')->orderBy('displayName'),
            'awards' => $query->orderByDesc('award_nominations_count')->orderByDesc('credits_count')->orderBy('displayName'),
            default => $query
                ->orderByDesc('meter_ranking_exists')
                ->orderBy('popularity_rank')
                ->orderByDesc('award_nominations_count')
                ->orderByDesc('credits_count')
                ->orderBy('displayName'),
        };
    }
}
