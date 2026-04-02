<?php

use App\Actions\Home\GetBrowseYearsAction;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    public Collection $years;

    public ?string $errorMessage = null;

    public function mount(GetBrowseYearsAction $getBrowseYears): void
    {
        $this->years = collect();

        try {
            $this->years = $getBrowseYears->handle();
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Browse-by-year links could not be loaded right now.';
        }
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg">Browse by Year</x-ui.heading>
                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                    Loading recent release-year routes from the title catalog.
                </x-ui.text>
            </div>

            <div class="grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                @foreach (range(1, 6) as $index)
                    <x-ui.card class="!max-w-none" wire:key="home-year-placeholder-{{ $index }}">
                        <div class="space-y-3">
                            <x-ui.skeleton.text class="w-1/2" />
                            <x-ui.skeleton.text class="w-1/3" />
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
                    <x-ui.icon name="calendar-days" class="size-5 text-[#d6b574]" />
                    <span>Browse by Year</span>
                </x-ui.heading>
                <x-ui.text class="sb-home-section-copy max-w-3xl text-sm">
                    Fast entry points into the busiest release years currently represented on Screenbase.
                </x-ui.text>
            </div>

            <x-ui.link :href="route('public.titles.index')" variant="ghost">
                Browse all titles
            </x-ui.link>
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
        @elseif ($years->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="calendar-days" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No release years are available yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Year shortcuts will populate as published titles enter the catalog.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                @foreach ($years as $year)
                    <x-ui.card class="!max-w-none h-full" wire:key="home-year-{{ $year['year'] }}">
                        <div class="flex h-full items-start justify-between gap-4">
                            <div class="space-y-2">
                                <x-ui.heading level="h3" size="md">
                                    <a href="{{ route('public.years.show', ['year' => $year['year']]) }}" class="hover:opacity-80">
                                        {{ $year['year'] }}
                                    </a>
                                </x-ui.heading>
                                <x-ui.badge variant="outline" color="neutral" icon="film">
                                    {{ number_format($year['titles_count']) }} titles
                                </x-ui.badge>
                            </div>

                            <x-ui.icon name="arrow-right" class="size-5 text-neutral-400" />
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>
</div>
