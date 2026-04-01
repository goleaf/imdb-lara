@props([
    'person',
])

@php
    $headshot = $person->relationLoaded('mediaAssets') ? $person->mediaAssets->first() : null;
    $creditsCount = isset($person->credits_count)
        ? (int) $person->credits_count
        : ($person->relationLoaded('credits') ? $person->credits->count() : 0);
    $professionLabels = $person->relationLoaded('professions')
        ? $person->professions->pluck('profession')->filter()->unique()->take(2)
        : collect();
    $summary = $person->short_biography ?: $person->biography;
@endphp

<x-ui.card class="!max-w-none h-full overflow-hidden">
    <div class="flex h-full flex-col gap-4">
        <a
            href="{{ route('public.people.show', $person) }}"
            class="group block overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800"
        >
            @if ($headshot)
                <img
                    src="{{ $headshot->url }}"
                    alt="{{ $headshot->alt_text ?: $person->name }}"
                    class="aspect-[3/4] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                    loading="lazy"
                >
            @else
                <div class="flex aspect-[3/4] items-center justify-center bg-neutral-200 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                    <x-ui.icon name="user" class="size-10" />
                </div>
            @endif
        </a>

        <div class="flex flex-1 flex-col gap-3">
            <div class="space-y-2">
                <x-ui.heading level="h3" size="md">
                    <a href="{{ route('public.people.show', $person) }}" class="hover:opacity-80">
                        {{ $person->name }}
                    </a>
                </x-ui.heading>

                <div class="flex flex-wrap gap-2">
                    @if ($person->known_for_department)
                        <x-ui.badge variant="outline">{{ $person->known_for_department }}</x-ui.badge>
                    @endif

                    @foreach ($professionLabels as $professionLabel)
                        <x-ui.badge variant="outline" color="neutral">{{ $professionLabel }}</x-ui.badge>
                    @endforeach

                    @if ($person->nationality)
                        <x-ui.badge variant="outline" color="slate">{{ $person->nationality }}</x-ui.badge>
                    @endif
                </div>
            </div>

            @if (filled($summary))
                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                    {{ str($summary)->limit(140) }}
                </x-ui.text>
            @endif

            <div class="mt-auto flex items-center justify-between gap-3 text-sm text-neutral-500 dark:text-neutral-400">
                <span>{{ number_format($creditsCount) }} credits</span>
                <x-ui.link :href="route('public.people.show', $person)" variant="ghost">
                    View profile
                </x-ui.link>
            </div>
        </div>
    </div>
</x-ui.card>
