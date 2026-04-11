<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\Interest;
use App\Models\InterestCategory;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class LoadInterestCategoryDetailsAction
{
    /**
     * @return array{
     *     interestCategory: InterestCategory,
     *     linkedTitleCount: int,
     *     relatedInterests: Collection<int, array{
     *         interest: Interest,
     *         href: string,
     *         titleCountLabel: string,
     *         description: string|null
     *     }>,
     *     linkedTitles: Collection<int, Title>,
     *     seo: PageSeoData
     * }
     */
    public function handle(InterestCategory $interestCategory): array
    {
        $interestCategory->loadCount([
            'interests',
            'interests as title_linked_interests_count' => fn (Builder $interestQuery) => $interestQuery->linkedToPublishedTitles(),
            'interests as subgenre_interests_count' => fn (Builder $interestQuery) => $interestQuery->where('interests.is_subgenre', true),
        ]);

        $interestCategory->load([
            'interests' => fn ($interestQuery) => $interestQuery
                ->select(['interests.imdb_id', 'interests.name', 'interests.description', 'interests.is_subgenre'])
                ->withCount([
                    'titles as catalog_titles_count' => fn ($movieQuery) => $movieQuery->publishedCatalog(),
                ])
                ->orderBy('interest_category_interests.position')
                ->orderBy('interests.name'),
        ]);

        $relatedInterests = $interestCategory->interests
            ->filter(fn (Interest $interest): bool => filled($interest->name))
            ->take(18)
            ->map(function (Interest $interest): array {
                $linkedTitleCount = (int) ($interest->catalog_titles_count ?? 0);

                return [
                    'interest' => $interest,
                    'href' => route('public.search', ['q' => (string) $interest->name]),
                    'titleCountLabel' => Number::format($linkedTitleCount).' linked '.Str::plural('title', $linkedTitleCount),
                    'description' => filled($interest->description)
                        ? Str::of((string) $interest->description)->squish()->limit(120)->toString()
                        : null,
                ];
            })
            ->values();

        $linkedTitlesQuery = Title::query()
            ->selectCatalogCardColumns()
            ->publishedCatalog()
            ->forInterestCategory($interestCategory)
            ->withMatchedInterestCount($interestCategory)
            ->withCatalogCardRelations()
            ->orderByDesc('matched_interest_count')
            ->orderByDesc(Title::catalogColumn('release_year'))
            ->orderBy(Title::catalogColumn('name'));

        $linkedTitleCount = (clone $linkedTitlesQuery)->count();

        return [
            'interestCategory' => $interestCategory,
            'linkedTitleCount' => $linkedTitleCount,
            'relatedInterests' => $relatedInterests,
            'linkedTitles' => $linkedTitlesQuery
                ->limit(12)
                ->get(),
            'seo' => new PageSeoData(
                title: $interestCategory->name.' Interest Category',
                description: 'Browse interests and linked titles grouped under '.$interestCategory->name.'.',
                canonical: route('public.interest-categories.show', $interestCategory),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Interest Categories', 'href' => route('public.interest-categories.index')],
                    ['label' => $interestCategory->name],
                ],
                paginationPageName: null,
            ),
        ];
    }
}
