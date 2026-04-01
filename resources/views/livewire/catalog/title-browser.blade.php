<div class="space-y-4">
    <div wire:loading.delay class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach (range(1, 6) as $index)
            <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="title-browser-skeleton-{{ $index }}">
                <div class="space-y-4">
                    <x-ui.skeleton class="aspect-[2/3] w-full rounded-box" />
                    <x-ui.skeleton.text class="w-1/3" />
                    <x-ui.skeleton.text class="w-3/4" />
                    <x-ui.skeleton.text class="w-5/6" />
                </div>
            </x-ui.card>
        @endforeach
    </div>

    <div wire:loading.remove class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($titles as $title)
                <div wire:key="title-browser-{{ $title->id }}">
                    <x-catalog.title-card :title="$title" :show-summary="$showSummary" />
                </div>
            @empty
                <div class="md:col-span-2 xl:col-span-3">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.heading level="h3">{{ $emptyHeading }}</x-ui.heading>
                        <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                            {{ $emptyText }}
                        </x-ui.text>
                    </x-ui.empty>
                </div>
            @endforelse
        </div>

        <div>
            {{ $titles->links() }}
        </div>
    </div>
</div>
