@props([
    'person',
])

<x-ui.card data-slot="person-card" class="sb-person-card !max-w-none h-full overflow-hidden rounded-[1.4rem] p-3">
    <div class="flex h-full flex-col gap-4">
        <div class="flex items-start gap-4">
            <x-ui.avatar
                as="a"
                :href="route('public.people.show', $person)"
                :src="$person->preferredHeadshot()?->url"
                :alt="$person->preferredHeadshot()?->alt_text ?: $person->name"
                :name="$person->name"
                color="auto"
                class="!h-28 !w-24 shrink-0 border border-black/5 shadow-sm dark:border-white/10"
            />

            <div class="flex min-w-0 flex-1 flex-col gap-3">
                <div class="space-y-2">
                    <x-ui.heading level="h3" size="md" class="font-[family-name:var(--font-editorial)] text-[1.22rem] font-semibold tracking-[-0.03em] text-[#f4eee5]">
                        <a href="{{ route('public.people.show', $person) }}" class="hover:opacity-80">
                            {{ $person->name }}
                        </a>
                    </x-ui.heading>

                    <div class="flex flex-wrap gap-2">
                        @if ($person->known_for_department)
                            <x-ui.badge variant="outline" icon="briefcase">{{ $person->known_for_department }}</x-ui.badge>
                        @endif

                        @foreach ($person->professionLabels() as $professionLabel)
                            <x-ui.badge variant="outline" color="neutral" icon="sparkles">{{ $professionLabel }}</x-ui.badge>
                        @endforeach

                        @if ($person->nationality)
                            <x-ui.badge variant="outline" color="slate" icon="globe-alt">{{ $person->nationality }}</x-ui.badge>
                        @endif
                    </div>
                </div>

                @if (filled($person->summaryText()))
                    <x-ui.text class="text-sm text-[#aca293] dark:text-[#aca293]">
                        {{ str($person->summaryText())->limit(140) }}
                    </x-ui.text>
                @endif

                @if ($person->popularityRankBadgeLabel() || $person->awardNominationsBadgeLabel())
                    <div data-slot="person-card-metrics" class="flex flex-wrap gap-2">
                        @if ($person->popularityRankBadgeLabel())
                            <x-ui.badge data-slot="person-card-popularity-rank" variant="outline" color="amber" icon="fire">
                                {{ $person->popularityRankBadgeLabel() }}
                            </x-ui.badge>
                        @endif

                        @if ($person->awardNominationsBadgeLabel())
                            <x-ui.badge data-slot="person-card-awards" variant="outline" color="slate" icon="trophy">
                                {{ $person->awardNominationsBadgeLabel() }}
                            </x-ui.badge>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-auto flex items-center justify-between gap-3 text-sm text-[#988f82] dark:text-[#988f82]">
            <span>{{ $person->creditsBadgeLabel() }}</span>
            <x-ui.link :href="route('public.people.show', $person)" variant="ghost" iconAfter="arrow-right">
                View profile
            </x-ui.link>
        </div>
    </div>
</x-ui.card>
