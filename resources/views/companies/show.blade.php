@extends('layouts.public')

@section('title', ($company->name ?: 'Company').' archive')
@section('meta_description', 'Browse related company credit records, linked titles, countries, and attributes for '.($company->name ?: 'this company').'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $company->name ?: 'Company' }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-seo.pagination-links :paginator="$archiveRecords" />

        <x-ui.card data-slot="company-detail-hero" class="sb-page-hero !max-w-none p-6 sm:p-7">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.04fr)_minmax(18rem,0.96fr)] xl:items-start">
                <div class="space-y-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="sb-page-kicker">Company archive</div>
                        <x-ui.badge variant="outline" color="neutral" icon="building-office-2">
                            {{ number_format($archiveRecords->total()) }} matching records
                        </x-ui.badge>
                        <x-ui.badge variant="outline" color="{{ $hasActiveFilters ? 'amber' : 'slate' }}" icon="{{ $hasActiveFilters ? 'sparkles' : 'globe-alt' }}">
                            {{ $hasActiveFilters ? 'Filtered archive' : 'All published matches' }}
                        </x-ui.badge>
                    </div>

                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">
                            {{ $company->name ?: 'Company' }}
                        </x-ui.heading>

                        <x-ui.text class="sb-page-copy max-w-4xl text-base">
                            Published titles connected to this company through imported company credit records, with category, territory, and attribute details kept on the page.
                        </x-ui.text>
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

        <x-ui.card data-slot="company-detail-filters" class="sb-detail-section !max-w-none">
            <form method="GET" action="{{ route('public.companies.show', $company) }}" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_12rem_14rem_14rem_auto] xl:items-end">
                <x-ui.field>
                    <x-ui.label>Search titles</x-ui.label>
                    <x-ui.input
                        name="q"
                        :value="$filters['q']"
                        placeholder="Search by title or IMDb id"
                        left-icon="magnifying-glass"
                    />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Type</x-ui.label>
                    <select
                        name="type"
                        class="w-full rounded-[1rem] border border-black/10 bg-white px-4 py-3 text-sm text-neutral-900 outline-none transition focus:border-neutral-400 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white/20"
                    >
                        <option value="">All types</option>
                        @foreach ($typeOptions as $typeOption)
                            <option value="{{ $typeOption['value'] }}" @selected($filters['type'] === $typeOption['value'])>
                                {{ $typeOption['label'] }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Category</x-ui.label>
                    <select
                        name="category"
                        class="w-full rounded-[1rem] border border-black/10 bg-white px-4 py-3 text-sm text-neutral-900 outline-none transition focus:border-neutral-400 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white/20"
                    >
                        <option value="">All categories</option>
                        @foreach ($categoryOptions as $categoryOption)
                            <option value="{{ $categoryOption['value'] }}" @selected($filters['category'] === $categoryOption['value'])>
                                {{ $categoryOption['label'] }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Country</x-ui.label>
                    <select
                        name="country"
                        class="w-full rounded-[1rem] border border-black/10 bg-white px-4 py-3 text-sm text-neutral-900 outline-none transition focus:border-neutral-400 dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-white/20"
                    >
                        <option value="">All countries</option>
                        @foreach ($countryOptions as $countryOption)
                            <option value="{{ $countryOption['value'] }}" @selected($filters['country'] === $countryOption['value'])>
                                {{ $countryOption['label'] }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <div class="flex flex-wrap gap-2">
                    <x-ui.button type="submit" icon="magnifying-glass">
                        Filter
                    </x-ui.button>

                    @if ($hasActiveFilters)
                        <x-ui.button as="a" :href="route('public.companies.show', $company)" variant="ghost" icon="x-mark">
                            Clear
                        </x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <x-ui.card data-slot="company-detail-records" class="sb-detail-section !max-w-none">
            <div class="space-y-4">
                <div>
                    <x-ui.heading level="h2" size="lg">Related company records</x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Published titles that currently expose this company in the imported company-credit archive.
                    </x-ui.text>
                </div>

                <div class="space-y-3">
                    @forelse ($archiveRecords as $archiveRecord)
                        <div class="rounded-[1.2rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                            <div class="grid gap-4 md:grid-cols-[4.8rem_minmax(0,1fr)_auto] md:items-start">
                                <a href="{{ $archiveRecord['titleHref'] }}" class="group overflow-hidden rounded-[1rem] border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                    @if ($archiveRecord['posterUrl'])
                                        <img
                                            src="{{ $archiveRecord['posterUrl'] }}"
                                            alt="{{ $archiveRecord['posterAlt'] ?: $archiveRecord['titleLabel'] }}"
                                            class="aspect-[2/3] w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="film" class="size-8" />
                                        </div>
                                    @endif
                                </a>

                                <div class="min-w-0 space-y-3">
                                    <div class="space-y-2">
                                        <a href="{{ $archiveRecord['titleHref'] }}" class="block text-lg font-semibold text-neutral-900 transition hover:opacity-80 dark:text-neutral-100">
                                            {{ $archiveRecord['titleLabel'] }}
                                        </a>

                                        <div class="flex flex-wrap gap-2 text-sm text-neutral-600 dark:text-neutral-300">
                                            @if ($archiveRecord['titleMeta'])
                                                <span>{{ $archiveRecord['titleMeta'] }}</span>
                                            @endif

                                            @if ($archiveRecord['categoryLabel'])
                                                <x-ui.badge variant="outline" color="slate" icon="building-office-2">
                                                    {{ $archiveRecord['categoryLabel'] }}
                                                </x-ui.badge>
                                            @endif

                                            @if ($archiveRecord['activeYearsLabel'])
                                                <x-ui.badge variant="outline" color="neutral" icon="calendar-days">
                                                    {{ $archiveRecord['activeYearsLabel'] }}
                                                </x-ui.badge>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Countries</div>
                                        <div class="flex flex-wrap gap-2">
                                            @forelse ($archiveRecord['countryBadges'] as $countryBadge)
                                                <span class="inline-flex items-center gap-2 rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium dark:border-white/10">
                                                    <x-ui.flag type="country" :code="$countryBadge['code']" class="size-3.5" />
                                                    <span>{{ $countryBadge['label'] }}</span>
                                                </span>
                                            @empty
                                                <span class="text-sm text-neutral-500 dark:text-neutral-400">No country rows recorded.</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Attributes</div>
                                        <div class="flex flex-wrap gap-2">
                                            @forelse ($archiveRecord['attributeBadges'] as $attributeBadge)
                                                <span class="inline-flex items-center rounded-full border border-black/8 px-2.5 py-1 text-xs font-medium text-neutral-700 dark:border-white/10 dark:text-neutral-200">
                                                    {{ $attributeBadge['label'] }}
                                                </span>
                                            @empty
                                                <span class="text-sm text-neutral-500 dark:text-neutral-400">No attribute rows recorded.</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-start md:justify-end">
                                    <x-ui.button as="a" :href="$archiveRecord['titleHref']" variant="outline" icon="film">
                                        View title
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.2rem] border border-dashed border-black/10 bg-white/50 p-6 text-sm text-neutral-600 dark:border-white/10 dark:bg-white/[0.02] dark:text-neutral-300">
                            No published titles match the current company filters.
                        </div>
                    @endforelse
                </div>

                @if ($archiveRecords->hasPages())
                    <div class="pt-2">
                        {{ $archiveRecords->links() }}
                    </div>
                @endif
            </div>
        </x-ui.card>
    </section>
@endsection
