<?php

use App\Actions\Home\GetLatestTrailerTitlesAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

new class extends Component
{
    public EloquentCollection $titles;

    public ?string $errorMessage = null;

    public function mount(GetLatestTrailerTitlesAction $getLatestTrailerTitles): void
    {
        $this->titles = new EloquentCollection;

        try {
            $this->titles = $getLatestTrailerTitles->handle();
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Latest trailers could not be loaded right now.';
        }
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <x-ui.heading level="h2" size="lg">Latest Trailers</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                        Loading the freshest trailer and featurette uploads.
                    </x-ui.text>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                @foreach (range(1, 4) as $index)
                    <x-ui.card class="!max-w-none" wire:key="home-trailer-placeholder-{{ $index }}">
                        <div class="grid gap-4 md:grid-cols-[9rem_minmax(0,1fr)]">
                            <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                            <div class="space-y-3">
                                <x-ui.skeleton.text class="w-1/4" />
                                <x-ui.skeleton.text class="w-2/3" />
                                <x-ui.skeleton.text class="w-full" />
                                <x-ui.skeleton.text class="w-1/2" />
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    @endplaceholder

    <div class="space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                    <x-ui.icon name="play" class="size-5 text-neutral-500 dark:text-neutral-400" />
                    <span>Latest Trailers</span>
                </x-ui.heading>
                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                    Trailer, clip, and featurette uploads from the public catalog, ordered by publish time.
                </x-ui.text>
            </div>

            <x-ui.link :href="route('public.trailers.latest')" variant="ghost">
                See all trailers
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
        @elseif ($titles->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="play-circle" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No public trailers are available yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    This rail will populate as trailers, clips, and featurettes are attached to titles.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($titles as $title)
                    @php
                        $poster = \App\Models\MediaAsset::preferredFrom(
                            $title->mediaAssets,
                            \App\Enums\MediaKind::Poster,
                            \App\Enums\MediaKind::Backdrop,
                        );
                        $trailer = $title->titleVideos->first();
                    @endphp

                    <x-ui.card class="!max-w-none" wire:key="home-trailer-{{ $title->id }}">
                        <div class="grid gap-4 md:grid-cols-[9rem_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                @if ($poster)
                                    <img
                                        src="{{ $poster->url }}"
                                        alt="{{ $poster->alt_text ?: $title->name }}"
                                        class="aspect-[2/3] w-full object-cover"
                                    >
                                @else
                                    <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                        <x-ui.icon name="play" class="size-10" />
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge icon="play" color="amber">Trailer</x-ui.badge>
                                    <x-ui.badge variant="outline">{{ str($title->title_type->value)->headline() }}</x-ui.badge>
                                    @if ($title->release_year)
                                        <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}">
                                            <x-ui.badge variant="outline" color="slate">{{ $title->release_year }}</x-ui.badge>
                                        </a>
                                    @endif
                                </div>

                                <div>
                                    <x-ui.heading level="h3" size="md">
                                        <a href="{{ route('public.titles.show', $title) }}" class="hover:opacity-80">
                                            {{ $title->name }}
                                        </a>
                                    </x-ui.heading>
                                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ $trailer?->caption ?: $title->plot_outline ?: 'No public trailer copy is attached yet.' }}
                                    </x-ui.text>
                                </div>

                                <div class="flex flex-wrap gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                    @if ($trailer?->provider)
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-ui.icon name="video-camera" class="size-4 text-neutral-400 dark:text-neutral-500" />
                                            <span>{{ str($trailer->provider)->headline() }}</span>
                                        </span>
                                    @endif
                                    @if ($trailer?->published_at)
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-ui.icon name="calendar-days" class="size-4 text-neutral-400 dark:text-neutral-500" />
                                            <span>{{ $trailer->published_at->format('M j, Y') }}</span>
                                        </span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="outline" icon="film">
                                        View title
                                    </x-ui.button>
                                    @if (filled($trailer?->url))
                                        <x-ui.link :href="$trailer->url" open-in-new-tab variant="ghost">
                                            Open trailer
                                        </x-ui.link>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>
</div>
