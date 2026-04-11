@extends('layouts.public')

@section('title', collect([$awardNomination->awardCategory?->name, $awardNomination->awardEvent?->name, $awardNomination->award_year])->filter()->implode(' · ') ?: 'Award nomination')
@section('meta_description', 'Browse nominees, linked titles, and same-category archive entries for '.($headlineLabel ?: 'this award nomination').'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.awards.index')">Awards</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $awardNomination->awardCategory?->name ?? 'Award nomination' }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card data-slot="award-nomination-detail-hero" class="sb-page-hero !max-w-none p-6 sm:p-7">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.04fr)_minmax(18rem,0.96fr)] xl:items-start">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="sb-page-kicker">Awards archive</div>
                        <x-ui.badge variant="outline" color="{{ $awardNomination->is_winner ? 'amber' : 'neutral' }}" icon="{{ $awardNomination->is_winner ? 'trophy' : 'sparkles' }}">
                            {{ $awardNomination->is_winner ? 'Winner' : 'Nominee' }}
                        </x-ui.badge>
                        @if ($awardNomination->winner_rank)
                            <x-catalog.winner-rank-badge :rank="$awardNomination->winner_rank" />
                        @endif
                    </div>

                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">
                            {{ $awardNomination->awardCategory?->name ?? 'Award nomination' }}
                        </x-ui.heading>

                        <x-ui.text class="sb-page-copy max-w-4xl text-base">
                            {{ $awardNomination->awardEvent?->name ?? 'Awards archive' }}
                            @if ($awardNomination->award_year)
                                · {{ $awardNomination->award_year }}
                            @endif
                            @if (filled($headlineLabel))
                                · {{ $headlineLabel }}
                            @endif
                        </x-ui.text>

                        @if (filled($awardNomination->text))
                            <x-ui.text class="max-w-4xl text-sm text-neutral-600 dark:text-neutral-300">
                                {{ $awardNomination->text }}
                            </x-ui.text>
                        @endif
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach ($summaryItems as $summaryItem)
                        <div class="rounded-[1.2rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                            <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">{{ $summaryItem['label'] }}</div>
                            <div class="mt-2 text-lg font-semibold text-neutral-900 dark:text-neutral-100">{{ $summaryItem['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        @if ($linkedNominees->isNotEmpty())
            <x-ui.card data-slot="award-nomination-linked-nominees" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Linked nominees</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            People attached to this nomination record.
                        </x-ui.text>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($linkedNominees as $linkedNominee)
                            <a href="{{ route('public.people.show', $linkedNominee) }}" class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar
                                        :src="$linkedNominee->preferredHeadshot()?->url"
                                        :alt="$linkedNominee->preferredHeadshot()?->alt_text ?: $linkedNominee->name"
                                        :name="$linkedNominee->name"
                                        color="auto"
                                        class="!h-14 !w-14 shrink-0 border border-black/5 dark:border-white/10"
                                    />
                                    <div class="min-w-0">
                                        <div class="truncate font-medium text-neutral-900 dark:text-neutral-100">{{ $linkedNominee->name }}</div>
                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">View profile</div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>
        @endif

        @if ($linkedTitles->isNotEmpty())
            <x-ui.card data-slot="award-nomination-linked-titles" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Linked titles</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Published titles attached to this nomination record.
                        </x-ui.text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($linkedTitles as $linkedTitle)
                            <x-catalog.title-card :title="$linkedTitle" />
                        @endforeach
                    </div>
                </div>
            </x-ui.card>
        @endif

        <x-ui.card data-slot="award-nomination-cohort" class="sb-detail-section !max-w-none">
            <div class="space-y-4">
                <div>
                    <x-ui.heading level="h2" size="lg">Same category archive</x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Every nomination published for this event, category, and year, including other movie entries in the same cohort.
                    </x-ui.text>
                </div>

                <div class="space-y-3">
                    @foreach ($cohortEntries as $cohortEntry)
                        <a
                            href="{{ $cohortEntry['href'] }}"
                            class="block rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05] {{ $cohortEntry['isCurrent'] ? 'ring-1 ring-amber-300/50 dark:ring-amber-500/30' : '' }}"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium {{ $cohortEntry['isWinner'] ? 'bg-amber-100 text-amber-900 dark:bg-amber-500/15 dark:text-amber-100' : 'bg-neutral-100 text-neutral-700 dark:bg-white/[0.06] dark:text-neutral-200' }}">
                                            @if ($cohortEntry['isWinner'])
                                                <x-ui.icon name="trophy" class="size-3" />
                                            @endif
                                            {{ $cohortEntry['statusLabel'] }}
                                        </span>

                                        @if ($cohortEntry['isCurrent'])
                                            <x-ui.badge variant="outline" color="amber" icon="sparkles">Current page</x-ui.badge>
                                        @endif
                                    </div>

                                    <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                        {{ $cohortEntry['label'] }}
                                    </div>

                                    @if ($cohortEntry['meta'])
                                        <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ $cohortEntry['meta'] }}</div>
                                    @endif

                                    @if ($cohortEntry['creditedAs'])
                                        <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                            Credited as {{ $cohortEntry['creditedAs'] }}
                                        </div>
                                    @endif
                                </div>

                                <x-ui.icon name="arrow-right" class="size-4 shrink-0 text-neutral-400 dark:text-neutral-500" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>
    </section>
@endsection
