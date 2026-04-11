## 🗓️ 2026-04-11 — Watchlists and Lists Now Load More Smoothly

Hey! Here is what changed today in this project:

### What's New
The account watchlist page, list creation form, and list management page now load as deferred Livewire components with purpose-built placeholder skeletons. That means these heavier account tools can show useful page structure immediately instead of blocking the first render while all their data loads. A new portal layout render test was also added so shared account and admin shell partials are validated with prepared view data.

### What Was Improved
Watchlist loading was reworked to gather matching title IDs first, then load and sort the local list items safely in memory. This makes the page more resilient when catalog titles disappear or no longer qualify for display, and the same null-title cleanup was applied to dashboard activity, public profile modules, latest reviews, and public user lists so broken relations stop leaking into the UI. Loading states are now more targeted as well, so the watchlist skeleton only appears for filter, pagination, and item actions instead of flashing for unrelated updates.

### What Was Removed or Cleaned Up
This update removes a fragile dependency on cross-source subquery sorting for the watchlist browser and replaces it with a safer collection-based flow. It also cleans up Livewire view bindings by reading computed values directly from `$this` in the custom list picker, which matches how the component actually exposes its data. Nothing user-visible was deleted, but a few brittle assumptions around missing titles and loading behavior were trimmed away.

### Files That Changed
- `app/Actions/Lists/BuildAccountWatchlistQueryAction.php` — rebuilt watchlist filtering and sorting so matching titles are resolved first, missing titles are skipped, and pagination can happen after safe in-memory ordering.
- `app/Actions/Lists/GetAccountWatchlistFilterOptionsAction.php` — fixed genre option ordering by sorting the in-memory collection correctly.
- `app/Actions/Users/LoadAccountDashboardAction.php` — filters recent watchlist, rating, and review activity so the dashboard ignores records whose titles are no longer available.
- `app/Actions/Users/LoadPublicUserProfileAction.php` — removes null-title items from the public watchlist preview, recent reviews, and recent ratings before rendering profile sections.
- `app/Livewire/Account/WatchlistBrowser.php` — switched the component to deferred rendering with a placeholder view and manual pagination over the filtered watchlist collection.
- `app/Livewire/Lists/ManageList.php` — added a placeholder view and explicit return types so the deferred list manager can render a stable skeleton first.
- `app/Livewire/Pages/Public/LatestReviewsPage.php` — filters paginated reviews after loading so orphaned titles do not break the latest reviews feed.
- `app/Livewire/Pages/Public/UserPage.php` — filters public list items after pagination and uses the first surviving title for the preview artwork.
- `resources/views/account/lists/index.blade.php` — defers the create-list Livewire component on the account lists index page.
- `resources/views/account/lists/show.blade.php` — defers the manage-list Livewire component on the list detail page.
- `resources/views/account/watchlist.blade.php` — defers the watchlist browser on the watchlist page.
- `resources/views/livewire/account/watchlist-browser.blade.php` — narrows loading indicators to watchlist interactions that actually refresh results.
- `resources/views/livewire/lists/create-list-form.blade.php` — scopes the submit button loading state to the save action.
- `resources/views/livewire/titles/custom-list-picker.blade.php` — reads computed lists, selected lists, and visibility options from the component instance consistently.
- `resources/views/livewire/placeholders/account-watchlist-browser.blade.php` — adds a skeleton placeholder for the deferred watchlist browser.
- `resources/views/livewire/placeholders/create-list-form.blade.php` — adds a skeleton placeholder for the deferred create-list form.
- `resources/views/livewire/placeholders/manage-list.blade.php` — adds a skeleton placeholder for the deferred list manager.
- `tests/Feature/Feature/LivewireLoadingStateConventionTest.php` — adds coverage for deferred account components, placeholder methods, and tighter watchlist loading targets.
- `tests/Feature/Feature/PortalSurfaceSmokeTest.php` — updates the public lists heading assertion to match the current page copy.
- `tests/Feature/Feature/PortalLayoutPreparedDataRenderTest.php` — adds coverage for layout shell flags and prepared account/admin navigation partials.
- `changelog/changelog-2026-04-11.md` — records this update in a human-readable project changelog entry.

### Why This Matters
This update makes the account-side browsing experience feel faster, makes public/profile surfaces more durable when catalog data goes missing, and adds tests around the exact layout and loading conventions the portal now depends on. The result is a UI that degrades more gracefully, loads more predictably, and is less likely to show broken cards or jarring loading flashes.

---
