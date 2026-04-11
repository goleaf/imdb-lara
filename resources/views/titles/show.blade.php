@extends('layouts.public')

@section('title', $title->meta_title ?: $title->name)
@section('meta_description', $title->meta_description ?: ($title->plot_outline ?: 'Browse cast, awards, genres, ratings, and release details for '.$title->name.'.'))

@php
    $akaAttributeRows = $akaAttributeRows ?? collect();
    $akaTypeRows = $akaTypeRows ?? collect();
    $awardCategoryRows = $awardCategoryRows ?? collect();
    $awardEventRows = $awardEventRows ?? collect();
    $certificateAttributeRows = $certificateAttributeRows ?? collect();
    $certificateRatingRows = $certificateRatingRows ?? collect();
    $companyRows = $companyRows ?? collect();
    $interestHighlights = $interestHighlights ?? collect();
@endphp

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $title->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card data-slot="title-detail-hero" class="sb-detail-hero !max-w-none overflow-hidden p-0">
            <div class="relative">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $title->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-28"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(110deg,rgba(11,10,9,0.92),rgba(11,10,9,0.78),rgba(11,10,9,0.42))]"></div>
                @else
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(16,15,13,0.96),rgba(10,10,9,0.98))]"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[15rem_minmax(0,1fr)]">
                    <div class="overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                        @if ($poster)
                            <img
                                src="{{ $poster->url }}"
                                alt="{{ $poster->alt_text ?: $title->name }}"
                                class="aspect-[2/3] w-full object-cover"
                            >
                        @else
                            <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="film" class="size-14" />
                            </div>
                        @endif
                    </div>

                    <div class="space-y-6 p-5 sm:p-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="sb-detail-overline">{{ $title->typeLabel() }}</span>
                                @if ($title->release_year)
                                    <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $title->release_year }}</x-ui.badge>
                                @endif
                                @if ($title->runtime_minutes)
                                    <x-ui.badge variant="outline" color="neutral" icon="clock">{{ $title->runtime_minutes }} min</x-ui.badge>
                                @endif
                                @if ($title->age_rating)
                                    <x-ui.badge variant="outline" color="neutral" icon="shield-check">{{ $title->age_rating }}</x-ui.badge>
                                @endif
                            </div>

                            <div class="space-y-3">
                                <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $title->name }}</x-ui.heading>

                                @if ($title->original_name && $title->original_name !== $title->name)
                                    <x-ui.text class="text-sm text-[#a99f92] dark:text-[#a99f92]">
                                        Original title: {{ $title->original_name }}
                                    </x-ui.text>
                                @endif

                                <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                    {{ $title->summaryText() ?: 'A full plot outline has not been published yet.' }}
                                </x-ui.text>
                            </div>

                            @if ($title->genres->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($title->genres as $genre)
                                        <a href="{{ route('public.genres.show', $genre) }}" class="sb-detail-chip">
                                            {{ $genre->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ($heroStats as $heroStat)
                                <div class="sb-detail-stat p-4">
                                    <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">{{ $heroStat['label'] }}</div>
                                    <div class="mt-2 text-2xl font-semibold">{{ $heroStat['value'] }}</div>
                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $heroStat['copy'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex flex-wrap gap-3">
                            @if ($primaryVideo?->url)
                                <x-ui.button as="a" :href="$primaryVideo->url" icon="play" color="amber">
                                    Watch trailer
                                </x-ui.button>
                            @endif
                            <x-ui.button as="a" :href="$shareUrl" variant="outline" color="amber" icon="share">
                                Share title
                            </x-ui.button>
                            @if ($isSeriesLike && $seasons->isNotEmpty())
                                <x-ui.button as="a" href="#title-seasons" variant="ghost" icon="rectangle-stack">
                                    Browse seasons
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <div class="space-y-6">
                <x-ui.card class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Overview</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Core release, origin, and catalog facts from the imported MySQL dataset.
                            </x-ui.text>
                        </div>

                        @if ($detailItems->isNotEmpty())
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($detailItems as $item)
                                    <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">{{ $item['label'] }}</div>
                                        <div class="mt-2 text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $item['value'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($countries->isNotEmpty() || $languages->isNotEmpty())
                            <div class="grid gap-3 sm:grid-cols-2">
                                @if ($countries->isNotEmpty())
                                    <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Countries</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($countries as $country)
                                                <span class="inline-flex items-center gap-2 rounded-full border border-black/5 bg-white/80 px-3 py-1.5 text-sm font-medium text-neutral-900 dark:border-white/10 dark:bg-white/[0.05] dark:text-neutral-100">
                                                    <x-ui.flag type="country" :code="$country['code']" class="size-4" />
                                                    <span>{{ $country['label'] }}</span>
                                                    @if ($country['label'] !== $country['code'])
                                                        <span class="text-xs uppercase tracking-[0.16em] text-neutral-500 dark:text-neutral-400">{{ $country['code'] }}</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($languages->isNotEmpty())
                                    <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Languages</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($languages as $language)
                                                <span class="inline-flex items-center gap-2 rounded-full border border-black/5 bg-white/80 px-3 py-1.5 text-sm font-medium text-neutral-900 dark:border-white/10 dark:bg-white/[0.05] dark:text-neutral-100">
                                                    <x-ui.flag type="language" :code="$language['code']" class="size-4" />
                                                    <span>{{ $language['label'] }}</span>
                                                    @if ($language['label'] !== $language['code'])
                                                        <span class="text-xs uppercase tracking-[0.16em] text-neutral-500 dark:text-neutral-400">{{ $language['code'] }}</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($certificateItems->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach ($certificateItems as $certificateItem)
                                    <x-ui.badge variant="outline" color="neutral" icon="shield-check">
                                        {{ $certificateItem['rating'] }}{{ $certificateItem['country'] ? ' · '.$certificateItem['country'] : '' }}
                                    </x-ui.badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-ui.card>

                @if ($akaAttributeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-aka-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">AKA attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>aka_attributes</code> table and linked to this movie through its AKA records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($akaAttributeRows as $akaAttributeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $akaAttributeRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $akaAttributeRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($akaTypeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-aka-types" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">AKA types</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>aka_types</code> table and linked to this movie through its title AKA records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($akaTypeRows as $akaTypeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $akaTypeRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $akaTypeRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($awardCategoryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-award-categories" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Award categories</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>award_categories</code> table and linked to this movie through its award nominations.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($awardCategoryRows as $awardCategoryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $awardCategoryRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $awardCategoryRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($awardEventRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-award-events" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Award events</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>award_events</code> table and linked to this movie through its award nominations.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">imdb_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($awardEventRows as $awardEventRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $awardEventRow->imdb_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $awardEventRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($certificateAttributeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-certificate-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Certificate attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>certificate_attributes</code> table and linked to this movie through its certificate records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($certificateAttributeRows as $certificateAttributeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $certificateAttributeRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $certificateAttributeRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($certificateRatingRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-certificate-ratings" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Certificate ratings</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>certificate_ratings</code> table and linked to this movie through its certificate records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($certificateRatingRows as $certificateRatingRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $certificateRatingRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $certificateRatingRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($companyRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-companies" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Companies</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>companies</code> table and linked to this movie through its company credit records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">imdb_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($companyRows as $companyRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $companyRow->imdb_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $companyRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($companyCreditAttributeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-company-credit-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Company credit attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>company_credit_attributes</code> table and linked to this movie through its company credit records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($companyCreditAttributeRows as $companyCreditAttributeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $companyCreditAttributeRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $companyCreditAttributeRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($companyCreditCategoryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-company-credit-categories" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Company credit categories</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>company_credit_categories</code> table and linked to this movie through its company credit records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($companyCreditCategoryRows as $companyCreditCategoryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $companyCreditCategoryRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $companyCreditCategoryRow->name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($interestHighlights->isNotEmpty())
                    <x-ui.card data-slot="title-discovery-profile" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Discovery profile</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Imported interest tags and subgenre signals that shape related-title discovery across the MySQL catalog.
                                </x-ui.text>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($interestHighlights as $interestHighlight)
                                    <a href="{{ $interestHighlight['href'] }}" class="sb-detail-chip">
                                        {{ $interestHighlight['name'] }}
                                        @if ($interestHighlight['isSubgenre'])
                                            <span class="text-[0.7rem] uppercase tracking-[0.18em] text-[#988f82]">Subgenre</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>

                            <x-ui.link :href="route('public.titles.metadata', $title)" variant="ghost" iconAfter="arrow-right">
                                Open the full keywords and connections view
                            </x-ui.link>
                        </div>
                    </x-ui.card>
                @endif

                @if ($castPreview->isNotEmpty())
                    <x-ui.card class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Featured cast</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Principal cast and on-screen talent linked from the imported credits table.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($castPreview as $credit)
                                    @if ($credit->person)
                                        <a href="{{ route('public.people.show', $credit->person) }}" class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                            <div class="flex items-center gap-3">
                                                <x-ui.avatar
                                                    :src="$credit->person->preferredHeadshot()?->url"
                                                    :alt="$credit->person->preferredHeadshot()?->alt_text ?: $credit->person->name"
                                                    :name="$credit->person->name"
                                                    color="auto"
                                                    class="!h-14 !w-14 shrink-0 border border-black/5 dark:border-white/10"
                                                />
                                                <div class="min-w-0">
                                                    <div class="truncate font-medium text-neutral-900 dark:text-neutral-100">{{ $credit->person->name }}</div>
                                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $credit->job }}</div>
                                                </div>
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($crewGroups->isNotEmpty())
                    <x-ui.card class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Key crew</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Behind-the-camera departments condensed into the key creative groups on this title.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($crewGroups as $crewGroup)
                                    <div class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $crewGroup['role'] }}</div>
                                        <div class="mt-3 space-y-2">
                                            @foreach ($crewGroup['credits'] as $credit)
                                                @if ($credit->person)
                                                    <a href="{{ route('public.people.show', $credit->person) }}" class="flex items-center justify-between gap-3 text-sm text-neutral-600 transition hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-neutral-100">
                                                        <span>{{ $credit->person->name }}</span>
                                                        <x-ui.icon name="arrow-right" class="size-4" />
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($isSeriesLike && $seasonNavigation->isNotEmpty())
                    <x-ui.card id="title-seasons" data-slot="series-guide-navigation" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Series guide</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Season navigation, latest-season context, and standout episodes built from the imported season and episode index.
                                </x-ui.text>
                            </div>

                            <div class="space-y-3">
                                <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Season navigation</div>

                                <div class="sb-season-nav-row">
                                    @foreach ($seasonNavigation as $navigationSeason)
                                        <a
                                            href="{{ route('public.seasons.show', ['series' => $title, 'season' => $navigationSeason]) }}"
                                            class="sb-season-nav-pill"
                                        >
                                            <span class="sb-season-nav-pill-title">Season {{ $navigationSeason->season_number }}</span>
                                            <span class="sb-season-nav-pill-meta">{{ number_format((int) $navigationSeason->episodes_count) }} episodes</span>
                                        </a>
                                    @endforeach
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($seasonNavigation as $navigationSeason)
                                        <a href="{{ route('public.seasons.show', ['series' => $title, 'season' => $navigationSeason]) }}" class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                            <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $navigationSeason->name }}</div>
                                            <div class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                                {{ number_format((int) $navigationSeason->episodes_count) }} episodes
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </x-ui.card>

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                        <x-ui.card data-slot="series-guide-latest-season" class="sb-detail-section !max-w-none">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <x-ui.heading level="h2" size="lg">Latest season overview</x-ui.heading>
                                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                            The newest season currently mapped inside the imported episode hierarchy.
                                        </x-ui.text>
                                    </div>

                                    @if ($latestSeason)
                                        <x-ui.badge variant="outline" color="slate" icon="rectangle-stack">
                                            {{ $latestSeason->name }}
                                        </x-ui.badge>
                                    @endif
                                </div>

                                @if ($latestSeason)
                                    <div class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $latestSeason->name }}</div>
                                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ number_format((int) $latestSeason->episodes_count) }} published episodes in the current guide.
                                                </div>
                                            </div>

                                            <x-ui.link :href="route('public.seasons.show', ['series' => $title, 'season' => $latestSeason])" variant="ghost" iconAfter="arrow-right">
                                                Open season page
                                            </x-ui.link>
                                        </div>
                                    </div>
                                @endif

                                @if ($latestSeasonEpisodes->isNotEmpty() && $latestSeason)
                                    <div class="grid gap-3">
                                        @foreach ($latestSeasonEpisodes as $episodeMeta)
                                            <article class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                                <div class="flex flex-wrap items-start justify-between gap-3">
                                                    <div class="space-y-2">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <x-ui.badge variant="outline" color="neutral" icon="rectangle-stack">
                                                                Episode {{ $episodeMeta->episode_number }}
                                                            </x-ui.badge>
                                                            @if ($episodeMeta->aired_at)
                                                                <x-ui.badge variant="outline" color="slate" icon="calendar-days">
                                                                    {{ $episodeMeta->aired_at->format('M j, Y') }}
                                                                </x-ui.badge>
                                                            @endif
                                                        </div>

                                                        <x-ui.heading level="h3" size="md">
                                                            <a href="{{ route('public.episodes.show', ['series' => $title, 'season' => $latestSeason, 'episode' => $episodeMeta->title]) }}" class="hover:opacity-80">
                                                                {{ $episodeMeta->title->name }}
                                                            </a>
                                                        </x-ui.heading>

                                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                            {{ $episodeMeta->title->summaryText() ?: 'No public synopsis is available for this episode yet.' }}
                                                        </x-ui.text>
                                                    </div>

                                                    <div class="text-right text-sm text-neutral-500 dark:text-neutral-400">
                                                        <div>{{ $episodeMeta->title->displayAverageRating() ? number_format($episodeMeta->title->displayAverageRating(), 1) : 'N/A' }}</div>
                                                        <div class="mt-1">{{ number_format((int) ($episodeMeta->title->statistic?->rating_count ?? 0)) }} votes</div>
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @else
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="rectangle-stack" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">Episode rows for the newest season have not been imported yet.</x-ui.heading>
                                    </x-ui.empty>
                                @endif
                            </div>
                        </x-ui.card>

                        <x-ui.card data-slot="series-guide-top-episodes" class="sb-detail-section !max-w-none">
                            <div class="space-y-4">
                                <div>
                                    <x-ui.heading level="h2" size="lg">Top-rated episodes</x-ui.heading>
                                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                        Highest-rated episodes across the published series guide.
                                    </x-ui.text>
                                </div>

                                @if ($topRatedEpisodes->isNotEmpty())
                                    <div class="space-y-3">
                                        @foreach ($topRatedEpisodes as $episodeMeta)
                                            <a href="{{ route('public.episodes.show', ['series' => $title, 'season' => 'season-'.$episodeMeta->season_number, 'episode' => $episodeMeta->title]) }}" class="flex items-center justify-between gap-3 rounded-[1rem] border border-black/5 bg-white/70 px-4 py-3 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                                <div>
                                                    <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $episodeMeta->title->name }}</div>
                                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                        Season {{ $episodeMeta->season_number }} · Episode {{ $episodeMeta->episode_number }}
                                                    </div>
                                                </div>

                                                <div class="text-right">
                                                    <x-ui.badge color="amber" icon="star">
                                                        {{ $episodeMeta->title->displayAverageRating() ? number_format($episodeMeta->title->displayAverageRating(), 1) : 'N/A' }}
                                                    </x-ui.badge>
                                                    <div class="mt-2 text-xs uppercase tracking-[0.16em] text-neutral-500 dark:text-neutral-400">
                                                        {{ number_format((int) ($episodeMeta->title->statistic?->rating_count ?? 0)) }} votes
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="star" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">Episode ratings have not been imported for this series yet.</x-ui.heading>
                                    </x-ui.empty>
                                @endif
                            </div>
                        </x-ui.card>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                @if ($awardHighlights->isNotEmpty())
                    <x-ui.card class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Awards</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Highlighted nominations and wins connected to this title in the imported catalog.
                                </x-ui.text>
                            </div>

                            <div class="space-y-3">
                                @foreach ($awardHighlights as $awardNomination)
                                    <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $awardNomination->awardCategory?->name ?: 'Award nomination' }}</div>
                                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $awardNomination->awardEvent?->name ?: 'Event' }}
                                                    @if ($awardNomination->award_year)
                                                        · {{ $awardNomination->award_year }}
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($awardNomination->is_winner)
                                                <x-ui.badge color="amber" icon="trophy">Winner</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="outline" color="neutral" icon="bookmark">Nominee</x-ui.badge>
                                            @endif
                                        </div>

                                        @if ($awardNomination->text)
                                            <x-ui.text class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                                                {{ $awardNomination->text }}
                                            </x-ui.text>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($galleryAssets->isNotEmpty())
                    <x-ui.card class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Gallery</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Posters, backdrops, stills, and gallery images linked to this title.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 grid-cols-2">
                                @foreach ($galleryAssets as $asset)
                                    <a href="{{ $asset->url }}" class="overflow-hidden rounded-[1.1rem] border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                        <img src="{{ $asset->url }}" alt="{{ $asset->alt_text ?: $title->name }}" class="aspect-[4/3] w-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($archiveLinks->isNotEmpty())
                    <x-ui.card class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Archive Views</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Dedicated read-only surfaces for the imported MySQL data attached to this title.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                                @foreach ($archiveLinks as $archiveLink)
                                    <a href="{{ $archiveLink['href'] }}" class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-2">
                                                    <x-ui.icon :name="$archiveLink['icon']" class="size-4 text-[#d6b574]" />
                                                    <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $archiveLink['label'] }}</span>
                                                </div>
                                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                    {{ $archiveLink['copy'] }}
                                                </x-ui.text>
                                            </div>

                                            <div class="shrink-0 text-right">
                                                <x-ui.badge variant="outline" color="neutral">{{ $archiveLink['status'] }}</x-ui.badge>
                                                <div class="mt-3 text-sm text-[#988f82]">Open view</div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif
            </div>
        </div>

        @if ($relatedTitles->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="sb-page-kicker">Related titles</div>
                        <x-ui.heading level="h2" size="lg">More from the same catalog neighborhood</x-ui.heading>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($relatedTitles as $relatedTitle)
                        <x-catalog.title-card :title="$relatedTitle" />
                    @endforeach
                </div>
            </section>
        @endif
    </section>
@endsection
