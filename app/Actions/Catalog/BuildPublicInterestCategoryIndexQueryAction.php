<?php

namespace App\Actions\Catalog;

use App\Models\InterestCategory;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicInterestCategoryIndexQueryAction
{
    /**
     * @param  array{search?: string, showImages?: bool, sort?: string}  $filters
     */
    public function handle(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $showImages = (bool) ($filters['showImages'] ?? false);
        $sort = (string) ($filters['sort'] ?? 'popular');

        $query = InterestCategory::query()
            ->selectDirectoryColumns()
            ->withDirectoryMetrics()
            ->matchingSearch($search);

        if ($showImages) {
            $query->withDirectoryPreviewImage();
        }

        return match ($sort) {
            'name' => $query->orderBy('interest_categories.name'),
            'interests' => $query
                ->orderByDesc('interests_count')
                ->orderByDesc('title_linked_interests_count')
                ->orderBy('interest_categories.name'),
            'subgenres' => $query
                ->orderByDesc('subgenre_interests_count')
                ->orderByDesc('title_linked_interests_count')
                ->orderBy('interest_categories.name'),
            default => $query
                ->orderByDesc('title_linked_interests_count')
                ->orderByDesc('interests_count')
                ->orderBy('interest_categories.name'),
        };
    }
}
