@props([
    'interestCategory',
    'showImage' => false,
])

@php($directoryImage = $showImage ? $interestCategory->preferredDirectoryImage() : null)

<x-ui.card
    data-slot="interest-category-card"
    class="sb-person-card !max-w-none h-full overflow-hidden rounded-[1.4rem] {{ $showImage ? '!p-0' : 'p-4' }}"
>
    <div class="flex h-full flex-col gap-4">
        @if ($showImage)
            <a
                href="{{ route('public.interest-categories.show', $interestCategory) }}"
                class="group block overflow-hidden border-b border-white/10"
            >
                @if ($directoryImage)
                    <img
                        src="{{ $directoryImage->url }}"
                        alt="{{ $directoryImage->accessibleAltText($interestCategory->name) }}"
                        class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                        loading="lazy"
                    >
                @else
                    <div class="flex aspect-[16/9] items-center justify-center bg-white/[0.04] text-[#8f877a]">
                        <x-ui.icon name="photo" class="size-10" />
                    </div>
                @endif
            </a>
        @endif

        <div class="space-y-3 {{ $showImage ? 'px-4 pt-4' : '' }}">
            <div class="flex flex-wrap gap-2">
                <x-ui.badge variant="outline" icon="squares-2x2">
                    {{ $interestCategory->interestCountBadgeLabel() }}
                </x-ui.badge>

                @if ($interestCategory->titleLinkedInterestCount() > 0)
                    <x-ui.badge variant="outline" color="neutral" icon="film">
                        {{ $interestCategory->titleLinkedInterestCountBadgeLabel() }}
                    </x-ui.badge>
                @endif

                @if ($interestCategory->subgenreInterestCount() > 0)
                    <x-ui.badge variant="outline" color="slate" icon="tag">
                        {{ $interestCategory->subgenreInterestCountBadgeLabel() }}
                    </x-ui.badge>
                @endif
            </div>

            <div class="space-y-2">
                <x-ui.heading level="h3" size="md" class="font-[family-name:var(--font-editorial)] text-[1.22rem] font-semibold tracking-[-0.03em] text-[#f4eee5]">
                    <a href="{{ route('public.interest-categories.show', $interestCategory) }}" class="hover:opacity-80">
                        {{ $interestCategory->name }}
                    </a>
                </x-ui.heading>

                <x-ui.text class="text-sm text-[#aca293] dark:text-[#aca293]">
                    {{ $interestCategory->description }}
                </x-ui.text>
            </div>
        </div>

        @if ($slot->isNotEmpty())
            <div class="flex flex-wrap gap-2 {{ $showImage ? 'px-4' : '' }}">
                {{ $slot }}
            </div>
        @endif

        <div class="mt-auto flex items-center justify-end gap-3 px-4 pb-4 text-sm text-[#988f82] dark:text-[#988f82] {{ $showImage ? '' : 'px-0 pb-0' }}">
            <x-ui.button.light-outline
                :href="route('public.interest-categories.show', $interestCategory)"
                size="sm"
                iconAfter="arrow-right"
            >
                Open category
            </x-ui.button.light-outline>
        </div>
    </div>
</x-ui.card>
