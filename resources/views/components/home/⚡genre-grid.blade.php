<?php

use App\Actions\Catalog\GetFeaturedGenresAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

new class extends Component
{
    public EloquentCollection $genres;

    public ?string $errorMessage = null;

    public function mount(GetFeaturedGenresAction $getFeaturedGenres): void
    {
        $this->genres = new EloquentCollection;

        try {
            $this->genres = $getFeaturedGenres->handle(12);
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Browse-by-genre links could not be loaded right now.';
        }
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg">Browse by Genre</x-ui.heading>
                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                    Loading popular genre routes from the catalog.
                </x-ui.text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach (range(1, 8) as $index)
                    <x-ui.card class="!max-w-none" wire:key="home-genre-placeholder-{{ $index }}">
                        <div class="space-y-3">
                            <x-ui.skeleton.text class="w-1/2" />
                            <x-ui.skeleton.text class="w-1/3" />
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
                    <x-ui.icon name="tag" class="size-5 text-neutral-500 dark:text-neutral-400" />
                    <span>Browse by Genre</span>
                </x-ui.heading>
                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                    Jump into the most populated genre routes without opening the full discovery surface.
                </x-ui.text>
            </div>

            <x-ui.link :href="route('public.discover')" variant="ghost">
                Open discovery
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
        @elseif ($genres->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="tag" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No genres are ready to browse yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Genre routes will appear once published titles are attached to them.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($genres as $genre)
                    <x-ui.card class="!max-w-none h-full" wire:key="home-genre-{{ $genre->id }}">
                        <div class="flex h-full items-start justify-between gap-4">
                            <div class="space-y-2">
                                <x-ui.heading level="h3" size="md">
                                    <a href="{{ route('public.genres.show', $genre) }}" class="hover:opacity-80">
                                        {{ $genre->name }}
                                    </a>
                                </x-ui.heading>
                                <x-ui.badge variant="outline" color="neutral" icon="film">
                                    {{ number_format((int) $genre->published_titles_count) }} titles
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
