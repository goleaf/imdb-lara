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
            ->select([
                'name_basics.id',
                'name_basics.nconst',
                'name_basics.imdb_id',
                'name_basics.primaryname',
                'name_basics.displayName',
                'name_basics.alternativeNames',
                'name_basics.primaryProfessions',
                'name_basics.biography',
                'name_basics.birthLocation',
                'name_basics.deathLocation',
                'name_basics.primaryImage_url',
                'name_basics.primaryImage_width',
                'name_basics.primaryImage_height',
            ])
            ->addSelect([
                'popularity_rank' => NameBasicMeterRanking::query()
                    ->select('current_rank')
                    ->whereColumn('name_basic_meter_rankings.name_basic_id', 'name_basics.id')
                    ->limit(1),
            ])
            ->published()
            ->withCount(['credits', 'awardNominations'])
            ->withExists('meterRanking')
            ->with([
                'personImages:name_basic_id,position,url,width,height,type',
                'professionTerms:id,name',
                'meterRanking:name_basic_id,current_rank,change_direction,difference',
            ]);

        $query->matchingSearch($search);

        if ($profession !== null) {
            $query->whereHas(
                'professionTerms',
                fn (Builder $builder) => $builder->where('professions.name', $profession),
            );
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
