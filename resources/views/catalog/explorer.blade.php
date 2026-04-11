@extends('layouts.public')

@section('title', $sectionConfig['title'])
@section('meta_description', $sectionConfig['description'])

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.catalog.explorer')">Catalog Explorer</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $sectionConfig['label'] }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-page-hero !max-w-none p-6 sm:p-7" data-slot="catalog-explorer-hero">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                <div class="space-y-4">
                    <div class="sb-page-kicker">{{ $sectionConfig['eyebrow'] }}</div>
                    <div class="space-y-3">
                        <x-ui.heading level="h1" size="xl" class="sb-page-title">Catalog Explorer</x-ui.heading>
                        <x-ui.text class="sb-page-copy max-w-3xl">
                            {{ $sectionConfig['description'] }}
                        </x-ui.text>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 xl:w-[28rem]">
                    @foreach ($sectionConfig['badges'] as $badge)
                        <div class="sb-page-stat p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-[#a89d8d]">{{ $sectionConfig['label'] }}</div>
                            <div class="mt-2 flex items-center gap-2 text-lg font-semibold text-[#f7f1e8]">
                                <x-ui.icon :name="$badge['icon']" class="size-4" />
                                <span>{{ $badge['label'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-results-shell !max-w-none rounded-[1.6rem] p-5" data-slot="catalog-explorer-sections">
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <x-ui.heading level="h2" size="lg">Explorer Sections</x-ui.heading>
                        <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                            Each section reads from the same normalized Eloquent graph while keeping filters and pagination isolated to the active Livewire surface.
                        </x-ui.text>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @foreach ($sectionConfig['actions'] as $action)
                            @if (($action['variant'] ?? 'ghost') === 'outline')
                                <x-ui.button.light-outline :href="$action['href']" :icon="$action['icon']">
                                    {{ $action['label'] }}
                                </x-ui.button.light-outline>
                            @else
                                <x-ui.button
                                    as="a"
                                    :href="$action['href']"
                                    :variant="$action['variant']"
                                    :icon="$action['icon']"
                                >
                                    {{ $action['label'] }}
                                </x-ui.button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-3 lg:grid-cols-3">
                    @foreach ($sectionNav as $sectionItem)
                        <a
                            href="{{ $sectionItem['href'] }}"
                            class="rounded-[1.25rem] border p-4 transition {{ $currentSection === $sectionItem['key'] ? 'border-amber-300/70 bg-amber-50/70 shadow-sm dark:border-amber-400/30 dark:bg-amber-500/10' : 'border-black/5 bg-white/70 hover:border-black/10 dark:border-white/10 dark:bg-white/5 dark:hover:border-white/20' }}"
                        >
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex size-10 items-center justify-center rounded-full bg-black/5 text-neutral-700 dark:bg-white/10 dark:text-neutral-100">
                                            <x-ui.icon :name="$sectionItem['icon']" class="size-5" />
                                        </span>
                                        <div>
                                            <div class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $sectionItem['label'] }}</div>
                                            <div class="text-xs uppercase tracking-[0.16em] text-neutral-500 dark:text-neutral-400">
                                                {{ $currentSection === $sectionItem['key'] ? 'Active section' : 'Switch section' }}
                                            </div>
                                        </div>
                                    </div>

                                    @if ($currentSection === $sectionItem['key'])
                                        <x-ui.badge color="amber" icon="check-circle">Active</x-ui.badge>
                                    @endif
                                </div>

                                <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                                    {{ $sectionItem['copy'] }}
                                </x-ui.text>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        @if ($currentSection === 'people')
            <livewire:catalog.people-browser />
        @elseif ($currentSection === 'themes')
            <livewire:catalog.interest-category-browser :show-all="true" :show-images="true" />
        @else
            <livewire:catalog.title-browser
                :sort="'popular'"
                :page-name="'catalog-titles'"
                :per-page="12"
                :display-mode="'catalog'"
                :show-summary="true"
                :empty-heading="'No titles match the explorer slice right now.'"
                :empty-text="'Use the directory routes or discovery search to widen the graph.'"
            />
        @endif
    </section>
@endsection
