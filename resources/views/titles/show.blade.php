@extends('layouts.public')

@section('title', $title->seoTitle())
@section('meta_description', $title->seoDescription())

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

                            @if ($genreRows->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($genreRows as $genre)
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
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-ui.button as="a" :href="$primaryVideo->url" icon="play" color="amber">
                                        Open video
                                    </x-ui.button>

                                    @if ($primaryVideo->caption)
                                        <x-ui.badge variant="outline" color="slate" icon="play">
                                            {{ $primaryVideo->caption }}
                                        </x-ui.badge>
                                    @endif
                                </div>
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

                @if ($hasCatalogInternals)
                    <details
                        data-slot="title-detail-catalog-internals"
                        class="group rounded-[1.35rem] border border-black/5 bg-white/60 p-5 shadow-sm dark:border-white/10 dark:bg-white/[0.02]"
                    >
                        <summary class="flex cursor-pointer list-none items-start justify-between gap-4 [&::-webkit-details-marker]:hidden">
                            <div class="space-y-2">
                                <div class="sb-page-kicker">Catalog Internals</div>
                                <x-ui.heading level="h2" size="lg">Imported source tables and linkage records</x-ui.heading>
                                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                                    These sections are useful for catalog QA, but they stay collapsed by default so the public title page remains readable.
                                </x-ui.text>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                                    {{ number_format($catalogInternalSectionCount) }} sections
                                </x-ui.badge>
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-black/5 bg-white/80 transition group-open:rotate-180 dark:border-white/10 dark:bg-white/[0.04]">
                                    <x-ui.icon name="chevron-down" class="size-4" />
                                </span>
                            </div>
                        </summary>

                        <div class="mt-5 space-y-6 border-t border-black/5 pt-5 dark:border-white/10">
                @endif

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

                @if ($akaAttributeEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-aka-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">AKA attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Alternate-title attributes linked to this movie through its imported AKA records.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Attribute</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Meaning</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Used on this movie</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Archive</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($akaAttributeEntries as $akaAttributeEntry)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        <div class="flex items-center gap-2">
                                                            <x-catalog.aka-attribute-chip
                                                                :href="$akaAttributeEntry['href']"
                                                                :label="$akaAttributeEntry['label']"
                                                                active
                                                            />
                                                            <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                                                {{ number_format($akaAttributeEntry['linkedAkaCount']) }} record{{ $akaAttributeEntry['linkedAkaCount'] === 1 ? '' : 's' }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $akaAttributeEntry['description'] }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach ($akaAttributeEntry['linkedAkas'] as $linkedAka)
                                                                <span class="inline-flex items-center gap-2 rounded-full border border-black/8 bg-white/70 px-2.5 py-1 text-xs font-medium dark:border-white/10 dark:bg-white/[0.03]">
                                                                    <span>{{ $linkedAka['text'] }}</span>
                                                                    @if ($linkedAka['meta'])
                                                                        <span class="text-[11px] uppercase tracking-[0.14em] text-neutral-500 dark:text-neutral-400">
                                                                            {{ $linkedAka['meta'] }}
                                                                        </span>
                                                                    @endif
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        <x-ui.link :href="$akaAttributeEntry['href']" variant="ghost" icon="queue-list" :primary="false">
                                                            Open archive
                                                        </x-ui.link>
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

                @if ($akaTypeEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-aka-types" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">AKA types</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Imported alternate-title classifications linked to this title through its published AKA rows.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">Type</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Meaning</th>
                                                <th scope="col" class="px-4 py-3 font-medium">Used on this title</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($akaTypeEntries as $akaTypeEntry)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        <div>{{ $akaTypeEntry['label'] }}</div>
                                                        <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                            {{ number_format($akaTypeEntry['linkedAkaCount']) }} linked AKA {{ \Illuminate\Support\Str::plural('record', $akaTypeEntry['linkedAkaCount']) }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $akaTypeEntry['description'] }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach ($akaTypeEntry['linkedAkas'] as $linkedAka)
                                                                <span class="inline-flex items-center gap-2 rounded-full border border-black/8 px-3 py-1 text-xs font-medium dark:border-white/10">
                                                                    <span>{{ $linkedAka['text'] }}</span>

                                                                    @if ($linkedAka['meta'])
                                                                        <span class="text-[11px] uppercase tracking-[0.14em] text-neutral-500 dark:text-neutral-400">
                                                                            {{ $linkedAka['meta'] }}
                                                                        </span>
                                                                    @endif
                                                                </span>
                                                            @endforeach
                                                        </div>
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

                @if ($awardNomineeEntries->isNotEmpty())
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
                                            @foreach ($awardNomineeEntries as $awardNomineeEntry)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        @if ($awardNomineeEntry['awardHref'])
                                                            <a
                                                                href="{{ $awardNomineeEntry['awardHref'] }}"
                                                                class="block rounded-[1rem] transition hover:opacity-80"
                                                            >
                                                                <div class="font-medium text-neutral-900 dark:text-neutral-100">
                                                                    {{ $awardNomineeEntry['awardLabel'] }}
                                                                </div>
                                                                @if ($awardNomineeEntry['awardMeta'])
                                                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                                        {{ $awardNomineeEntry['awardMeta'] }}
                                                                    </div>
                                                                @endif
                                                            </a>
                                                        @else
                                                            <div class="font-medium text-neutral-900 dark:text-neutral-100">Award nomination</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        @if ($awardNomineeEntry['nomineeHref'] && $awardNomineeEntry['nomineeName'])
                                                            <a
                                                                href="{{ $awardNomineeEntry['nomineeHref'] }}"
                                                                data-slot="title-detail-award-nominee-link"
                                                                class="flex items-center gap-3 rounded-[1rem] transition hover:opacity-80"
                                                            >
                                                                <div
                                                                    data-slot="title-detail-award-nominee-avatar"
                                                                    class="flex h-12 w-10 shrink-0 items-center justify-center overflow-hidden rounded-[0.8rem] border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-white/[0.04]"
                                                                >
                                                                    @if ($awardNomineeEntry['nomineeHeadshotUrl'])
                                                                        <img
                                                                            src="{{ $awardNomineeEntry['nomineeHeadshotUrl'] }}"
                                                                            alt="{{ $awardNomineeEntry['nomineeHeadshotAlt'] ?: $awardNomineeEntry['nomineeName'] }}"
                                                                            class="h-full w-full object-cover"
                                                                            loading="lazy"
                                                                        >
                                                                    @else
                                                                        <x-ui.icon name="user" class="size-4 text-neutral-400 dark:text-neutral-500" />
                                                                    @endif
                                                                </div>

                                                                <span class="min-w-0 truncate font-medium text-neutral-900 dark:text-neutral-100">
                                                                    {{ $awardNomineeEntry['nomineeName'] }}
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

                @php($awardNominationTitleEntries = $awardNominationTitleEntries ?? collect())
                @if ($awardNominationTitleEntries->isNotEmpty())
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
                                            @foreach ($awardNominationTitleEntries as $awardNominationTitleEntry)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $awardNominationTitleEntry['awardLabel'] }}
                                                        @if (filled($awardNominationTitleEntry['awardMeta']))
                                                            <div class="mt-1 text-xs font-normal text-neutral-500 dark:text-neutral-400">
                                                                {{ $awardNominationTitleEntry['awardMeta'] }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $awardNominationTitleEntry['titleLabel'] }}
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

                @if ($certificateRatingEntries->isNotEmpty() || $certificateTitleEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-certificate-ratings" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Certificate ratings</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Certificate ratings linked to this title through its certificate records.
                                </x-ui.text>
                            </div>

                            <x-ui.tabs
                                class="block"
                                variant="outlined"
                                :active-tab="$certificateRatingEntries->isNotEmpty() ? 'rating' : 'title'"
                                data-slot="title-detail-certificate-rating-tabs"
                            >
                                <x-ui.tab.group class="justify-start gap-2">
                                    @if ($certificateRatingEntries->isNotEmpty())
                                        <x-ui.tab name="rating" class="justify-between gap-3">
                                            <span>By rating</span>
                                            <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-black/5 px-2 py-0.5 text-[0.72rem] font-semibold text-neutral-600 dark:bg-white/10 dark:text-neutral-200">
                                                {{ number_format($certificateRatingEntries->count()) }}
                                            </span>
                                        </x-ui.tab>
                                    @endif

                                    @if ($certificateTitleEntries->isNotEmpty())
                                        <x-ui.tab name="title" class="justify-between gap-3">
                                            <span>By title</span>
                                            <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-black/5 px-2 py-0.5 text-[0.72rem] font-semibold text-neutral-600 dark:bg-white/10 dark:text-neutral-200">
                                                {{ number_format($certificateTitleEntries->count()) }}
                                            </span>
                                        </x-ui.tab>
                                    @endif
                                </x-ui.tab.group>

                                @if ($certificateRatingEntries->isNotEmpty())
                                    <x-ui.tab.panel name="rating" class="!border-0 !bg-transparent !p-0">
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
                                    </x-ui.tab.panel>
                                @endif

                                @if ($certificateTitleEntries->isNotEmpty())
                                    <x-ui.tab.panel name="title" class="!border-0 !bg-transparent !p-0">
                                        <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                                    <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                                        <tr>
                                                            <th scope="col" class="px-4 py-3 font-medium">Rating</th>
                                                            <th scope="col" class="px-4 py-3 font-medium">Meaning</th>
                                                            <th scope="col" class="px-4 py-3 font-medium">Country</th>
                                                            <th scope="col" class="px-4 py-3 font-medium">Attributes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                                        @foreach ($certificateTitleEntries as $certificateTitleEntry)
                                                            <tr>
                                                                <td class="px-4 py-3 align-top text-neutral-900 dark:text-neutral-100">
                                                                    @if ($certificateTitleEntry['rating'])
                                                                        <x-catalog.certificate-rating-chip :rating="$certificateTitleEntry['rating']" />
                                                                    @else
                                                                        <span class="text-neutral-500 dark:text-neutral-400">—</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                                    {{ $certificateTitleEntry['meaning'] }}
                                                                </td>
                                                                <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                                    @if ($certificateTitleEntry['country'])
                                                                        <div class="flex items-center gap-2">
                                                                            <x-ui.flag type="country" :code="$certificateTitleEntry['country']['code']" class="size-4" />
                                                                            <span>{{ $certificateTitleEntry['country']['label'] }}</span>
                                                                        </div>
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                                    <div class="flex flex-wrap gap-2">
                                                                        @forelse ($certificateTitleEntry['attributes'] as $certificateAttribute)
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
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </x-ui.tab.panel>
                                @endif
                            </x-ui.tabs>
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

                @if ($directorEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-directors" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie directors</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Directors linked directly to this title.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($directorEntries as $directorEntry)
                                    <div class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="flex items-start gap-4">
                                            @if ($directorEntry['profileHref'])
                                                <a href="{{ $directorEntry['profileHref'] }}" class="shrink-0" data-slot="title-detail-director-avatar-link">
                                                    <x-ui.avatar
                                                        :src="$directorEntry['headshotUrl']"
                                                        :alt="$directorEntry['headshotAlt'] ?: $directorEntry['name']"
                                                        :name="$directorEntry['name']"
                                                        color="auto"
                                                        class="!h-20 !w-16 rounded-[1rem] border border-black/5 dark:border-white/10"
                                                    />
                                                </a>
                                            @else
                                                <x-ui.avatar
                                                    :src="$directorEntry['headshotUrl']"
                                                    :alt="$directorEntry['headshotAlt'] ?: $directorEntry['name']"
                                                    :name="$directorEntry['name']"
                                                    color="auto"
                                                    class="!h-20 !w-16 shrink-0 rounded-[1rem] border border-black/5 dark:border-white/10"
                                                />
                                            @endif

                                            <div class="min-w-0 flex-1 space-y-3">
                                                <div class="space-y-2">
                                                    @if ($directorEntry['profileHref'])
                                                        <a
                                                            href="{{ $directorEntry['profileHref'] }}"
                                                            data-slot="title-detail-director-link"
                                                            class="block text-lg font-semibold text-neutral-900 transition hover:opacity-80 dark:text-neutral-100"
                                                        >
                                                            {{ $directorEntry['name'] }}
                                                        </a>
                                                    @else
                                                        <div class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                                            {{ $directorEntry['name'] }}
                                                        </div>
                                                    @endif

                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($directorEntry['professionLabels'] as $directorProfessionLabel)
                                                            <x-ui.badge variant="outline" color="slate" icon="briefcase">
                                                                {{ $directorProfessionLabel }}
                                                            </x-ui.badge>
                                                        @endforeach

                                                        @if ($directorEntry['nationality'])
                                                            <x-ui.badge variant="outline" color="neutral" icon="globe-alt">
                                                                {{ $directorEntry['nationality'] }}
                                                            </x-ui.badge>
                                                        @endif

                                                        @if ($directorEntry['creditsBadgeLabel'])
                                                            <x-ui.badge variant="outline" color="neutral" icon="film">
                                                                {{ $directorEntry['creditsBadgeLabel'] }}
                                                            </x-ui.badge>
                                                        @endif
                                                    </div>

                                                    @if ($directorEntry['summary'])
                                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                            {{ str($directorEntry['summary'])->limit(180) }}
                                                        </x-ui.text>
                                                    @endif
                                                </div>

                                                <div class="flex flex-wrap gap-2">
                                                    @if ($directorEntry['profileHref'])
                                                        <x-ui.button as="a" :href="$directorEntry['profileHref']" variant="outline" size="sm" icon="user">
                                                            View person
                                                        </x-ui.button>
                                                    @endif

                                                    @if ($directorEntry['archiveHref'])
                                                        <x-ui.button as="a" :href="$directorEntry['archiveHref']" size="sm" icon="film">
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

                @if ($movieCompanyCreditAttributeEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-movie-company-credit-attributes" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Movie company credit attributes</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Company credit attributes linked to this title's company records, with direct paths into the related company and attribute archives.
                                </x-ui.text>
                            </div>

                            <div class="grid auto-rows-fr gap-3 sm:grid-cols-2">
                                @foreach ($movieCompanyCreditAttributeEntries as $movieCompanyCreditAttributeEntry)
                                    <x-ui.card class="!max-w-none h-full rounded-[1.35rem]">
                                        <div class="flex h-full flex-col gap-4">
                                            <div class="flex items-start gap-3">
                                                <div class="flex size-11 shrink-0 items-center justify-center rounded-[1rem] border border-black/5 bg-neutral-100 text-neutral-600 dark:border-white/10 dark:bg-neutral-800 dark:text-neutral-300">
                                                    <x-ui.icon name="building-office-2" class="size-5" />
                                                </div>

                                                <div class="min-w-0 flex-1 space-y-3">
                                                    <div class="flex flex-wrap gap-2">
                                                        @if ($movieCompanyCreditAttributeEntry['companyHref'] && $movieCompanyCreditAttributeEntry['companyLabel'])
                                                            <a href="{{ $movieCompanyCreditAttributeEntry['companyHref'] }}">
                                                                <x-ui.badge variant="outline" color="neutral" icon="building-office-2">
                                                                    {{ $movieCompanyCreditAttributeEntry['companyLabel'] }}
                                                                </x-ui.badge>
                                                            </a>
                                                        @elseif ($movieCompanyCreditAttributeEntry['companyLabel'])
                                                            <x-ui.badge variant="outline" color="neutral" icon="building-office-2">
                                                                {{ $movieCompanyCreditAttributeEntry['companyLabel'] }}
                                                            </x-ui.badge>
                                                        @endif

                                                        @if ($movieCompanyCreditAttributeEntry['categoryHref'] && $movieCompanyCreditAttributeEntry['categoryLabel'])
                                                            <a href="{{ $movieCompanyCreditAttributeEntry['categoryHref'] }}">
                                                                <x-ui.badge variant="outline" color="slate" icon="tag">
                                                                    {{ $movieCompanyCreditAttributeEntry['categoryLabel'] }}
                                                                </x-ui.badge>
                                                            </a>
                                                        @elseif ($movieCompanyCreditAttributeEntry['categoryLabel'])
                                                            <x-ui.badge variant="outline" color="slate" icon="tag">
                                                                {{ $movieCompanyCreditAttributeEntry['categoryLabel'] }}
                                                            </x-ui.badge>
                                                        @endif

                                                        @if ($movieCompanyCreditAttributeEntry['activeYearsLabel'])
                                                            <x-ui.badge variant="outline" color="amber" icon="calendar-days">
                                                                {{ $movieCompanyCreditAttributeEntry['activeYearsLabel'] }}
                                                            </x-ui.badge>
                                                        @endif
                                                    </div>

                                                    <div class="space-y-2">
                                                        <a
                                                            href="{{ $movieCompanyCreditAttributeEntry['attributeHref'] }}"
                                                            class="block text-lg font-semibold text-neutral-900 transition hover:opacity-80 dark:text-neutral-100"
                                                        >
                                                            {{ $movieCompanyCreditAttributeEntry['attributeLabel'] }}
                                                        </a>

                                                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                            Attribute archive for company records matching this title, with links back into other related titles and companies.
                                                        </x-ui.text>
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($movieCompanyCreditAttributeEntry['countryBadges']->isNotEmpty())
                                                <div class="space-y-2">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Countries</div>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($movieCompanyCreditAttributeEntry['countryBadges'] as $countryBadge)
                                                            <span class="inline-flex items-center gap-2 rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                                <x-ui.flag type="country" :code="$countryBadge['code']" class="size-3.5" />
                                                                <span>{{ $countryBadge['label'] }}</span>
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="mt-auto flex flex-wrap justify-end gap-2">
                                                @if ($movieCompanyCreditAttributeEntry['companyHref'])
                                                    <x-ui.button.light-outline :href="$movieCompanyCreditAttributeEntry['companyHref']" size="sm" icon="building-office-2">
                                                        Open company
                                                    </x-ui.button.light-outline>
                                                @endif

                                                <x-ui.button.light-outline :href="$movieCompanyCreditAttributeEntry['attributeHref']" size="sm" iconAfter="arrow-right">
                                                    Open attribute archive
                                                </x-ui.button.light-outline>
                                            </div>
                                        </div>
                                    </x-ui.card>
                                @endforeach
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

                @if ($genreEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-genres" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Genres</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Genre lanes attached to this title. Open any genre to browse more titles in the existing archive.
                                </x-ui.text>
                            </div>

                            <div class="grid auto-rows-fr gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @forelse ($genreEntries as $genreEntry)
                                    <x-ui.card class="!max-w-none h-full overflow-hidden rounded-[1.4rem] !p-0">
                                        <div class="flex h-full flex-col">
                                            <a
                                                href="{{ $genreEntry['href'] }}"
                                                class="group block overflow-hidden border-b border-black/5 dark:border-white/10"
                                            >
                                                @if ($genreEntry['previewUrl'])
                                                    <img
                                                        src="{{ $genreEntry['previewUrl'] }}"
                                                        alt="{{ $genreEntry['previewAlt'] ?? $genreEntry['genre']->name }}"
                                                        class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <div class="flex aspect-[16/9] items-center justify-center bg-white/[0.04] text-[#8f877a]">
                                                        <x-ui.icon name="tag" class="size-10" />
                                                    </div>
                                                @endif
                                            </a>

                                            <div class="flex flex-1 flex-col gap-4 p-4">
                                                <div class="flex flex-wrap gap-2">
                                                    <x-ui.badge variant="outline" color="neutral" icon="tag">
                                                        {{ $genreEntry['genre']->name }}
                                                    </x-ui.badge>

                                                    @if ($genreEntry['titleCountLabel'])
                                                        <x-ui.badge variant="outline" color="slate" icon="film">
                                                            {{ $genreEntry['titleCountLabel'] }}
                                                        </x-ui.badge>
                                                    @endif
                                                </div>

                                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                    {{ $genreEntry['description'] }}
                                                </x-ui.text>

                                                <div class="mt-auto flex justify-end">
                                                    <x-ui.button.light-outline :href="$genreEntry['href']" size="sm" iconAfter="arrow-right">
                                                        Open genre
                                                    </x-ui.button.light-outline>
                                                </div>
                                            </div>
                                        </div>
                                    </x-ui.card>
                                @empty
                                @endforelse
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($interestCategoryEntries->isNotEmpty())
                    <x-ui.card data-slot="title-detail-interest-categories" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Interest categories</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Discovery themes connected to this title through its imported interest graph.
                                </x-ui.text>
                            </div>

                            <div class="grid auto-rows-fr gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @forelse ($interestCategoryEntries as $interestCategoryEntry)
                                    <x-catalog.interest-category-card
                                        :interest-category="$interestCategoryEntry['interestCategory']"
                                        show-image
                                    >
                                        <x-ui.badge variant="outline" color="amber" icon="sparkles">
                                            {{ $interestCategoryEntry['matchedInterestCountLabel'] }}
                                        </x-ui.badge>

                                        @foreach ($interestCategoryEntry['matchedInterests'] as $matchedInterest)
                                            <a href="{{ $matchedInterest['href'] }}">
                                                <x-ui.badge
                                                    variant="outline"
                                                    :color="$matchedInterest['isSubgenre'] ? 'slate' : 'neutral'"
                                                    :icon="$matchedInterest['isSubgenre'] ? 'tag' : 'sparkles'"
                                                >
                                                    {{ $matchedInterest['name'] }}
                                                </x-ui.badge>
                                            </a>
                                        @endforeach
                                    </x-catalog.interest-category-card>
                                @empty
                                @endforelse
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

                @if ($hasCatalogInternals)
                        </div>
                    </details>
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

                @if ($featuredCastEntries->isNotEmpty())
                    <x-ui.card class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Featured cast</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Principal cast and on-screen talent linked from the imported credits table.
                                </x-ui.text>
                            </div>

                            <div class="grid auto-rows-fr gap-3 sm:grid-cols-2">
                                @foreach ($featuredCastEntries as $featuredCastEntry)
                                    <article
                                        class="h-full rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]"
                                        data-slot="featured-cast-card"
                                    >
                                        <div class="flex h-full flex-col gap-4">
                                            <div class="flex items-start gap-3">
                                                <x-ui.avatar
                                                    as="a"
                                                    :href="$featuredCastEntry['profileHref']"
                                                    :src="$featuredCastEntry['headshotUrl']"
                                                    :alt="$featuredCastEntry['headshotAlt'] ?: $featuredCastEntry['name']"
                                                    :name="$featuredCastEntry['name']"
                                                    color="auto"
                                                    class="!h-14 !w-14 shrink-0 border border-black/5 dark:border-white/10"
                                                />

                                                <div class="flex min-w-0 flex-1 flex-col gap-3">
                                                    <div class="space-y-2">
                                                        <div>
                                                            <a
                                                                href="{{ $featuredCastEntry['profileHref'] }}"
                                                                class="block truncate font-medium text-neutral-900 transition hover:opacity-80 dark:text-neutral-100"
                                                            >
                                                                {{ $featuredCastEntry['name'] }}
                                                            </a>
                                                            <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                                {{ $featuredCastEntry['roleLabel'] }}
                                                            </div>
                                                        </div>

                                                        <div class="flex flex-wrap gap-2">
                                                            @if ($featuredCastEntry['nationality'])
                                                                <x-ui.badge variant="outline" color="neutral" icon="globe-alt">
                                                                    {{ $featuredCastEntry['nationality'] }}
                                                                </x-ui.badge>
                                                            @endif

                                                            @if ($featuredCastEntry['creditsBadgeLabel'])
                                                                <x-ui.badge variant="outline" color="slate" icon="film">
                                                                    {{ $featuredCastEntry['creditsBadgeLabel'] }}
                                                                </x-ui.badge>
                                                            @endif
                                                        </div>

                                                        @if ($featuredCastEntry['summary'])
                                                            <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                                                {{ str($featuredCastEntry['summary'])->limit(110) }}
                                                            </x-ui.text>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-auto flex justify-start">
                                                <x-ui.button as="a" :href="$featuredCastEntry['profileHref']" variant="outline" size="sm" icon="user">
                                                    View person
                                                </x-ui.button>
                                            </div>
                                        </div>
                                    </article>
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

                                <div class="grid auto-rows-fr gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($seasonNavigation as $navigationSeason)
                                        <a
                                            href="{{ route('public.seasons.show', ['series' => $title, 'season' => $navigationSeason]) }}"
                                            class="flex h-full min-h-[7.5rem] flex-col justify-between rounded-[1.15rem] border border-black/5 bg-white/70 p-4 transition hover:bg-white dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.05]"
                                            data-slot="series-guide-season-card"
                                        >
                                            <div>
                                                <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $navigationSeason->name }}</div>
                                                <div class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ number_format((int) $navigationSeason->episodes_count) }} episodes
                                                </div>
                                            </div>

                                            <div class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                                <span>Open season</span>
                                                <x-ui.icon name="arrow-right" class="size-4" />
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
