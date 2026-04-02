<?php

use App\Actions\Home\GetHomepageTitleRailAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

new class extends Component
{
    public string $rail = 'trending';

    public string $heading = 'Trending Now';

    public string $description = 'Audience momentum, watchlist activity, and review volume from the public catalog.';

    public string $linkLabel = 'See all';

    public string $linkHref = '';

    public string $emptyHeading = 'No titles are available yet.';

    public string $emptyText = 'This section will populate as the catalog grows.';

    public string $railIcon = 'fire';

    public bool $isPrimaryRail = false;

    public string $gridClasses = 'grid gap-4 md:grid-cols-2 xl:grid-cols-3';

    public string $sectionClasses = 'sb-home-section sb-home-section--secondary space-y-5 rounded-[1.6rem] p-4 sm:p-5';

    public EloquentCollection $titles;

    public ?string $errorMessage = null;

    public function mount(
        GetHomepageTitleRailAction $getHomepageTitleRail,
        string $rail = 'trending',
    ): void {
        $this->rail = $rail;
        $this->titles = new EloquentCollection;

        [
            'heading' => $this->heading,
            'description' => $this->description,
            'linkLabel' => $this->linkLabel,
            'linkHref' => $this->linkHref,
            'emptyHeading' => $this->emptyHeading,
            'emptyText' => $this->emptyText,
        ] = $this->meta();

        [
            'railIcon' => $this->railIcon,
            'isPrimaryRail' => $this->isPrimaryRail,
            'gridClasses' => $this->gridClasses,
            'sectionClasses' => $this->sectionClasses,
        ] = $this->presentation();

        try {
            $this->titles = $getHomepageTitleRail->handle($this->rail);
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = sprintf('The %s section could not be loaded right now.', str($this->heading)->lower());
        }
    }

    /**
     * @return array{
     *     heading: string,
     *     description: string,
     *     linkLabel: string,
     *     linkHref: string,
     *     emptyHeading: string,
     *     emptyText: string
     * }
     */
    private function meta(): array
    {
        return match ($this->rail) {
            'top-rated-movies' => [
                'heading' => 'Top Rated Movies',
                'description' => 'Feature films ranked by audience score and vote depth.',
                'linkLabel' => 'See movie rankings',
                'linkHref' => route('public.rankings.movies'),
                'emptyHeading' => 'No rated movies are available yet.',
                'emptyText' => 'As audience ratings accumulate, the strongest films will surface here.',
            ],
            'top-rated-series' => [
                'heading' => 'Top Rated TV Shows',
                'description' => 'Series and mini-series ordered by audience score and viewing momentum.',
                'linkLabel' => 'See TV rankings',
                'linkHref' => route('public.rankings.series'),
                'emptyHeading' => 'No rated TV shows are available yet.',
                'emptyText' => 'This rail will fill as series ratings and reviews build up.',
            ],
            'coming-soon' => [
                'heading' => 'Coming Soon',
                'description' => 'Future-dated releases already staged in the public catalog.',
                'linkLabel' => 'Browse all titles',
                'linkHref' => route('public.titles.index'),
                'emptyHeading' => 'No upcoming releases are scheduled yet.',
                'emptyText' => 'When future-dated titles are published, they will appear here first.',
            ],
            'recently-added' => [
                'heading' => 'Recently Added Titles',
                'description' => 'The newest catalog records added to Screenbase, regardless of release year.',
                'linkLabel' => 'Browse the catalog',
                'linkHref' => route('public.titles.index'),
                'emptyHeading' => 'No recently added titles are available yet.',
                'emptyText' => 'Newly published catalog entries will land here as soon as they are added.',
            ],
            default => [
                'heading' => 'Trending Now',
                'description' => 'Audience momentum driven by watchlists, reviews, and popularity rank.',
                'linkLabel' => 'See trending',
                'linkHref' => route('public.trending'),
                'emptyHeading' => 'No trending titles are available yet.',
                'emptyText' => 'As community activity grows, this section will highlight the hottest titles.',
            ],
        };
    }

    /**
     * @return array{railIcon: string, isPrimaryRail: bool, gridClasses: string, sectionClasses: string}
     */
    private function presentation(): array
    {
        $isPrimaryRail = in_array($this->rail, ['trending', 'top-rated-movies'], true);

        return [
            'railIcon' => match ($this->rail) {
                'top-rated-movies' => 'star',
                'top-rated-series' => 'tv',
                'coming-soon' => 'calendar-days',
                'recently-added' => 'plus-circle',
                default => 'fire',
            },
            'isPrimaryRail' => $isPrimaryRail,
            'gridClasses' => $isPrimaryRail
                ? 'grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4'
                : 'grid gap-4 md:grid-cols-2 xl:grid-cols-3',
            'sectionClasses' => $isPrimaryRail
                ? 'sb-home-section sb-home-section--primary space-y-5 rounded-[1.75rem] p-4 sm:p-5'
                : 'sb-home-section sb-home-section--secondary space-y-5 rounded-[1.6rem] p-4 sm:p-5',
        ];
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <x-ui.heading level="h2" size="lg">{{ $heading ?? 'Loading titles' }}</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                        {{ $description ?? 'Fetching the latest title rail.' }}
                    </x-ui.text>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach (range(1, 6) as $index)
                    <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="home-title-rail-placeholder-{{ $rail ?? 'default' }}-{{ $index }}">
                        <div class="space-y-4">
                            <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                            <x-ui.skeleton.text class="w-1/3" />
                            <x-ui.skeleton.text class="w-4/5" />
                            <x-ui.skeleton.text class="w-2/3" />
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    @endplaceholder

    <div class="{{ $sectionClasses }}" @if ($isPrimaryRail) data-slot="home-primary-rail" @endif>
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg" class="sb-home-section-heading inline-flex items-center gap-2">
                    <x-ui.icon :name="$railIcon" class="size-5 text-[#d6b574]" />
                    <span>{{ $heading }}</span>
                </x-ui.heading>
                <x-ui.text class="sb-home-section-copy max-w-3xl text-sm">
                    {{ $description }}
                </x-ui.text>
            </div>

            @if (filled($linkHref))
                <x-ui.link :href="$linkHref" variant="ghost">
                    {{ $linkLabel }}
                </x-ui.link>
            @endif
        </div>

        @if ($errorMessage)
            <x-ui.card class="!max-w-none border-dashed border-red-200/70 dark:border-red-400/40">
                <div class="space-y-2">
                    <x-ui.heading level="h3">Section unavailable</x-ui.heading>
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        {{ $errorMessage }}
                    </x-ui.text>
                </div>
            </x-ui.card>
        @elseif ($titles->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon :name="$railIcon" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">{{ $emptyHeading }}</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    {{ $emptyText }}
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="{{ $gridClasses }}">
                @foreach ($titles as $title)
                    <div class="{{ $isPrimaryRail && $loop->first ? 'md:col-span-2 xl:col-span-2' : '' }}" wire:key="home-title-rail-{{ $rail }}-{{ $title->id }}">
                        <x-catalog.title-card :title="$title" :show-summary="$isPrimaryRail && $loop->first" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
