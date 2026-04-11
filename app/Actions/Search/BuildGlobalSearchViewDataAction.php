<?php

namespace App\Actions\Search;

use App\Models\InterestCategory;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;

class BuildGlobalSearchViewDataAction
{
    public function __construct(
        private GetGlobalSearchSuggestionsAction $getGlobalSearchSuggestions,
        private ResolveSearchTopMatchAction $resolveSearchTopMatch,
        private PruneTopCatalogMatchAction $pruneTopCatalogMatch,
    ) {}

    /**
     * @return array{
     *     hasSearchTerm: bool,
     *     hasSuggestions: bool,
     *     visibleSections: list<array{
     *         chipClass: string,
     *         copy: string,
     *         icon: string,
     *         items: Collection<int, InterestCategory|Person|Title>,
     *         key: 'interestCategories'|'people'|'titles',
     *         label: string,
     *         panelClass: string
     *     }>,
     *     suggestions: array{
     *         interestCategories: Collection<int, InterestCategory>,
     *         people: Collection<int, Person>,
     *         titles: Collection<int, Title>
     *     },
     *     topSuggestion: array{
     *         record: Person|Title|null,
     *         type: 'person'|'title'|null
     *     },
     *     trimmedQuery: string
     * }
     */
    public function handle(?string $query, int $perGroup = 4): array
    {
        $trimmedQuery = trim((string) $query);
        $suggestions = $this->getGlobalSearchSuggestions->handle($trimmedQuery, $perGroup + 1);
        $topSuggestion = $this->resolveSearchTopMatch->handle(
            $trimmedQuery,
            $suggestions['titles']->first(),
            $suggestions['people']->first(),
        );

        $visibleSuggestions = [
            'interestCategories' => $suggestions['interestCategories']
                ->take($perGroup)
                ->values(),
            'people' => $this->pruneTopCatalogMatch->handle(
                $suggestions['people'],
                $topSuggestion['type'] === 'person' ? $topSuggestion['record'] : null,
                $perGroup,
            ),
            'titles' => $this->pruneTopCatalogMatch->handle(
                $suggestions['titles'],
                $topSuggestion['type'] === 'title' ? $topSuggestion['record'] : null,
                $perGroup,
            ),
        ];
        $visibleSections = $this->buildVisibleSections($visibleSuggestions);

        return [
            'hasSearchTerm' => mb_strlen($trimmedQuery) >= 2,
            'hasSuggestions' => $visibleSections !== [],
            'suggestions' => $visibleSuggestions,
            'visibleSections' => $visibleSections,
            'topSuggestion' => $topSuggestion,
            'trimmedQuery' => $trimmedQuery,
        ];
    }

    /**
     * @param  array{
     *     interestCategories: Collection<int, InterestCategory>,
     *     people: Collection<int, Person>,
     *     titles: Collection<int, Title>
     * }  $visibleSuggestions
     * @return list<array{
     *     chipClass: string,
     *     copy: string,
     *     icon: string,
     *     items: Collection<int, InterestCategory|Person|Title>,
     *     key: 'interestCategories'|'people'|'titles',
     *     label: string,
     *     panelClass: string
     * }>
     */
    private function buildVisibleSections(array $visibleSuggestions): array
    {
        /** @var array<int, array{
         *     chipClass: string,
         *     copy: string,
         *     icon: string,
         *     items: Collection<int, InterestCategory|Person|Title>,
         *     key: 'interestCategories'|'people'|'titles',
         *     label: string,
         *     panelClass: string
         * }> $sections
         */
        $sections = collect([
            [
                'key' => 'titles',
                'label' => 'Titles',
                'icon' => 'film',
                'copy' => 'Poster-led matches with year and type.',
                'panelClass' => 'sb-search-panel--titles',
                'chipClass' => 'sb-search-chip--accent',
                'items' => $visibleSuggestions['titles'],
            ],
            [
                'key' => 'people',
                'label' => 'People',
                'icon' => 'user',
                'copy' => 'Portrait-first profiles with profession cues.',
                'panelClass' => 'sb-search-panel--people',
                'chipClass' => 'sb-search-chip--people',
                'items' => $visibleSuggestions['people'],
            ],
            [
                'key' => 'interestCategories',
                'label' => 'Themes',
                'icon' => 'squares-2x2',
                'copy' => 'Interest-category lanes from the imported discovery graph.',
                'panelClass' => 'sb-search-panel--people',
                'chipClass' => 'sb-search-chip--people',
                'items' => $visibleSuggestions['interestCategories'],
            ],
        ])
            ->filter(fn (array $section): bool => $section['items']->isNotEmpty())
            ->values()
            ->all();

        return $sections;
    }
}
