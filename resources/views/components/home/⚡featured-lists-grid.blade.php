<?php

use App\Actions\Home\GetFeaturedPublicListsAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

new class extends Component
{
    public EloquentCollection $lists;

    public ?string $errorMessage = null;

    public function mount(GetFeaturedPublicListsAction $getFeaturedPublicLists): void
    {
        $this->lists = new EloquentCollection;

        try {
            $this->lists = $getFeaturedPublicLists->handle();
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Featured public lists could not be loaded right now.';
        }
    }
};
?>

<div>
    @placeholder
        <div class="space-y-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg">Featured Public Lists</x-ui.heading>
                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                    Loading member-curated public lists.
                </x-ui.text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach (range(1, 3) as $index)
                    <x-ui.card class="!max-w-none h-full" wire:key="home-list-placeholder-{{ $index }}">
                        <div class="space-y-3">
                            <x-ui.skeleton.text class="w-2/3" />
                            <x-ui.skeleton.text class="w-full" />
                            <x-ui.skeleton.text class="w-3/4" />
                            <div class="grid grid-cols-3 gap-2 pt-2">
                                <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                                <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                                <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                            </div>
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
                    <x-ui.icon name="queue-list" class="size-5 text-[#d6b574]" />
                    <span>Featured Public Lists</span>
                </x-ui.heading>
                <x-ui.text class="sb-home-section-copy max-w-3xl text-sm">
                    Public member curation with title previews, curator profiles, and visible list counts.
                </x-ui.text>
            </div>

            <x-ui.link :href="route('public.lists.index')" variant="ghost">
                See all lists
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
        @elseif ($lists->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="queue-list" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No public lists are featured right now.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Public lists will appear here once members publish and populate them with titles.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($lists as $list)
                    <x-ui.card class="!max-w-none h-full" wire:key="home-list-{{ $list->id }}">
                        <div class="flex h-full flex-col gap-4">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('public.users.show', $list->user) }}">
                                        <x-ui.badge variant="outline" color="neutral" icon="user">{{ $list->user->name }}</x-ui.badge>
                                    </a>
                                    <x-ui.badge variant="outline" color="slate" icon="queue-list">
                                        {{ number_format((int) $list->published_items_count) }} titles
                                    </x-ui.badge>
                                </div>

                                <x-ui.heading level="h3" size="md">
                                    <a href="{{ route('public.lists.show', [$list->user, $list]) }}" class="hover:opacity-80">
                                        {{ $list->name }}
                                    </a>
                                </x-ui.heading>
                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $list->description ?: 'A public Screenbase list curated by the community.' }}
                                </x-ui.text>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                @forelse ($list->previewItems() as $item)
                                    <a
                                        href="{{ route('public.titles.show', $item->title) }}"
                                        class="group overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800"
                                    >
                                        @if ($item->title->preferredPoster())
                                            <img
                                                src="{{ $item->title->preferredPoster()->url }}"
                                                alt="{{ $item->title->preferredPoster()->alt_text ?: $item->title->name }}"
                                                class="aspect-[2/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                                <x-ui.icon name="film" class="size-8" />
                                            </div>
                                        @endif
                                    </a>
                                @empty
                                    @foreach (range(1, 3) as $index)
                                        <div class="flex aspect-[2/3] items-center justify-center rounded-box border border-dashed border-black/10 bg-neutral-50 text-neutral-400 dark:border-white/10 dark:bg-neutral-800/70 dark:text-neutral-500">
                                            <x-ui.icon name="film" class="size-8" />
                                        </div>
                                    @endforeach
                                @endforelse
                            </div>

                            @if ($list->previewItems()->isNotEmpty())
                                <div class="flex flex-wrap gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                    @foreach ($list->previewItems() as $item)
                                        <span>{{ $item->title->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-auto flex items-center justify-between gap-3">
                                <x-ui.badge variant="outline" color="neutral" icon="at-symbol">{{ '@'.$list->user->username }}</x-ui.badge>
                                <x-ui.link :href="route('public.lists.show', [$list->user, $list])" variant="ghost">
                                    View list
                                </x-ui.link>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>
</div>
