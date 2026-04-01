@props([
    'person',
])

@php
    $headshot = $person->relationLoaded('mediaAssets')
        ? \App\Models\MediaAsset::preferredFrom(
            $person->mediaAssets,
            \App\Enums\MediaKind::Headshot,
            \App\Enums\MediaKind::Gallery,
            \App\Enums\MediaKind::Still,
        )
        : null;
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
        <div class="flex items-start gap-4">
            <x-ui.avatar
                as="a"
                :href="route('public.people.show', $person)"
                :src="$headshot?->url"
                :alt="$headshot?->alt_text ?: $person->name"
                :name="$person->name"
                color="auto"
                class="!h-28 !w-24 shrink-0 border border-black/5 shadow-sm dark:border-white/10"
            />

            <div class="flex min-w-0 flex-1 flex-col gap-3">
                <div class="space-y-2">
                    <x-ui.heading level="h3" size="md">
                        <a href="{{ route('public.people.show', $person) }}" class="hover:opacity-80">
                            {{ $person->name }}
                        </a>
                    </x-ui.heading>

                    <div class="flex flex-wrap gap-2">
                        @if ($person->known_for_department)
                            <x-ui.badge variant="outline" icon="briefcase">{{ $person->known_for_department }}</x-ui.badge>
                        @endif

                        @foreach ($professionLabels as $professionLabel)
                            <x-ui.badge variant="outline" color="neutral" icon="sparkles">{{ $professionLabel }}</x-ui.badge>
                        @endforeach

                        @if ($person->nationality)
                            <x-ui.badge variant="outline" color="slate" icon="globe-alt">{{ $person->nationality }}</x-ui.badge>
                        @endif
                    </div>
                </div>

                @if (filled($summary))
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        {{ str($summary)->limit(140) }}
                    </x-ui.text>
                @endif
            </div>
        </div>

        <div class="mt-auto flex items-center justify-between gap-3 text-sm text-neutral-500 dark:text-neutral-400">
            <span>{{ number_format($creditsCount) }} credits</span>
            <x-ui.link :href="route('public.people.show', $person)" variant="ghost" iconAfter="arrow-right">
                View profile
            </x-ui.link>
        </div>
    </div>
</x-ui.card>
