<?php

namespace App\Actions\Search;

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
     *     suggestions: array{
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

        return [
            'hasSearchTerm' => mb_strlen($trimmedQuery) >= 2,
            'hasSuggestions' => $visibleSuggestions['titles']->isNotEmpty() || $visibleSuggestions['people']->isNotEmpty(),
            'suggestions' => $visibleSuggestions,
            'topSuggestion' => $topSuggestion,
            'trimmedQuery' => $trimmedQuery,
        ];
    }
}
