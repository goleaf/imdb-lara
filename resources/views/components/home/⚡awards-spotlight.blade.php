<?php

use App\Actions\Home\GetAwardsSpotlightTitlesAction;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

new class extends Component
{
    public EloquentCollection $titles;

    public ?Title $featuredTitle = null;

    public EloquentCollection $supportingTitles;

    public ?string $errorMessage = null;

    public function mount(GetAwardsSpotlightTitlesAction $getAwardsSpotlightTitles): void
    {
        $this->titles = new EloquentCollection;
        $this->supportingTitles = new EloquentCollection;

        try {
            $this->titles = $getAwardsSpotlightTitles->handle();
            $this->featuredTitle = $this->titles->first();
            $this->supportingTitles = $this->titles->slice(1)->values();
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Awards spotlight could not be loaded right now.';
        }
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <x-ui.heading level="h2" size="lg">Awards Spotlight</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                        Loading celebrated titles and standout award recognition.
                    </x-ui.text>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]">
                <x-ui.card class="!max-w-none h-full overflow-hidden">
                    <div class="grid gap-5 md:grid-cols-[11rem_minmax(0,1fr)]">
                        <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                        <div class="space-y-4">
                            <x-ui.skeleton.text class="w-1/4" />
                            <x-ui.skeleton.text class="w-2/3" />
                            <x-ui.skeleton.text class="w-full" />
                            <x-ui.skeleton.text class="w-4/5" />
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach (range(1, 3) as $statIndex)
                                    <x-ui.skeleton class="h-20 w-full rounded-box" wire:key="home-awards-stat-placeholder-{{ $statIndex }}" />
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach (range(1, 3) as $index)
                        <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="home-awards-placeholder-{{ $index }}">
                            <div class="flex items-center gap-4">
                                <x-ui.skeleton class="h-20 w-14 rounded-box" />
                                <div class="min-w-0 flex-1 space-y-3">
                                    <x-ui.skeleton.text class="w-1/2" />
                                    <x-ui.skeleton.text class="w-2/3" />
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        </div>
    @endplaceholder

    <div class="sb-home-section space-y-4 rounded-[1.6rem] p-4 sm:p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg" class="sb-home-section-heading inline-flex items-center gap-2">
                    <x-ui.icon name="trophy" class="size-5 text-[#d6b574]" />
                    <span>Awards Spotlight</span>
                </x-ui.heading>
                <x-ui.text class="sb-home-section-copy max-w-3xl text-sm">
                    Award winners, nominees, and standout recognition moments from the published catalog.
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
        @elseif ($titles->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="trophy" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No award-recognized titles are available yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Awards spotlight entries will appear once published titles accumulate nominations and wins.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]">
                @if ($featuredTitle)
                    <x-ui.card class="sb-home-side-card !max-w-none h-full overflow-hidden text-white">
                        <div class="grid h-full gap-5 md:grid-cols-[11rem_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                                @if ($featuredTitle->preferredPoster())
                                    <img
                                        src="{{ $featuredTitle->preferredPoster()->url }}"
                                        alt="{{ $featuredTitle->preferredPoster()->alt_text ?: $featuredTitle->name }}"
                                        class="aspect-[2/3] h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                        <x-ui.icon name="trophy" class="size-12" />
                                    </div>
                                @endif
                            </div>

                            <div class="flex h-full flex-col gap-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge color="amber" icon="sparkles">Awards Spotlight</x-ui.badge>

                                    @if (($featuredTitle->statistic?->awards_won_count ?? 0) > 0)
                                        <x-ui.badge variant="outline" icon="trophy">{{ number_format((int) $featuredTitle->statistic->awards_won_count) }} wins</x-ui.badge>
                                    @endif

                                    @if (($featuredTitle->statistic?->awards_nominated_count ?? 0) > 0)
                                        <x-ui.badge variant="outline" color="slate" icon="star">{{ number_format((int) $featuredTitle->statistic->awards_nominated_count) }} nominations</x-ui.badge>
                                    @endif
                                </div>

                                <div class="space-y-3">
                                    <x-ui.heading level="h3" size="xl">
                                        <a href="{{ route('public.titles.show', $featuredTitle) }}" class="hover:opacity-80">
                                            {{ $featuredTitle->name }}
                                        </a>
                                    </x-ui.heading>

                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.badge variant="outline" color="neutral" :icon="$featuredTitle->typeIcon()">{{ $featuredTitle->typeLabel() }}</x-ui.badge>
                                        @if ($featuredTitle->release_year)
                                            <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $featuredTitle->release_year }}</x-ui.badge>
                                        @endif
                                        @if ($featuredTitle->displayAverageRating())
                                            <x-ui.badge variant="outline" color="amber" icon="star">{{ number_format($featuredTitle->displayAverageRating(), 1) }}</x-ui.badge>
                                        @endif
                                    </div>

                                    @if (filled($featuredTitle->summaryText()))
                                        <x-ui.text class="max-w-2xl text-sm leading-7 text-neutral-600 dark:text-neutral-300">
                                            {{ str($featuredTitle->summaryText())->limit(240) }}
                                        </x-ui.text>
                                    @endif
                                </div>

                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-box border border-black/5 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
                                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="trophy" class="size-4" />
                                            <span>Lead Award</span>
                                        </div>
                                        <div class="mt-2 text-sm font-semibold text-neutral-900 dark:text-white">
                                            {{ $featuredTitle->leadAwardNomination()?->awardEvent?->award?->name ?: 'Catalog recognition' }}
                                        </div>
                                    </div>

                                    <div class="rounded-box border border-black/5 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
                                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="sparkles" class="size-4" />
                                            <span>Category</span>
                                        </div>
                                        <div class="mt-2 text-sm font-semibold text-neutral-900 dark:text-white">
                                            {{ $featuredTitle->leadAwardNomination()?->awardCategory?->name ?: 'Award shortlist' }}
                                        </div>
                                    </div>

                                    <div class="rounded-box border border-black/5 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
                                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="calendar-days" class="size-4" />
                                            <span>Event</span>
                                        </div>
                                        <div class="mt-2 text-sm font-semibold text-neutral-900 dark:text-white">
                                            {{ $featuredTitle->leadAwardNomination()?->awardEvent?->name ?: 'Published catalog' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-auto flex flex-wrap items-center justify-between gap-3">
                                    <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                        Follow the title page for credits, reviews, ratings, and full catalog context.
                                    </x-ui.text>

                                    <x-ui.button as="a" :href="route('public.titles.show', $featuredTitle)" variant="outline" icon="film">
                                        View title page
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach ($supportingTitles as $title)
                        <x-ui.card class="sb-poster-card !max-w-none h-full overflow-hidden rounded-[1.35rem]" wire:key="home-awards-{{ $title->id }}">
                            <div class="flex h-full items-start gap-4">
                                <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                                    @if ($title->preferredPoster())
                                        <img
                                            src="{{ $title->preferredPoster()->url }}"
                                            alt="{{ $title->preferredPoster()->alt_text ?: $title->name }}"
                                            class="h-24 w-16 object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-24 w-16 items-center justify-center text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="film" class="size-7" />
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1 space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if (($title->statistic?->awards_won_count ?? 0) > 0)
                                            <x-ui.badge color="amber" icon="trophy">{{ number_format((int) $title->statistic->awards_won_count) }} wins</x-ui.badge>
                                        @endif
                                        <x-ui.badge variant="outline" color="slate" icon="star">{{ number_format((int) ($title->statistic?->awards_nominated_count ?? 0)) }} nominations</x-ui.badge>
                                    </div>

                                    <div>
                                        <x-ui.heading level="h3" size="md">
                                            <a href="{{ route('public.titles.show', $title) }}" class="hover:opacity-80">
                                                {{ $title->name }}
                                            </a>
                                        </x-ui.heading>
                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $title->leadAwardNomination()?->awardCategory?->name ?: 'Award recognition in the Screenbase catalog.' }}
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                        @if ($title->release_year)
                                            <span>{{ $title->release_year }}</span>
                                        @endif
                                        @if ($title->displayAverageRating())
                                            <span>• {{ number_format($title->displayAverageRating(), 1) }}/10</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
