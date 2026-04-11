<?php

namespace App\Actions\Catalog;

use App\Models\Interest;
use App\Models\InterestCategory;
use Illuminate\Support\Facades\Cache;

class GetInterestCategoryDirectorySnapshotAction
{
    /**
     * @return array{
     *     categoryCount: int,
     *     interestCount: int,
     *     titleLinkedInterestCount: int,
     *     subgenreInterestCount: int,
     *     topCategories: list<array{
     *         id: int,
     *         name: string,
     *         href: string,
     *         interestsCount: int,
     *         titleLinkedInterestsCount: int
     *     }>
     * }
     */
    public function handle(): array
    {
        return Cache::remember(
            'catalog:interest-category-directory-snapshot:v1',
            now()->addMinutes(10),
            function (): array {
                $topCategories = InterestCategory::query()
                    ->selectDirectoryColumns()
                    ->withDirectoryMetrics()
                    ->orderByDesc('title_linked_interests_count')
                    ->orderByDesc('interests_count')
                    ->orderBy('interest_categories.name')
                    ->limit(6)
                    ->get()
                    ->map(fn (InterestCategory $interestCategory): array => [
                        'id' => (int) $interestCategory->getKey(),
                        'name' => (string) $interestCategory->name,
                        'href' => route('public.interest-categories.show', $interestCategory),
                        'interestsCount' => $interestCategory->interestCount(),
                        'titleLinkedInterestsCount' => $interestCategory->titleLinkedInterestCount(),
                    ])
                    ->all();

                return [
                    'categoryCount' => InterestCategory::query()->count(),
                    'interestCount' => Interest::query()->count(),
                    'titleLinkedInterestCount' => Interest::query()->linkedToPublishedTitles()->count(),
                    'subgenreInterestCount' => Interest::query()
                        ->where('interests.is_subgenre', true)
                        ->count(),
                    'topCategories' => $topCategories,
                ];
            },
        );
    }
}
