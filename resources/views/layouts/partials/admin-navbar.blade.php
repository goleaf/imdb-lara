<x-ui.navbar.item
    :href="route('admin.dashboard')"
    label="Dashboard"
    icon="chart-bar-square"
    class="sb-shell-topnav-item"
    :active="request()->routeIs('admin.dashboard')"
/>
@if ($canViewAdminTitles)
    <x-ui.navbar.item
        :href="route('admin.titles.index')"
        label="Titles"
        icon="film"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('admin.titles.*')"
    />
@endif
@if ($canViewAdminPeople)
    <x-ui.navbar.item
        :href="route('admin.people.index')"
        label="People"
        icon="users"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('admin.people.*') || request()->routeIs('admin.professions.*')"
    />
@endif
@if ($canViewAdminGenres)
    <x-ui.navbar.item
        :href="route('admin.genres.index')"
        label="Genres"
        icon="tag"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('admin.genres.*')"
    />
@endif
@if ($canViewAdminMediaAssets)
    <x-ui.navbar.item
        :href="route('admin.media-assets.index')"
        label="Media"
        icon="photo"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('admin.media-assets.*')"
    />
@endif
@if ($canViewAdminContributions)
    <x-ui.navbar.item
        :href="route('admin.contributions.index')"
        label="Contributions"
        icon="clipboard-document-check"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('admin.contributions.*')"
    />
@endif
@if ($canViewAdminReviews)
    <x-ui.navbar.item
        :href="route('admin.reviews.index')"
        label="Reviews"
        icon="chat-bubble-left-right"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('admin.reviews.*')"
    />
@endif
@if ($canViewAdminReports)
    <x-ui.navbar.item
        :href="route('admin.reports.index')"
        label="Reports"
        icon="flag"
        class="sb-shell-topnav-item"
        :active="request()->routeIs('admin.reports.*')"
    />
@endif
<x-ui.navbar.item
    :href="route('public.home')"
    label="Public Site"
    icon="arrow-up-right"
    class="sb-shell-topnav-item"
/>

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <x-ui.button
        type="submit"
        variant="ghost"
        size="sm"
        icon="arrow-right-start-on-rectangle"
    >
        Sign out
    </x-ui.button>
</form>
