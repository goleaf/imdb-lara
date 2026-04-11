<?php

namespace App\Livewire\Search;

use App\Actions\Search\BuildDiscoveryQueryAction;
use App\Actions\Search\GetDiscoveryFilterOptionsAction;
use App\Actions\Search\GetDiscoveryTitleSuggestionsAction;
use App\Enums\TitleType;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DiscoveryFilters extends Component
{
    use WithPagination;

    protected BuildDiscoveryQueryAction $buildDiscoveryQuery;

    protected GetDiscoveryFilterOptionsAction $getDiscoveryFilterOptions;

    protected GetDiscoveryTitleSuggestionsAction $getDiscoveryTitleSuggestions;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public ?string $genre = null;

    #[Url]
    public ?string $type = null;

    #[Url]
    public string $sort = 'popular';

    #[Url]
    public ?string $minimumRating = null;

    #[Url]
    public ?string $yearFrom = null;

    #[Url]
    public ?string $yearTo = null;

    #[Url]
    public ?string $votesMin = null;

    #[Url]
    public ?string $language = null;

    #[Url]
    public ?string $country = null;

    #[Url]
    public ?string $runtime = null;

    #[Url]
    public ?string $awards = null;

    public function boot(
        BuildDiscoveryQueryAction $buildDiscoveryQuery,
        GetDiscoveryFilterOptionsAction $getDiscoveryFilterOptions,
        GetDiscoveryTitleSuggestionsAction $getDiscoveryTitleSuggestions,
    ): void {
        $this->buildDiscoveryQuery = $buildDiscoveryQuery;
        $this->getDiscoveryFilterOptions = $getDiscoveryFilterOptions;
        $this->getDiscoveryTitleSuggestions = $getDiscoveryTitleSuggestions;
    }

    public function updated(string $property): void
    {
        if (! str_starts_with($property, 'paginators.')) {
            $this->resetPage(pageName: 'discover');
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->genre = null;
        $this->type = null;
        $this->sort = 'popular';
        $this->minimumRating = null;
        $this->yearFrom = null;
        $this->yearTo = null;
        $this->votesMin = null;
        $this->language = null;
        $this->country = null;
        $this->runtime = null;
        $this->awards = null;
        $this->resetPage(pageName: 'discover');
    }

    #[Computed]
    public function viewData(): array
    {
        $searchSuggestions = $this->getDiscoveryTitleSuggestions->handle($this->search);

        $discoveryQuery = $this->buildDiscoveryQuery->handle([
            'search' => $this->search,
            'genre' => $this->genre,
            'type' => $this->type,
            'sort' => $this->sort,
            'minimumRating' => $this->minimumRating,
            'yearFrom' => $this->yearFrom,
            'yearTo' => $this->yearTo,
            'votesMin' => $this->votesMin,
            'language' => $this->language,
            'country' => $this->country,
            'runtime' => $this->runtime,
            'awards' => $this->awards,
        ]);

        $titles = $discoveryQuery
            ->simplePaginate(12, pageName: 'discover')
            ->withQueryString();
        $titleResultsCount = count($titles->items());

        $filterOptions = $this->getDiscoveryFilterOptions->handle();
        $awardLabels = collect($filterOptions['awardOptions'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $countryLabels = collect($filterOptions['countries'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $languageLabels = collect($filterOptions['languages'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $runtimeLabels = collect($filterOptions['runtimeOptions'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $voteThresholdLabels = collect($filterOptions['voteThresholdOptions'])->mapWithKeys(
            fn (array $option): array => [$option['value'] => $option['label']],
        );
        $activeFilters = collect([
            ['icon' => 'magnifying-glass', 'label' => filled($this->search) ? 'Keyword: '.$this->search : null],
            ['icon' => $this->type ? TitleType::from($this->type)->icon() : 'film', 'label' => $this->type ? TitleType::from($this->type)->label() : null],
            ['icon' => 'tag', 'label' => $this->genre ? collect($filterOptions['genres'])->firstWhere('slug', $this->genre)?->name : null],
            ['icon' => 'trophy', 'label' => $this->awards ? $awardLabels->get($this->awards) : null],
            ['icon' => 'calendar-days', 'label' => $this->yearFrom ? 'From '.$this->yearFrom : null],
            ['icon' => 'calendar-days', 'label' => $this->yearTo ? 'To '.$this->yearTo : null],
            ['icon' => 'star', 'label' => $this->minimumRating ? $this->minimumRating.'+ rating' : null],
            ['icon' => 'users', 'label' => $this->votesMin ? $voteThresholdLabels->get($this->votesMin) : null],
            ['icon' => 'clock', 'label' => $this->runtime ? $runtimeLabels->get($this->runtime) : null],
            ['icon' => 'language', 'label' => $this->language ? $languageLabels->get($this->language) : null],
            ['icon' => 'globe-alt', 'label' => $this->country ? $countryLabels->get($this->country) : null],
        ])->filter(fn (array $filter): bool => filled($filter['label']))->values();
        $sortOptions = collect($filterOptions['sortOptions'])
            ->map(fn (array $option): array => [
                ...$option,
                'icon' => match ($option['value']) {
                    'popular' => 'fire',
                    'trending' => 'bolt',
                    'rating' => 'star',
                    'latest' => 'clock',
                    'year' => 'calendar-days',
                    default => 'bars-arrow-down',
                },
            ])
            ->all();

        return [
            'activeFilterCount' => collect([
                filled($this->search) ? $this->search : null,
                $this->genre,
                $this->type,
                $this->minimumRating,
                $this->yearFrom,
                $this->yearTo,
                $this->votesMin,
                $this->language,
                $this->country,
                $this->runtime,
                $this->awards,
            ])->filter(fn (mixed $value): bool => filled($value))->count(),
            'activeFilters' => $activeFilters,
            'awardOptions' => $filterOptions['awardOptions'],
            'countries' => $filterOptions['countries'],
            'titles' => $titles,
            'genres' => $filterOptions['genres'],
            'keywordActive' => filled($this->search),
            'languages' => $filterOptions['languages'],
            'coreFiltersActive' => filled($this->type) || filled($this->genre) || filled($this->awards),
            'loadingTargets' => 'search,genre,type,sort,minimumRating,yearFrom,yearTo,votesMin,language,country,runtime,awards',
            'titleTypes' => $filterOptions['titleTypes'],
            'minimumRatings' => $filterOptions['minimumRatings'],
            'orderingActive' => $this->sort !== 'popular',
            'originFiltersActive' => filled($this->language) || filled($this->country),
            'releaseFiltersActive' => filled($this->yearFrom) || filled($this->yearTo),
            'runtimeOptions' => $filterOptions['runtimeOptions'],
            'signalFiltersActive' => filled($this->minimumRating) || filled($this->votesMin) || filled($this->runtime),
            'sortLabel' => collect($filterOptions['sortOptions'])->firstWhere('value', $this->sort)['label'] ?? 'Popularity',
            'sortOptions' => $sortOptions,
            'searchSuggestions' => $searchSuggestions,
            'titleResultsCount' => $titleResultsCount,
            'voteThresholdOptions' => $filterOptions['voteThresholdOptions'],
            'years' => $filterOptions['years'],
        ];
    }

    public function render(): View
    {
        return view('livewire.search.discovery-filters');
    }

    public function paginationSimpleView(): string
    {
        return 'livewire.pagination.island-simple';
    }

    public function paginationIslandName(): string
    {
        return 'discover-results-page';
    }
}
