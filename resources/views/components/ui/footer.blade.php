@php
    $currentYear = now()->year;

    $exploreLinks = [
        ['label' => 'Home', 'href' => route('public.home'), 'icon' => 'home'],
        ['label' => 'Discovery', 'href' => route('public.discover'), 'icon' => 'sparkles'],
        ['label' => 'Browse Titles', 'href' => route('public.titles.index'), 'icon' => 'film'],
        ['label' => 'People', 'href' => route('public.people.index'), 'icon' => 'users'],
        ['label' => 'Search', 'href' => route('public.search'), 'icon' => 'magnifying-glass'],
    ];

    if (\Illuminate\Support\Facades\Route::has('public.movies.index')) {
        array_splice($exploreLinks, 3, 0, [[
            'label' => 'Movies',
            'href' => route('public.movies.index'),
            'icon' => 'film',
        ]]);
    }

    if (\Illuminate\Support\Facades\Route::has('public.series.index')) {
        array_splice($exploreLinks, 4, 0, [[
            'label' => 'TV Shows',
            'href' => route('public.series.index'),
            'icon' => 'tv',
        ]]);
    }

    if (\Illuminate\Support\Facades\Route::has('public.lists.index')) {
        array_splice($exploreLinks, 6, 0, [[
            'label' => 'Public Lists',
            'href' => route('public.lists.index'),
            'icon' => 'queue-list',
        ]]);
    }

    $signalLinks = [];

    if (\Illuminate\Support\Facades\Route::has('public.trending')) {
        $signalLinks[] = ['label' => 'Trending', 'href' => route('public.trending'), 'icon' => 'fire'];
    }

    if (\Illuminate\Support\Facades\Route::has('public.trailers.latest')) {
        $signalLinks[] = ['label' => 'Latest Trailers', 'href' => route('public.trailers.latest'), 'icon' => 'play'];
    }

    if (\Illuminate\Support\Facades\Route::has('public.reviews.latest')) {
        $signalLinks[] = ['label' => 'Latest Reviews', 'href' => route('public.reviews.latest'), 'icon' => 'chat-bubble-left-right'];
    }

    $signalLinks[] = ['label' => 'Browse by Genre', 'href' => route('public.discover'), 'icon' => 'tag'];
    $signalLinks[] = ['label' => 'Browse by Year', 'href' => route('public.titles.index'), 'icon' => 'calendar-days'];

    $accountLinks = auth()->check()
        ? [
            ['label' => 'Watchlist', 'href' => route('account.watchlist'), 'icon' => 'bookmark'],
            ['label' => 'Custom Lists', 'href' => route('account.lists.index'), 'icon' => 'queue-list'],
            ['label' => 'Public Profile', 'href' => route('public.users.show', auth()->user()), 'icon' => 'user'],
        ]
        : [
            ['label' => 'Sign In', 'href' => route('login'), 'icon' => 'arrow-right-end-on-rectangle'],
            ['label' => 'Create Account', 'href' => route('register'), 'icon' => 'user-plus'],
            ['label' => 'Discover First', 'href' => route('public.discover'), 'icon' => 'sparkles'],
        ];

    $footerSections = [
        ['heading' => 'Explore', 'links' => $exploreLinks],
        ['heading' => 'Live Routes', 'links' => $signalLinks],
        ['heading' => auth()->check() ? 'Your Space' : 'Get Started', 'links' => $accountLinks],
    ];
@endphp

<footer {{ $attributes->class('mt-10 w-full border-t border-white/8 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.12),transparent_20%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.12),transparent_24%),linear-gradient(180deg,rgba(18,18,18,0.98),rgba(6,6,6,1))]') }} data-slot="site-footer">
    <div class="mx-auto w-full max-w-7xl px-4 py-8 md:px-6 md:py-10">
        <div class="grid gap-8 xl:grid-cols-[minmax(0,1.2fr)_repeat(3,minmax(0,0.75fr))]">
            <div class="space-y-4">
                <x-ui.brand
                    :href="route('public.home')"
                    name="Screenbase"
                    class="justify-start text-left"
                />

                <x-ui.text class="max-w-md text-sm leading-7 text-neutral-300">
                    Discover titles, people, reviews, and curated lists from one public catalog built for browsing, rating, and community writing.
                </x-ui.text>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" color="neutral" icon="sparkles">Discovery</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral" icon="chat-bubble-left-right">Reviews</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral" icon="queue-list">Lists</x-ui.badge>
                </div>
            </div>

            @foreach ($footerSections as $section)
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">
                        <x-ui.icon name="chevron-right" class="size-4" />
                        <span>{{ $section['heading'] }}</span>
                    </div>

                    <div class="grid gap-2">
                        @foreach ($section['links'] as $link)
                            <x-ui.link
                                :href="$link['href']"
                                variant="soft"
                                :primary="false"
                                :icon="$link['icon']"
                                class="text-sm"
                            >
                                {{ $link['label'] }}
                            </x-ui.link>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex flex-col gap-3 border-t border-white/8 pt-4 text-sm text-neutral-400 md:flex-row md:items-center md:justify-between">
            <x-ui.text class="text-sm text-neutral-400">
                © {{ $currentYear }} Screenbase. Built for title discovery, public curation, and IMDb-style browsing.
            </x-ui.text>

            <div class="flex flex-wrap gap-3">
                <x-ui.badge variant="outline" color="neutral" icon="code-bracket">Laravel 12</x-ui.badge>
                <x-ui.badge variant="outline" color="neutral" icon="bolt">Livewire 4</x-ui.badge>
            </div>
        </div>
    </div>
</footer>
