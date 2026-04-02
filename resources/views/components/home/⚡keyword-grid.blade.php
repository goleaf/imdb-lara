<?php

use App\Actions\Home\GetBrowseKeywordsAction;
use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component
{
    public Collection $keywords;

    public ?string $errorMessage = null;

    public function mount(GetBrowseKeywordsAction $getBrowseKeywords): void
    {
        $this->keywords = collect();

        try {
            $this->keywords = $getBrowseKeywords->handle();
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Keyword discovery could not be loaded right now.';
        }
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg">Browse by Keyword</x-ui.heading>
                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                    Loading search-led discovery shortcuts from the catalog.
                </x-ui.text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach (range(1, 8) as $index)
                    <x-ui.card class="!max-w-none" wire:key="home-keyword-placeholder-{{ $index }}">
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
                    <x-ui.icon name="magnifying-glass" class="size-5 text-[#d6b574]" />
                    <span>Browse by Keyword</span>
                </x-ui.heading>
                <x-ui.text class="sb-home-section-copy max-w-3xl text-sm">
                    Search-led discovery prompts drawn from recurring themes, missions, and story hooks in the public catalog.
                </x-ui.text>
            </div>

            <x-ui.link :href="route('public.search')" variant="ghost">
                Open search
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
        @elseif ($keywords->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="magnifying-glass" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No browseable keywords are ready yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Keyword discovery will appear once published titles expose consistent search themes.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($keywords as $keyword)
                    <a
                        href="{{ route('public.search', ['q' => $keyword['keyword']]) }}"
                        class="group block rounded-[1.35rem] border border-white/6 bg-[linear-gradient(180deg,rgba(18,17,15,0.96),rgba(11,10,9,0.98))] p-4 shadow-[0_16px_40px_rgba(0,0,0,0.22)] transition duration-200 hover:border-[#d6b574]/18 hover:bg-[linear-gradient(180deg,rgba(22,20,17,0.98),rgba(13,12,10,1))]"
                        wire:key="home-keyword-{{ \Illuminate\Support\Str::slug($keyword['keyword']) }}"
                    >
                        <div class="flex h-full items-start justify-between gap-4">
                            <div class="space-y-2">
                                <div class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-[#8f877a]">
                                    Keyword Discovery
                                </div>
                                <div class="text-lg font-semibold text-[#f4eee5]">
                                    {{ $keyword['keyword'] }}
                                </div>
                                <div class="text-sm text-[#a99f92]">
                                    {{ trans_choice('{1} 1 title|[2,*] :count titles', $keyword['titles_count'], ['count' => number_format($keyword['titles_count'])]) }}
                                </div>
                            </div>

                            <x-ui.icon name="arrow-right" class="size-5 text-[#8f877a] transition group-hover:text-[#d6b574]" />
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
