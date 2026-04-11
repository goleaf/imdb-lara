<?php

namespace App\Actions\Search;

use App\Enums\TitleType;
use App\Models\InterestCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetDiscoveryFilterOptionsAction
{
    public function __construct(
        protected GetSearchFilterOptionsAction $getSearchFilterOptions,
    ) {}

    /**
     * @return array{
     *     countries: list<array{value: string, label: string}>,
     *     genres: Collection<int, Genre>,
     *     interestCategories: Collection<int, InterestCategory>,
     *     languages: list<array{value: string, label: string}>,
     *     titleTypes: list<TitleType>,
     *     minimumRatings: list<int>,
     *     runtimeOptions: list<array{value: string, label: string}>,
     *     sortOptions: list<array{value: string, label: string}>,
     *     voteThresholdOptions: list<array{value: string, label: string}>,
     *     years: list<int>,
     *     awardOptions: list<array{value: string, label: string}>
     * }
     */
    public function handle(): array
    {
        return Cache::remember(
            'search:discovery-filter-options',
            now()->addMinutes(10),
            function (): array {
                $searchFilterOptions = $this->getSearchFilterOptions->handle();

                return [
                    'countries' => $searchFilterOptions['countries'],
                    'genres' => $searchFilterOptions['genres'],
                    'interestCategories' => $searchFilterOptions['interestCategories'],
                    'languages' => $searchFilterOptions['languages'],
                    'titleTypes' => TitleType::cases(),
                    'minimumRatings' => range(10, 1),
                    'runtimeOptions' => $searchFilterOptions['runtimeOptions'],
                    'sortOptions' => $searchFilterOptions['sortOptions'],
                    'voteThresholdOptions' => $searchFilterOptions['voteThresholdOptions'],
                    'years' => $searchFilterOptions['years'],
                    'awardOptions' => [
                        ['value' => 'winners', 'label' => 'Award winners'],
                        ['value' => 'nominated', 'label' => 'Award nominated'],
                    ],
                ];
            },
        );
    }
}
