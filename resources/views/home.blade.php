@extends('layouts.public')

@section('title', 'Home')
@section('meta_description', 'Discover new films, series, documentaries, and creators on Screenbase.')

@section('content')
    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
        <x-ui.card class="!max-w-none overflow-hidden border-black/5 bg-[linear-gradient(135deg,rgba(15,23,42,0.98),rgba(38,38,38,0.92))] text-white dark:border-white/10 dark:bg-[linear-gradient(135deg,rgba(250,250,250,0.08),rgba(38,38,38,0.95))]">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto]">
                <div class="space-y-4">
                    <x-ui.badge color="amber" icon="star">Curated discovery</x-ui.badge>
                    <x-ui.heading level="h1" size="xl" class="text-white">
                        Serious title discovery, ratings, reviews, and people pages.
                    </x-ui.heading>
                    <x-ui.text class="max-w-2xl text-base text-white/80">
                        Screenbase combines a public browse surface, rich title detail, person profiles, audience reviews, and private watchlists in one Laravel and Livewire application.
                    </x-ui.text>
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button as="a" :href="route('public.discover')" icon="sparkles">
                            Start Discovering
                        </x-ui.button>
                        <x-ui.button as="a" :href="route('public.search')" variant="outline" color="slate" icon="magnifying-glass">
                            Advanced Search
                        </x-ui.button>
                    </div>
                </div>

                <div class="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-box border border-white/10 bg-white/5 p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-white/50">Public catalog</div>
                        <div class="mt-2 text-3xl font-semibold">Titles, people, lists</div>
                    </div>
                    <div class="rounded-box border border-white/10 bg-white/5 p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-white/50">Community signals</div>
                        <div class="mt-2 text-3xl font-semibold">Ratings, reviews, watchlists</div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="space-y-3">
                <x-ui.heading level="h2" size="lg">What’s built in</x-ui.heading>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="font-medium">Discovery</div>
                        <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">Filter by type, genre, text, and audience rating.</div>
                    </div>
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="font-medium">Title pages</div>
                        <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">Poster, synopsis, credits, reviews, and interaction panels.</div>
                    </div>
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="font-medium">People pages</div>
                        <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">Filmography-oriented profiles for cast and crew.</div>
                    </div>
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="font-medium">Moderation</div>
                        <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">Review and report queues for staff operators.</div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </section>

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <x-ui.heading level="h2" size="lg">Featured titles</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Editorially surfaced releases with ratings, review counts, and genre tags.
                </x-ui.text>
            </div>

            <x-ui.button as="a" :href="route('public.titles.index')" variant="ghost" icon="arrow-right">
                Browse all
            </x-ui.button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($featuredTitles as $title)
                <x-catalog.title-card :title="$title" />
            @empty
                <div class="md:col-span-2 xl:col-span-3">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.heading level="h3">No titles have been published yet.</x-ui.heading>
                    </x-ui.empty>
                </div>
            @endforelse
        </div>
    </section>
@endsection
