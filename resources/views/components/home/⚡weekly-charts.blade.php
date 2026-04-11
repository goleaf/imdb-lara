<?php

use App\Actions\Home\GetHomepageTitleRailAction;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

new class extends Component
{
    public EloquentCollection $trendingTitles;

    public EloquentCollection $topMovieTitles;

    public EloquentCollection $topSeriesTitles;

    public ?string $errorMessage = null;

    public function mount(GetHomepageTitleRailAction $getHomepageTitleRail): void
    {
        $this->trendingTitles = new EloquentCollection;
        $this->topMovieTitles = new EloquentCollection;
        $this->topSeriesTitles = new EloquentCollection;

        try {
            $this->trendingTitles = $getHomepageTitleRail->handle('trending', 3);
            $this->topMovieTitles = $getHomepageTitleRail->handle('top-rated-movies', 3);
            $this->topSeriesTitles = $getHomepageTitleRail->handle('top-rated-series', 3);
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Weekly charts could not be loaded right now.';
        }
    }

    /**
     * @return list<array{heading: string, description: string, href: string, metricLabel: string, items: list<array{title: Title, metricValue: string}>}>
     */
    public function chartGroups(): array
    {
        return [
            [
                'heading' => 'Trending',
                'description' => 'Fast-moving titles with the strongest current momentum.',
                'href' => route('public.trending'),
                'metricLabel' => 'Watchlists',
                'items' => $this->chartItems($this->trendingTitles, fn (Title $title): string => number_format((int) ($title->statistic?->watchlist_count ?? 0))),
            ],
            [
                'heading' => 'Top Movies',
                'description' => 'The best-performing films in the public rankings.',
                'href' => route('public.rankings.movies'),
                'metricLabel' => 'Rating',
                'items' => $this->chartItems($this->topMovieTitles, fn (Title $title): string => $title->displayAverageRating() ? number_format($title->displayAverageRating(), 1).'/10' : 'Pending'),
            ],
            [
                'heading' => 'Top TV Shows',
                'description' => 'The strongest series and mini-series this week.',
                'href' => route('public.rankings.series'),
                'metricLabel' => 'Rating',
                'items' => $this->chartItems($this->topSeriesTitles, fn (Title $title): string => $title->displayAverageRating() ? number_format($title->displayAverageRating(), 1).'/10' : 'Pending'),
            ],
        ];
    }

    public function hasCharts(): bool
    {
        foreach ($this->chartGroups() as $group) {
            if ($group['items'] !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  EloquentCollection<int, Title>  $titles
     * @param  \Closure(Title): string  $metricResolver
     * @return list<array{title: Title, metricValue: string}>
     */
    private function chartItems(EloquentCollection $titles, \Closure $metricResolver): array
    {
        return $titles
            ->map(fn (Title $title): array => [
                'title' => $title,
                'metricValue' => $metricResolver($title),
            ])
            ->values()
            ->all();
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg">Weekly Charts</x-ui.heading>
                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                    Loading the current chart snapshot across public ranking routes.
                </x-ui.text>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                @foreach (range(1, 3) as $column)
                    <x-ui.card class="!max-w-none" wire:key="home-charts-placeholder-{{ $column }}">
                        <div class="space-y-4">
                            <x-ui.skeleton.text class="w-1/3" />
                            @foreach (range(1, 3) as $row)
                                <div class="flex items-center gap-3" wire:key="home-charts-placeholder-{{ $column }}-{{ $row }}">
                                    <x-ui.skeleton class="h-16 w-12 rounded-box" />
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <x-ui.skeleton.text class="w-2/3" />
                                        <x-ui.skeleton.text class="w-1/2" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    @endplaceholder

    <div class="sb-home-section space-y-4 rounded-[1.6rem] p-4 sm:p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg" class="sb-home-section-heading inline-flex items-center gap-2">
                    <x-ui.icon name="chart-bar" class="size-5 text-[#d6b574]" />
                    <span>Weekly Charts</span>
                </x-ui.heading>
                <x-ui.text class="sb-home-section-copy max-w-3xl text-sm">
                    A quick read of the strongest chart routes across trending, top movies, and top TV shows.
                </x-ui.text>
            </div>
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
        @elseif (! $this->hasCharts())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="chart-bar" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No weekly charts are available yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Chart previews will appear as the public catalog builds enough ratings, reviews, and watchlist activity.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 xl:grid-cols-3">
                @foreach ($this->chartGroups() as $group)
                    <x-ui.card class="sb-poster-card !max-w-none h-full rounded-[1.35rem]" wire:key="home-chart-group-{{ str($group['heading'])->slug() }}">
                        <div class="flex h-full flex-col gap-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <x-ui.heading level="h3" size="md" class="text-[#f4eee5]">
                                        {{ $group['heading'] }}
                                    </x-ui.heading>
                                    <x-ui.text class="text-sm text-[#a99f92]">
                                        {{ $group['description'] }}
                                    </x-ui.text>
                                </div>

                                <x-ui.link.light :href="$group['href']">
                                    Full chart
                                </x-ui.link.light>
                            </div>

                            <div class="space-y-3">
                                @foreach ($group['items'] as $index => $item)
                                    <a
                                        href="{{ route('public.titles.show', $item['title']) }}"
                                        class="group flex items-center gap-3 rounded-[1.15rem] border border-white/6 bg-white/[0.025] p-2.5 transition hover:bg-white/[0.05]"
                                    >
                                        <div class="w-7 text-center text-xl font-semibold text-[#d6b574]">
                                            {{ $index + 1 }}
                                        </div>

                                        <div class="overflow-hidden rounded-[0.9rem] border border-white/6 bg-black/20">
                                            @if ($item['title']->preferredPoster())
                                                <img
                                                    src="{{ $item['title']->preferredPoster()->url }}"
                                                    alt="{{ $item['title']->preferredPoster()->alt_text ?: $item['title']->name }}"
                                                    class="h-16 w-12 object-cover"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex h-16 w-12 items-center justify-center text-[#8f877a]">
                                                    <x-ui.icon name="film" class="size-5" />
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm font-semibold text-[#f4eee5]">
                                                {{ $item['title']->name }}
                                            </div>
                                            <div class="mt-1 text-sm text-[#a99f92]">
                                                {{ $group['metricLabel'] }}: {{ $item['metricValue'] }}
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>
</div>
