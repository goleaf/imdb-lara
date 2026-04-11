@extends('layouts.public')

@section('title', $title->meta_title ?: $title->name)
@section('meta_description', $title->meta_description ?: ($title->plot_outline ?: 'Browse cast, awards, genres, ratings, and release details for '.$title->name.'.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $title->name }}</x-ui.breadcrumbs.item>
@endsection

@php
    foreach ([
        'galleryAssets',
        'castPreview',
        'crewGroups',
        'movieAkaRows',
        'movieAkaAttributeRows',
        'akaAttributeRows',
        'akaTypeRows',
        'awardCategoryRows',
        'awardEventRows',
        'movieAwardNominationRows',
        'movieAwardNominationNomineeRows',
        'movieAwardNominationTitleRows',
        'movieAwardNominationSummaryRows',
        'movieCertificateRows',
        'movieCertificateSummaryRows',
        'movieCertificateAttributeRows',
        'movieCompanyCreditRows',
        'movieCompanyCreditAttributeRows',
        'movieCompanyCreditCountryRows',
        'movieCompanyCreditSummaryRows',
        'movieDirectorRows',
        'movieEpisodeRows',
        'movieEpisodeSummaryRows',
        'movieGenreRows',
        'movieImageSummaryRows',
        'certificateAttributeRows',
        'certificateRatingRows',
        'companyEntries',
        'companyRows',
        'companyCreditAttributeRows',
        'companyCreditCategoryRows',
        'movieBoxOfficeRows',
        'currencyRows',
        'countryRows',
        'genreRows',
        'interestRows',
        'interestCategoryRows',
        'interestPrimaryImageRows',
        'interestSimilarInterestRows',
        'detailItems',
        'certificateItems',
        'awardHighlights',
        'relatedTitles',
        'seasonNavigation',
        'seasons',
        'latestSeasonEpisodes',
        'topRatedEpisodes',
        'countries',
        'languages',
        'interestHighlights',
        'archiveLinks',
        'heroStats',
    ] as $collectionVariable) {
        if (! isset($$collectionVariable) || ! ($$collectionVariable instanceof \Illuminate\Support\Collection)) {
            $$collectionVariable = collect();
        }
    }

    $poster ??= null;
    $backdrop ??= null;
    $primaryVideo ??= null;
    $latestSeason ??= null;
    $shareModalId ??= 'title-share-'.$title->id;
    $shareUrl ??= route('public.titles.show', $title);
    $isSeriesLike ??= false;
    $ratingCount ??= 0;
    $posterLightboxModalId = 'title-poster-lightbox-'.$title->id;
@endphp

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

                <div class="relative grid gap-6 p-6">
                    @if ($poster)
                        <button
                            type="button"
                            x-data
                            x-on:click="$modal.open(@js($posterLightboxModalId))"
                            class="group mx-auto block w-full max-w-[15rem] text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-[#f4eee5] focus-visible:ring-offset-4 focus-visible:ring-offset-[#0b0a09]"
                            data-slot="title-detail-poster-trigger"
                        >
                            <div class="overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                                <div class="aspect-[2/3] w-full overflow-hidden">
                                    <img
                                        src="{{ $poster->url }}"
                                        alt="{{ $poster->alt_text ?: $title->name }}"
                                        class="block h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                    >
                                </div>
                            </div>
                        </button>
                    @else
                        <div class="mx-auto w-full max-w-[15rem] overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                            <div class="flex aspect-[2/3] w-full items-center justify-center text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="film" class="size-14" />
                            </div>
                        </div>
                    @endif

                    <div class="space-y-6 p-5 sm:p-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="sb-detail-overline">{{ $title->typeLabel() }}</span>
                                @if ($title->release_year)
                                    <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $title->release_year }}</x-ui.badge>
                                @endif
                                @if ($title->runtimeMinutesLabel())
                                    <x-ui.badge variant="outline" color="neutral" icon="clock">{{ $title->runtimeMinutesLabel() }}</x-ui.badge>
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

        @if ($poster)
            @push('modals')
                <x-ui.modal
                    :id="$posterLightboxModalId"
                    bare
                    width="screen"
                    backdrop="dark"
                    animation="fade"
                >
                    <div
                        class="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top_right,rgba(214,181,116,0.16),transparent_24%),linear-gradient(180deg,rgba(8,8,7,0.98),rgba(5,5,5,0.995))] p-4 sm:p-6"
                        data-slot="title-detail-poster-lightbox"
                    >
                        <div class="relative inline-flex max-w-full items-start justify-center">
                            <button
                                type="button"
                                class="sb-media-lightbox-close sb-media-lightbox-close--corner"
                                x-on:click="$modal.close(@js($posterLightboxModalId))"
                            >
                                <x-ui.icon name="x-mark" class="size-5" />
                                <span class="sr-only">Close poster lightbox</span>
                            </button>

                            <img
                                src="{{ $poster->url }}"
                                alt="{{ $poster->alt_text ?: $title->name }}"
                                @class([
                                    'sb-media-lightbox-image',
                                    'sb-media-lightbox-image--portrait' => ($poster->height ?? 0) > ($poster->width ?? 0),
                                    'sb-media-lightbox-image--landscape' => ($poster->height ?? 0) <= ($poster->width ?? 0),
                                ])
                            >
                        </div>
                    </div>
                </x-ui.modal>
            @endpush
        @endif

        <div class="grid gap-6">
            <div class="space-y-6">
                <x-ui.card class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Overview</x-ui.heading>
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

                @if ($movieAkaRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-akas" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie AKAs</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_akas</code> table and linked directly to this movie.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Title</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Country</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Language</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieAkaRows as $movieAkaRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieAkaRow->text }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        @if (filled($movieAkaRow->country_code))
                                                            <div class="flex items-center gap-2">
                                                                <x-ui.flag type="country" :code="$movieAkaRow->country_code" class="size-4" />
                                                                <span>{{ $movieAkaRow->resolvedCountryLabel() ?? $movieAkaRow->country_code }}</span>
                                                            </div>
                                                        @else
                                                            <span>&mdash;</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAkaRow->resolvedLanguageLabel() ?? '—' }}
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

                @if ($movieAkaAttributeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-aka-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie AKA attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_aka_attributes</code> table and linked to this movie through its movie AKA records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">movie_aka_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">aka_attribute_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">position</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieAkaAttributeRows as $movieAkaAttributeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieAkaAttributeRow->movie_aka_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAkaAttributeRow->aka_attribute_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAkaAttributeRow->position }}
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
                                    Award categories linked to this title through its nominations.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Category</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($awardCategoryRows as $awardCategoryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
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
                                    Award events linked to this title through its nominations.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Event</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($awardEventRows as $awardEventRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
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

                @if ($movieAwardNominationRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-award-nominations" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie award nominations</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Award nominations attached directly to this title.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Event</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Category</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Year</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Note</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Winner</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Winner rank</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieAwardNominationRows as $movieAwardNominationRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieAwardNominationRow->awardEvent?->name ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAwardNominationRow->awardCategory?->name ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAwardNominationRow->award_year ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ filled($movieAwardNominationRow->text) ? $movieAwardNominationRow->text : '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAwardNominationRow->is_winner ? 'Yes' : 'No' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        @if ($movieAwardNominationRow->winner_rank)
                                                            <x-catalog.winner-rank-badge :rank="$movieAwardNominationRow->winner_rank" />
                                                        @else
                                                            —
                                                        @endif
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

                @if ($movieAwardNominationNomineeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-award-nomination-nominees" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie award nomination nominees</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Nominees linked to this title's award nominations.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Nomination</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Nominee</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieAwardNominationNomineeRows as $movieAwardNominationNomineeRow)
                                                @php
                                                    $nomineePerson = $movieAwardNominationNomineeRow->person;
                                                    $nomineeHeadshot = $nomineePerson?->preferredHeadshot();
                                                    $nomineeHref = $nomineePerson ? route('public.people.show', $nomineePerson) : null;
                                                    $nomineeName = $nomineePerson?->name;
                                                @endphp
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        @if ($movieAwardNominationNomineeRow->awardNomination)
                                                            <a
                                                                href="{{ route('public.awards.nominations.show', $movieAwardNominationNomineeRow->awardNomination) }}"
                                                                class="block rounded-[1rem] transition hover:opacity-80"
                                                            >
                                                                <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                                                    {{ $movieAwardNominationNomineeRow->awardNomination->awardCategory?->name ?: 'Award nomination' }}
                                                                </div>
                                                                <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                                    {{ $movieAwardNominationNomineeRow->awardNomination->awardEvent?->name ?: 'Event' }}
                                                                    @if ($movieAwardNominationNomineeRow->awardNomination->award_year)
                                                                        · {{ $movieAwardNominationNomineeRow->awardNomination->award_year }}
                                                                    @endif
                                                                </div>
                                                            </a>
                                                        @else
                                                            <div class="font-medium text-neutral-900 dark:text-neutral-100">Award nomination</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        @if ($nomineePerson && $nomineeHref)
                                                            <a
                                                                href="{{ $nomineeHref }}"
                                                                data-slot="title-detail-award-nominee-link"
                                                                class="flex items-center gap-3 rounded-[1rem] transition hover:opacity-80"
                                                            >
                                                                <div
                                                                    data-slot="title-detail-award-nominee-avatar"
                                                                    class="flex h-12 w-10 shrink-0 items-center justify-center overflow-hidden rounded-[0.8rem] border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-white/[0.04]"
                                                                >
                                                                    @if ($nomineeHeadshot)
                                                                        <img
                                                                            src="{{ $nomineeHeadshot->url }}"
                                                                            alt="{{ $nomineeHeadshot->alt_text ?: $nomineeName }}"
                                                                            class="h-full w-full object-cover"
                                                                            loading="lazy"
                                                                        >
                                                                    @else
                                                                        <x-ui.icon name="user" class="size-4 text-neutral-400 dark:text-neutral-500" />
                                                                    @endif
                                                                </div>

                                                                <span class="min-w-0 truncate font-medium text-neutral-900 dark:text-neutral-100">
                                                                    {{ $nomineeName }}
                                                                </span>
                                                            </a>
                                                        @else
                                                            —
                                                        @endif
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

                @if ($movieAwardNominationSummaryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-award-nomination-summaries" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie award nomination summaries</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_award_nomination_summaries</code> table and linked directly to this movie.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Nominations</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Wins</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieAwardNominationSummaryRows as $movieAwardNominationSummaryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAwardNominationSummaryRow->nomination_count }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAwardNominationSummaryRow->win_count }}
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

                @if ($movieAwardNominationTitleRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-award-nomination-titles" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie award nomination titles</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Nominated titles linked to this title's award nominations.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Nomination</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Title</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieAwardNominationTitleRows as $movieAwardNominationTitleRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieAwardNominationTitleRow->movieAwardNomination?->awardCategory?->name ?? '—' }}
                                                        @if (filled($movieAwardNominationTitleRow->movieAwardNomination?->event?->name) || filled($movieAwardNominationTitleRow->movieAwardNomination?->award_year))
                                                            <div class="mt-1 text-xs font-normal text-neutral-500 dark:text-neutral-400">
                                                                {{ $movieAwardNominationTitleRow->movieAwardNomination?->event?->name ?? 'Unknown event' }}
                                                                @if (filled($movieAwardNominationTitleRow->movieAwardNomination?->award_year))
                                                                    · {{ $movieAwardNominationTitleRow->movieAwardNomination?->award_year }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieAwardNominationTitleRow->title?->name ?? $movieAwardNominationTitleRow->nominated_movie_id }}
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

                @if ($movieCertificateRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-certificates" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie certificates</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Certificate records linked directly to this title.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Rating</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Meaning</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Country</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieCertificateRows as $movieCertificateRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top text-neutral-900 dark:text-neutral-100">
                                                        @if ($movieCertificateRow->certificateRating)
                                                            <x-catalog.certificate-rating-chip :rating="$movieCertificateRow->certificateRating" />
                                                        @else
                                                            <span class="text-neutral-500 dark:text-neutral-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCertificateRow->ratingDescription() ?? 'Regional age classification attached to this title.' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        @if (filled($movieCertificateRow->country_code))
                                                            <div class="flex items-center gap-2">
                                                                <x-ui.flag type="country" :code="$movieCertificateRow->country_code" class="size-4" />
                                                                <span>{{ $movieCertificateRow->resolvedCountryLabel() ?? $movieCertificateRow->country_code }}</span>
                                                            </div>
                                                        @else
                                                            —
                                                        @endif
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

                @if ($movieCertificateAttributeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-certificate-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie certificate attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Certificate attributes linked to this title's certificate records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Rating</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Attribute</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieCertificateAttributeRows as $movieCertificateAttributeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top text-neutral-900 dark:text-neutral-100">
                                                        @if ($movieCertificateAttributeRow->movieCertificate?->certificateRating)
                                                            <x-catalog.certificate-rating-chip :rating="$movieCertificateAttributeRow->movieCertificate->certificateRating" />
                                                        @else
                                                            <span class="text-neutral-500 dark:text-neutral-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCertificateAttributeRow->certificateAttribute?->name ?? $movieCertificateAttributeRow->certificate_attribute_id }}
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

                @if ($movieCertificateSummaryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-certificate-summaries" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie certificate summaries</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_certificate_summaries</code> table and linked directly to this movie.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">movie_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">total_count</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieCertificateSummaryRows as $movieCertificateSummaryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieCertificateSummaryRow->movie_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCertificateSummaryRow->total_count }}
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

                @if ($certificateAttributeEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-certificate-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Certificate attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Certificate attributes linked to this title through its certificate records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Attribute</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Ratings on this title</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Countries on this title</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Certificates on this title</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($certificateAttributeEntries as $certificateAttributeEntry)
                                                <tr>
                                                    <td class="px-4 py-3 align-top">
                                                        <a
                                                            href="{{ route('public.certificate-attributes.show', $certificateAttributeEntry['attribute']) }}"
                                                            class="font-medium text-neutral-900 transition hover:opacity-80 dark:text-neutral-100"
                                                        >
                                                            {{ $certificateAttributeEntry['attribute']->name }}
                                                        </a>
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        <div class="flex flex-wrap gap-2">
                                                            @forelse ($certificateAttributeEntry['ratings'] as $certificateRating)
                                                                <x-catalog.certificate-rating-chip
                                                                    :rating="$certificateRating"
                                                                    class="text-xs"
                                                                />
                                                            @empty
                                                                <span class="text-neutral-500 dark:text-neutral-400">—</span>
                                                            @endforelse
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        <div class="flex flex-wrap gap-2">
                                                            @forelse ($certificateAttributeEntry['countries'] as $country)
                                                                <span class="inline-flex items-center gap-2 rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                                    <x-ui.flag type="country" :code="$country['code']" class="size-3.5" />
                                                                    <span>{{ $country['label'] }}</span>
                                                                </span>
                                                            @empty
                                                                <span class="text-neutral-500 dark:text-neutral-400">—</span>
                                                            @endforelse
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ number_format($certificateAttributeEntry['usageCount']) }}
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

                @if ($certificateRatingEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-certificate-ratings" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Certificate ratings</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Certificate ratings linked to this title through its certificate records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Rating</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Countries on this title</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Attributes on this title</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Certificates on this title</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($certificateRatingEntries as $certificateRatingEntry)
                                                <tr>
                                                    <td class="px-4 py-3 align-top">
                                                        <div class="space-y-2">
                                                            <x-catalog.certificate-rating-chip :rating="$certificateRatingEntry['rating']" />
                                                            <div class="sb-certificate-rating-note">
                                                                {{ $certificateRatingEntry['rating']->shortDescription() }}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        <div class="flex flex-wrap gap-2">
                                                            @forelse ($certificateRatingEntry['countries'] as $country)
                                                                <span class="inline-flex items-center gap-2 rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                                    <x-ui.flag type="country" :code="$country['code']" class="size-3.5" />
                                                                    <span>{{ $country['label'] }}</span>
                                                                </span>
                                                            @empty
                                                                <span class="text-neutral-500 dark:text-neutral-400">—</span>
                                                            @endforelse
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        <div class="flex flex-wrap gap-2">
                                                            @forelse ($certificateRatingEntry['attributes'] as $certificateAttribute)
                                                                <a
                                                                    href="{{ route('public.certificate-attributes.show', $certificateAttribute) }}"
                                                                    class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium text-neutral-700 transition hover:bg-white dark:border-white/10 dark:text-neutral-200 dark:hover:bg-white/[0.05]"
                                                                >
                                                                    {{ $certificateAttribute->name }}
                                                                </a>
                                                            @empty
                                                                <span class="text-neutral-500 dark:text-neutral-400">—</span>
                                                            @endforelse
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ number_format($certificateRatingEntry['usageCount']) }}
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

                @if ($movieCompanyCreditRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-company-credits" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie company credits</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Company credits linked directly to this title.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Company</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Category</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Start year</th>
                                                <th scope="col" class="px-4 py-3 font-medium">End year</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieCompanyCreditRows as $movieCompanyCreditRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        @if ($movieCompanyCreditRow->company)
                                                            <a
                                                                href="{{ route('public.companies.show', $movieCompanyCreditRow->company) }}"
                                                                class="transition hover:opacity-80"
                                                            >
                                                                {{ $movieCompanyCreditRow->company->name }}
                                                            </a>
                                                        @else
                                                            {{ $movieCompanyCreditRow->company_imdb_id }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCompanyCreditRow->companyCreditCategory?->name ?? $movieCompanyCreditRow->company_credit_category_id ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCompanyCreditRow->start_year ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCompanyCreditRow->end_year ?? '—' }}
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

                @if ($movieDirectorRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-directors" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie directors</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Directors linked directly to this title.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($movieDirectorRows as $movieDirectorRow)
                                    @php
                                        $directorPerson = $movieDirectorRow->person;
                                        $directorName = $directorPerson?->name
                                            ?? $movieDirectorRow->nameBasic?->displayName
                                            ?? $movieDirectorRow->nameBasic?->primaryname
                                            ?? (string) $movieDirectorRow->name_basic_id;
                                        $directorHeadshot = $directorPerson?->preferredHeadshot();
                                        $directorProfileHref = $directorPerson ? route('public.people.show', $directorPerson) : null;
                                        $directorArchiveHref = $directorPerson
                                            ? route('public.people.show', ['person' => $directorPerson, 'job' => 'Directing']).'#person-filmography'
                                            : null;
                                        $directorSummary = $directorPerson?->summaryText();
                                        $directorProfessionLabels = collect($directorPerson?->professionLabels() ?? []);
                                    @endphp

                                    <div class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="flex items-start gap-4">
                                            @if ($directorProfileHref)
                                                <a href="{{ $directorProfileHref }}" class="shrink-0" data-slot="title-detail-director-avatar-link">
                                                    <x-ui.avatar
                                                        :src="$directorHeadshot?->url"
                                                        :alt="$directorHeadshot?->alt_text ?: $directorName"
                                                        :name="$directorName"
                                                        color="auto"
                                                        class="!h-20 !w-16 rounded-[1rem] border border-black/5 dark:border-white/10"
                                                    />
                                                </a>
                                            @else
                                                <x-ui.avatar
                                                    :src="$directorHeadshot?->url"
                                                    :alt="$directorHeadshot?->alt_text ?: $directorName"
                                                    :name="$directorName"
                                                    color="auto"
                                                    class="!h-20 !w-16 shrink-0 rounded-[1rem] border border-black/5 dark:border-white/10"
                                                />
                                            @endif

                                            <div class="min-w-0 flex-1 space-y-3">
                                                <div class="space-y-2">
                                                    @if ($directorProfileHref)
                                                        <a
                                                            href="{{ $directorProfileHref }}"
                                                            data-slot="title-detail-director-link"
                                                            class="block text-lg font-semibold text-neutral-900 transition hover:opacity-80 dark:text-neutral-100"
                                                        >
                                                            {{ $directorName }}
                                                        </a>
                                                    @else
                                                        <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                                            {{ $directorName }}
                                                        </div>
                                                    @endif

                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($directorProfessionLabels as $directorProfessionLabel)
                                                            <x-ui.badge variant="outline" color="slate" icon="briefcase">
                                                                {{ $directorProfessionLabel }}
                                                            </x-ui.badge>
                                                        @endforeach

                                                        @if ($directorPerson?->nationality)
                                                            <x-ui.badge variant="outline" color="neutral" icon="globe-alt">
                                                                {{ $directorPerson->nationality }}
                                                            </x-ui.badge>
                                                        @endif

                                                        @if ($directorPerson?->credits_count)
                                                            <x-ui.badge variant="outline" color="neutral" icon="film">
                                                                {{ $directorPerson->creditsBadgeLabel() }}
                                                            </x-ui.badge>
                                                        @endif
                                                    </div>

                                                    @if ($directorSummary)
                                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                            {{ str($directorSummary)->limit(180) }}
                                                        </x-ui.text>
                                                    @endif
                                                </div>

                                                <div class="flex flex-wrap gap-2">
                                                    @if ($directorProfileHref)
                                                        <x-ui.button as="a" :href="$directorProfileHref" variant="outline" size="sm" icon="user">
                                                            View person
                                                        </x-ui.button>
                                                    @endif

                                                    @if ($directorArchiveHref)
                                                        <x-ui.button as="a" :href="$directorArchiveHref" size="sm" icon="film">
                                                            Directed titles
                                                        </x-ui.button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($companyEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-companies" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Companies</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Companies connected to this title through its company credit records.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($companyEntries as $companyEntry)
                                    <div class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="space-y-4">
                                            <div class="space-y-2">
                                                <a
                                                    href="{{ route('public.companies.show', $companyEntry['company']) }}"
                                                    class="block text-lg font-semibold text-neutral-900 transition hover:opacity-80 dark:text-neutral-100"
                                                >
                                                    {{ $companyEntry['company']->name }}
                                                </a>

                                                <div class="flex flex-wrap gap-2 text-sm text-neutral-600 dark:text-neutral-300">
                                                    <x-ui.badge variant="outline" color="neutral" icon="building-office-2">
                                                        {{ number_format($companyEntry['creditCount']) }} record{{ $companyEntry['creditCount'] === 1 ? '' : 's' }} on this title
                                                    </x-ui.badge>

                                                    @foreach ($companyEntry['activeYears'] as $companyYearsLabel)
                                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">
                                                            {{ $companyYearsLabel }}
                                                        </x-ui.badge>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="space-y-3">
                                                <div class="space-y-2">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Categories</div>
                                                    <div class="flex flex-wrap gap-2">
                                                        @forelse ($companyEntry['categories'] as $companyCategory)
                                                            <span class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium text-neutral-700 dark:border-white/10 dark:text-neutral-200">
                                                                {{ $companyCategory->name }}
                                                            </span>
                                                        @empty
                                                            <span class="text-sm text-neutral-500 dark:text-neutral-400">No category rows recorded.</span>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                <div class="space-y-2">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Countries</div>
                                                    <div class="flex flex-wrap gap-2">
                                                        @forelse ($companyEntry['countries'] as $companyCountry)
                                                            <span class="inline-flex items-center gap-2 rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                                <x-ui.flag type="country" :code="$companyCountry['code']" class="size-3.5" />
                                                                <span>{{ $companyCountry['label'] }}</span>
                                                            </span>
                                                        @empty
                                                            <span class="text-sm text-neutral-500 dark:text-neutral-400">No country rows recorded.</span>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                <div class="space-y-2">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Attributes</div>
                                                    <div class="flex flex-wrap gap-2">
                                                        @forelse ($companyEntry['attributes'] as $companyAttribute)
                                                            <span class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium text-neutral-700 dark:border-white/10 dark:text-neutral-200">
                                                                {{ $companyAttribute->name }}
                                                            </span>
                                                        @empty
                                                            <span class="text-sm text-neutral-500 dark:text-neutral-400">No attribute rows recorded.</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex justify-start">
                                                <x-ui.button as="a" :href="route('public.companies.show', $companyEntry['company'])" variant="outline" icon="building-office-2">
                                                    Open company archive
                                                </x-ui.button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($movieCompanyCreditAttributeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-company-credit-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie company credit attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Company credit attributes linked to this title's company records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Company</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Category</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Attribute</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieCompanyCreditAttributeRows as $movieCompanyCreditAttributeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieCompanyCreditAttributeRow->movieCompanyCredit?->company?->name ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCompanyCreditAttributeRow->movieCompanyCredit?->companyCreditCategory?->name ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCompanyCreditAttributeRow->companyCreditAttribute?->name ?? $movieCompanyCreditAttributeRow->company_credit_attribute_id }}
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

                @if ($movieCompanyCreditCountryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-company-credit-countries" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie company credit countries</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Countries linked to this title's company credits.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Company</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Category</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Country</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieCompanyCreditCountryRows as $movieCompanyCreditCountryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieCompanyCreditCountryRow->movieCompanyCredit?->company?->name ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCompanyCreditCountryRow->movieCompanyCredit?->companyCreditCategory?->name ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        @if (filled($movieCompanyCreditCountryRow->country_code))
                                                            <div class="flex items-center gap-2">
                                                                <x-ui.flag type="country" :code="$movieCompanyCreditCountryRow->country_code" class="size-4" />
                                                                <span>{{ $movieCompanyCreditCountryRow->resolvedCountryLabel() ?? $movieCompanyCreditCountryRow->country_code }}</span>
                                                            </div>
                                                        @else
                                                            —
                                                        @endif
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

                @if ($movieCompanyCreditSummaryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-company-credit-summaries" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie company credit summaries</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_company_credit_summaries</code> table and linked directly to this movie.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">movie_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">total_count</th>
                                                <th scope="col" class="px-4 py-3 font-medium">next_page_token</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieCompanyCreditSummaryRows as $movieCompanyCreditSummaryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieCompanyCreditSummaryRow->movie_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCompanyCreditSummaryRow->total_count }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieCompanyCreditSummaryRow->next_page_token }}
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

                @if ($movieEpisodeSummaryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-episode-summaries" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie episode summaries</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_episode_summaries</code> table and linked directly to this movie.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">movie_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">total_count</th>
                                                <th scope="col" class="px-4 py-3 font-medium">next_page_token</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieEpisodeSummaryRows as $movieEpisodeSummaryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieEpisodeSummaryRow->movie_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieEpisodeSummaryRow->total_count }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieEpisodeSummaryRow->next_page_token }}
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

                @if ($movieEpisodeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-episodes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie episodes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_episodes</code> table and linked directly to this movie.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">episode_movie_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">movie_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">season</th>
                                                <th scope="col" class="px-4 py-3 font-medium">episode_number</th>
                                                <th scope="col" class="px-4 py-3 font-medium">release_year</th>
                                                <th scope="col" class="px-4 py-3 font-medium">release_month</th>
                                                <th scope="col" class="px-4 py-3 font-medium">release_day</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieEpisodeRows as $movieEpisodeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieEpisodeRow->episode_movie_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieEpisodeRow->movie_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieEpisodeRow->season }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieEpisodeRow->episode_number }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieEpisodeRow->release_year }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieEpisodeRow->release_month }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieEpisodeRow->release_day }}
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

                @if ($countryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-countries" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Countries</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>countries</code> table and linked to this movie through its origin country records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">code</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($countryRows as $countryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ strtoupper((string) $countryRow->code) }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $countryRow->name ?? '—' }}
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

                @if ($movieBoxOfficeRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-box-office" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie box office</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_box_office</code> table and linked directly to this movie.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">movie_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">domestic_gross_amount</th>
                                                <th scope="col" class="px-4 py-3 font-medium">domestic_gross_currency_code</th>
                                                <th scope="col" class="px-4 py-3 font-medium">worldwide_gross_amount</th>
                                                <th scope="col" class="px-4 py-3 font-medium">worldwide_gross_currency_code</th>
                                                <th scope="col" class="px-4 py-3 font-medium">opening_weekend_gross_amount</th>
                                                <th scope="col" class="px-4 py-3 font-medium">opening_weekend_gross_currency_code</th>
                                                <th scope="col" class="px-4 py-3 font-medium">opening_weekend_end_year</th>
                                                <th scope="col" class="px-4 py-3 font-medium">opening_weekend_end_month</th>
                                                <th scope="col" class="px-4 py-3 font-medium">opening_weekend_end_day</th>
                                                <th scope="col" class="px-4 py-3 font-medium">production_budget_amount</th>
                                                <th scope="col" class="px-4 py-3 font-medium">production_budget_currency_code</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieBoxOfficeRows as $movieBoxOfficeRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieBoxOfficeRow->movie_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->domestic_gross_amount }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->domestic_gross_currency_code }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->worldwide_gross_amount }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->worldwide_gross_currency_code }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->opening_weekend_gross_amount }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->opening_weekend_gross_currency_code }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->opening_weekend_end_year }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->opening_weekend_end_month }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->opening_weekend_end_day }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->production_budget_amount }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieBoxOfficeRow->production_budget_currency_code }}
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

                @if ($currencyRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-currencies" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Currencies</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>currencies</code> table and linked to this movie through its box office records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">code</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($currencyRows as $currencyRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ strtoupper((string) $currencyRow->code) }}
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

                @if ($movieImageSummaryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-image-summaries" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie image summaries</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>movie_image_summaries</code> table and linked directly to this movie.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">movie_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">total_count</th>
                                                <th scope="col" class="px-4 py-3 font-medium">next_page_token</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieImageSummaryRows as $movieImageSummaryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieImageSummaryRow->movie_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieImageSummaryRow->total_count }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $movieImageSummaryRow->next_page_token }}
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

                @if ($movieGenreRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-genres" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie genres</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Genre links attached directly to this title.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Genre</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($movieGenreRows as $movieGenreRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $movieGenreRow->genre?->name ?? $movieGenreRow->genre_id }}
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

                @if ($genreRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-genres" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Genres</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>genres</code> table and linked to this movie through its movie genre records.
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
                                            @foreach ($genreRows as $genreRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $genreRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $genreRow->name }}
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

                @if ($interestCategoryRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-interest-categories" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Interest categories</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>interest_categories</code> table and linked to this movie through its imported interests.
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
                                            @foreach ($interestCategoryRows as $interestCategoryRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $interestCategoryRow->id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestCategoryRow->name }}
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

                @if ($interestPrimaryImageRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-interest-primary-images" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Interest primary images</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>interest_primary_images</code> table and linked to this movie through its imported interests.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">interest_imdb_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">url</th>
                                                <th scope="col" class="px-4 py-3 font-medium">width</th>
                                                <th scope="col" class="px-4 py-3 font-medium">height</th>
                                                <th scope="col" class="px-4 py-3 font-medium">type</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($interestPrimaryImageRows as $interestPrimaryImageRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $interestPrimaryImageRow->interest_imdb_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestPrimaryImageRow->url }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestPrimaryImageRow->width ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestPrimaryImageRow->height ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestPrimaryImageRow->type ?? '—' }}
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

                @if ($interestSimilarInterestRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-interest-similar-interests" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Interest similar interests</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>interest_similar_interests</code> table and linked to this movie through its imported interests.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">interest_imdb_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">similar_interest_imdb_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">position</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($interestSimilarInterestRows as $interestSimilarInterestRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $interestSimilarInterestRow->interest_imdb_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestSimilarInterestRow->similar_interest_imdb_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestSimilarInterestRow->position }}
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

                @if ($interestRows->isNotEmpty())
                    <x-ui.card data-slot="title-detail-interests" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Interests</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>interests</code> table and linked to this movie through its movie interest records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">imdb_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">name</th>
                                                <th scope="col" class="px-4 py-3 font-medium">description</th>
                                                <th scope="col" class="px-4 py-3 font-medium">is_subgenre</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($interestRows as $interestRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $interestRow->imdb_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestRow->name }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $interestRow->description ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ (int) $interestRow->is_subgenre }}
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
