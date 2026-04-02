<x-ui.navbar.item
    :href="route('public.home')"
    label="Home"
    icon="home"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.home')"
/>
<x-ui.navbar.item
    :href="route('public.discover')"
    label="Discovery"
    icon="sparkles"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.discover')"
/>
<x-ui.navbar.item
    :href="route('public.titles.index')"
    label="All Titles"
    icon="film"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.titles.*') || request()->routeIs('public.genres.*') || request()->routeIs('public.years.*')"
/>

@if ($hasPublicMoviesRoute)
    <x-ui.navbar.item
        :href="route('public.movies.index')"
        label="Movies"
        icon="film"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('public.movies.*') || request()->routeIs('public.rankings.movies')"
    />
@endif

@if ($hasPublicSeriesRoute)
    <x-ui.navbar.item
        :href="route('public.series.index')"
        label="TV Shows"
        icon="tv"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('public.series.*') || request()->routeIs('public.seasons.*') || request()->routeIs('public.episodes.*') || request()->routeIs('public.rankings.series')"
    />
@endif

<x-ui.navbar.item
    :href="route('public.people.index')"
    label="People"
    icon="users"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.people.*')"
/>

@if ($hasPublicListsRoute)
    <x-ui.navbar.item
        :href="route('public.lists.index')"
        label="Lists"
        icon="queue-list"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('public.lists.*')"
    />
@endif

@if ($hasPublicAwardsRoute)
    <x-ui.navbar.item
        :href="route('public.awards.index')"
        label="Awards"
        icon="trophy"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('public.awards.*')"
    />
@endif

@if ($hasPublicTrendingRoute)
    <x-ui.navbar.item
        :href="route('public.trending')"
        label="Charts"
        icon="bolt"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('public.trending') || request()->routeIs('public.rankings.*')"
    />
@endif

@if ($hasPublicLatestTrailersRoute)
    <x-ui.navbar.item
        :href="route('public.trailers.latest')"
        label="Latest Trailers"
        icon="play"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('public.trailers.*')"
    />
@endif

@if ($hasPublicLatestReviewsRoute)
    <x-ui.navbar.item
        :href="route('public.reviews.latest')"
        label="Latest Reviews"
        icon="chat-bubble-left-right"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('public.reviews.*')"
    />
@endif

<x-ui.navbar.item
    :href="route('public.search')"
    label="Advanced Search"
    icon="funnel"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('public.search')"
/>
