## 🗓️ 2026-04-12 — Admin write paths are back and the catalog got steadier

Hey! Here is what changed today in this project:

### What's New
The admin area now has real controller-backed write routes again for titles, people, genres, credits, seasons, episodes, media assets, reviews, reports, and contributions. That means create, update, and delete actions can move through normal Laravel requests while the Livewire pages stay focused on rendering and interaction. The public catalog also got richer title and person payloads, so detail pages can show more of the IMDb-backed data without feeling half-hydrated. On the frontend side, a new shared Alpine state module now powers the small reusable UI behaviors that used to live inline in Blade.

### What Was Improved
Catalog-aware helpers are now used much more consistently, so the app can tell when it is running against the remote IMDb schema and adjust its queries, relations, ordering, and fallbacks automatically. Admin edit pages share one form-state system instead of each partial rebuilding its own naming rules, which makes the Blade layer much easier to reason about. Loading states across buttons, dropdowns, selects, comboboxes, pagination, and search results were cleaned up to fit the current Livewire 4 approach. I also fixed two regressions during verification: local title pages no longer try to load remote interest data, and public title pages now default missing collections more safely.

### What Was Removed or Cleaned Up
Large inline Alpine blobs were removed from Blade components and replaced with named helpers in a shared JavaScript module. A bunch of old `wire:loading.attr="data-loading"` bridges were removed from reusable UI components in favor of slot-based loading shells and explicit `wire:target` scopes where needed. The old idea that the admin surface should expose only Livewire pages was cleaned up too, because the write side now lives in clearer controller endpoints.

### Files That Changed
- `app/Actions/Admin/BuildAdminTitlesIndexQueryAction.php` — switched the admin titles index query to catalog-aware select columns and catalog-friendly name ordering.
- `app/Actions/Catalog/BuildCatalogMediaLightboxGroupAction.php` — changed lightbox item matching to use a stable media asset identifier instead of raw URL comparisons.
- `app/Actions/Catalog/BuildPublicPeopleIndexQueryAction.php` — improved people sorting so catalog pages can order by award nominations and cleaner tie-breakers.
- `app/Actions/Catalog/GetFeaturedInterestCategoriesAction.php` — now checks catalog-only availability through the model helper instead of a raw config flag.
- `app/Actions/Catalog/LoadPersonDetailsAction.php` — hydrates alternate-name data from catalog aliases before building the public person page payload.
- `app/Actions/Catalog/LoadPublicTitleBrowserPageAction.php` — caps show-all collections by the configured page size and makes cache keys respect that limit.
- `app/Actions/Catalog/LoadTitleCastAction.php` — eager loads episode, season, and series context so episode-specific credits can render and link safely.
- `app/Actions/Catalog/LoadTitleDetailsAction.php` — conditionally loads richer catalog relationships and skips remote-only interest loading for local title pages.
- `app/Actions/Catalog/LoadTitleMetadataExplorationAction.php` — reads genre and metadata exploration data through catalog-safe helpers.
- `app/Actions/Catalog/LoadTitleParentsGuideAction.php` — builds parents-guide and certificate data more carefully when the app runs in catalog-only mode.
- `app/Actions/Home/GetAwardsSpotlightNominationsAction.php` — switched its catalog-only check to the shared model helper.
- `app/Actions/Home/GetAwardsSpotlightTitlesAction.php` — now returns an empty collection in catalog-only mode instead of querying unavailable local tables.
- `app/Actions/Home/GetBrowseKeywordsAction.php` — now returns an empty collection in catalog-only mode for the same reason.
- `app/Actions/Home/GetBrowseYearsAction.php` — reads the right release-year column whether titles come from local tables or the remote IMDb schema.
- `app/Actions/Home/GetHomepageTitleRailAction.php` — makes homepage rails sort upcoming and recently added titles correctly in both local and catalog-only modes.
- `app/Actions/Lists/BuildAccountWatchlistQueryAction.php` — now filters by release year through the shared catalog-aware scope.
- `app/Actions/Lists/GetListTitleSuggestionsAction.php` — switched suggestion queries to catalog card columns and catalog-aware popularity ordering.
- `app/Actions/Search/BuildSearchResultsViewDataAction.php` — now guards interest-category loading through the catalog schema helper.
- `app/Actions/Search/GetGlobalSearchSuggestionsAction.php` — now uses the same catalog-aware interest-category guard.
- `app/Actions/Seo/GetSitemapDataAction.php` — rebuilt sitemap queries around catalog-aware selects, ordering, and episode relation loading.
- `app/Actions/Seo/ResolvePageShellViewDataAction.php` — now resolves catalog-only behavior through the model helper instead of a direct config read.
- `app/Http/Controllers/Admin/Concerns/BlocksCatalogOnlyAdminMutations.php` — adds a shared 501 response for admin writes while the app is running in catalog-only mode.
- `app/Http/Controllers/Admin/CreditController.php` — adds controller endpoints for creating, updating, and deleting credits.
- `app/Http/Controllers/Admin/EpisodeController.php` — adds controller endpoints for updating and deleting episodes.
- `app/Http/Controllers/Admin/GenreController.php` — adds controller endpoints for creating, updating, and deleting genres.
- `app/Http/Controllers/Admin/MediaAssetController.php` — adds controller endpoints for updating and deleting media assets with safer redirect handling.
- `app/Http/Controllers/Admin/ModerationController.php` — adds controller endpoints for review, report, and contribution moderation flows.
- `app/Http/Controllers/Admin/PersonController.php` — adds controller endpoints for person writes, profession writes, and attached media asset writes.
- `app/Http/Controllers/Admin/PersonProfessionController.php` — adds controller endpoints for updating and deleting person professions.
- `app/Http/Controllers/Admin/SeasonController.php` — adds controller endpoints for season writes and episode creation.
- `app/Http/Controllers/Admin/TitleController.php` — adds controller endpoints for title writes plus season and media asset creation.
- `app/Http/Controllers/Controller.php` — introduces the shared base controller for the new admin HTTP surface.
- `app/Http/Requests/Admin/UpdateTitleRequest.php` — tightens validation for origin country and original language fields.
- `app/Livewire/Account/WatchlistBrowser.php` — uses catalog card columns when resolving titles for watchlist state changes.
- `app/Livewire/Catalog/TitleBrowser.php` — passes the configured page size into show-all collection loading.
- `app/Livewire/Contributions/SuggestionForm.php` — resolves titles and people through catalog and directory column scopes instead of tiny ad hoc selects.
- `app/Livewire/Lists/ManageList.php` — uses catalog card columns when adding a title to a list.
- `app/Livewire/Pages/Admin/Concerns/ResolvesAdminFormState.php` — centralizes field names, state paths, and old-input helpers for admin Blade forms.
- `app/Livewire/Pages/Admin/ContributionsPage.php` — hydrates admin contribution targets through local title and person wrappers before rendering the queue.
- `app/Livewire/Pages/Admin/EpisodesPage.php` — now passes shared episode form data into the edit view.
- `app/Livewire/Pages/Admin/MediaAssetsPage.php` — hydrates local admin mediables and shares reusable media-asset form data with the edit view.
- `app/Livewire/Pages/Admin/PeoplePage.php` — shares reusable media-asset form state with the person edit page.
- `app/Livewire/Pages/Admin/SeasonsPage.php` — shares reusable form state for season editing and draft episode rows.
- `app/Livewire/Pages/Admin/TitlesPage.php` — shares reusable form state for titles, draft seasons, and draft media assets.
- `app/Livewire/Pages/Concerns/RendersPageView.php` — now resolves catalog-only page flags through the shared model helper.
- `app/Livewire/Pages/Public/TitlePage.php` — adds safer defaults for derived collections and supports director fallback entries more reliably.
- `app/Livewire/People/FilmographyPanel.php` — adds stable keys for grouped rows so the filmography panel re-renders more predictably.
- `app/Livewire/Search/GlobalSearch.php` — moves to URL-synced locked state and prepares cleaner, keyed section data for the overlay.
- `app/Models/AwardNomination.php` — eager loads nominee and nominated-title records with parent context preserved for title detail pages.
- `app/Models/CatalogMediaAsset.php` — adds a stable identifier helper for gallery, lightbox, and viewer matching.
- `app/Models/Contribution.php` — resolves admin labels and edit URLs through local title and person wrappers.
- `app/Models/Genre.php` — makes catalog-style slug binding fail cleanly when a genre slug or ID does not resolve.
- `app/Models/InterestCategory.php` — reads directory image values from raw attributes so catalog-backed cards stay reliable.
- `app/Models/LocalPerson.php` — adds a local-only person wrapper that always uses the app’s writable schema.
- `app/Models/LocalTitle.php` — adds a local-only title wrapper that always uses the app’s writable schema.
- `app/Models/MediaAsset.php` — adds integer casts and resolves admin attached-record labels and links through local wrappers.
- `app/Models/MovieAka.php` — makes archived title aliases load only the related catalog tables that are actually available.
- `app/Models/Person.php` — auto-detects catalog-only mode from the active database connection and adds nomination counts to directory metrics.
- `app/Models/Review.php` — adds an `adminTitle` relation so moderation screens can link through local titles safely.
- `app/Models/Title.php` — auto-detects catalog-only mode, caches table availability, adds catalog-aware scopes and relations, and preserves parent back-references on flattened records.
- `changelog/changelog-2026-04-12.md` — records this update in plain English for the team.
- `config/livewire.php` — publishes the app’s Livewire configuration, layout, pagination, asset, and component defaults.
- `resources/js/app.js` — registers the shared Alpine state module during frontend boot.
- `resources/js/components/ui-state.js` — adds reusable Alpine helpers for search, tooltips, dropdowns, popovers, switches, textareas, popups, and accordions.
- `resources/views/admin/episodes/_form.blade.php` — switches episode form binding over to the shared admin form helpers.
- `resources/views/admin/episodes/edit.blade.php` — passes reusable episode form data into the episode partial.
- `resources/views/admin/media-assets/_form.blade.php` — switches media asset form binding over to the shared admin form helpers.
- `resources/views/admin/media-assets/edit.blade.php` — passes reusable media asset form data and shows the attached-record link whenever a safe admin URL exists.
- `resources/views/admin/people/edit.blade.php` — reuses the shared media asset form data on the person edit screen.
- `resources/views/admin/seasons/_form.blade.php` — switches season form binding over to the shared admin form helpers.
- `resources/views/admin/seasons/edit.blade.php` — passes reusable season and draft-episode form data into the season page.
- `resources/views/admin/titles/_form.blade.php` — uses prepared genre selections and shared form helpers instead of rebuilding that state inline.
- `resources/views/admin/titles/edit.blade.php` — reuses shared title, season, and media asset form data on the title edit screen.
- `resources/views/components/auth/member-entry-card.blade.php` — simplifies the member tab highlighting logic.
- `resources/views/components/ui/accordion/index.blade.php` — replaces inline Alpine state with the shared accordion root helper.
- `resources/views/components/ui/accordion/item.blade.php` — replaces inline Alpine state with the shared accordion item helper.
- `resources/views/components/ui/button/index.blade.php` — removes the old `wire:loading.attr` bridge and leaves loading decoration to explicit state and slot selectors.
- `resources/views/components/ui/combobox/index.blade.php` — adds slot-based loading selectors for Livewire 4-friendly combobox rendering.
- `resources/views/components/ui/combobox/loading.blade.php` — exposes a named loading slot for combobox lists.
- `resources/views/components/ui/combobox/option.blade.php` — removes the old selector that hid options through list-level loading state.
- `resources/views/components/ui/combobox/option/empty.blade.php` — exposes a named empty-state slot for combobox lists.
- `resources/views/components/ui/combobox/options.blade.php` — standardizes combobox options markup around explicit list, empty, and loading slots.
- `resources/views/components/ui/dropdown/checkbox-or-radio.blade.php` — moves checkbox and radio dropdown state into the shared dropdown toggle helper.
- `resources/views/components/ui/dropdown/index.blade.php` — replaces inline dropdown Alpine code with the shared dropdown shell helper.
- `resources/views/components/ui/dropdown/item.blade.php` — removes the old `wire:loading.attr` bridge from reusable dropdown items.
- `resources/views/components/ui/dropdown/submenu.blade.php` — replaces inline submenu behavior with the shared dropdown submenu helper.
- `resources/views/components/ui/input/options/copyable.blade.php` — replaces inline copy-to-clipboard code with the shared input copy helper.
- `resources/views/components/ui/input/options/revealable.blade.php` — replaces inline password reveal code with the shared input reveal helper.
- `resources/views/components/ui/pagination/control.blade.php` — adds a reusable pagination control component for buttons and links.
- `resources/views/components/ui/popover/index.blade.php` — replaces inline Alpine state with the shared popover helper.
- `resources/views/components/ui/popup.blade.php` — replaces inline Alpine state with the shared popup visibility helper.
- `resources/views/components/ui/select/index.blade.php` — adds slot-based loading selectors for Livewire 4-friendly select rendering.
- `resources/views/components/ui/select/loading.blade.php` — exposes a named loading slot for select lists.
- `resources/views/components/ui/select/option.blade.php` — removes the old selector that hid options through list-level loading state.
- `resources/views/components/ui/select/option/empty.blade.php` — exposes a named empty-state slot for select lists.
- `resources/views/components/ui/select/options.blade.php` — standardizes select options markup around explicit list, empty, and loading slots.
- `resources/views/components/ui/switch/index.blade.php` — replaces inline switch state with the shared switch helper.
- `resources/views/components/ui/textarea/index.blade.php` — replaces inline autosize logic with the shared textarea helper.
- `resources/views/components/ui/tooltip/index.blade.php` — replaces inline tooltip state with the shared tooltip helper.
- `resources/views/livewire/account/watchlist-browser.blade.php` — adds stable keys and explicit `wire:target` scopes to watchlist filters and summary badges.
- `resources/views/livewire/admin/contribution-moderation-card.blade.php` — adds stable keys for moderation status options so Livewire can re-render the card cleanly.
- `resources/views/livewire/admin/person-profession-editor.blade.php` — scopes loading feedback to the save and delete actions.
- `resources/views/livewire/admin/report-moderation-card.blade.php` — links reported reviews through the local admin title relation when it exists.
- `resources/views/livewire/admin/review-moderation-card.blade.php` — links moderated reviews through the local admin title relation when it exists.
- `resources/views/livewire/lists/manage-list.blade.php` — replaces input loading wrappers with slot-based suggestion loading and scopes destructive actions more clearly.
- `resources/views/livewire/pagination/island-simple.blade.php` — moves Livewire island pagination onto the shared pagination control component and cleaner scroll handling.
- `resources/views/livewire/people/filmography-panel.blade.php` — uses the new stable group and row keys from the filmography panel payload.
- `resources/views/livewire/search/discovery-filters.blade.php` — adds stable keys to active filters and scopes clear and reset buttons to their own loading state.
- `resources/views/livewire/search/global-search.blade.php` — replaces inline overlay state with the shared search helper and adds stable keys to suggestion sections and rows.
- `resources/views/livewire/search/search-results.blade.php` — adds stable keys across filters and result cards and scopes the clear button to its own loading state.
- `resources/views/titles/cast.blade.php` — reuses preloaded episode credit data more safely when rendering episode-specific cast links.
- `resources/views/titles/media-archive.blade.php` — redesigns archive summary cards and trailer metadata around shared badges and named shell hooks.
- `resources/views/titles/media.blade.php` — compares viewer strip items through stable media identifiers instead of raw IDs or URLs.
- `resources/views/titles/show.blade.php` — reads award nomination title rows from prepared entry arrays with safer fallbacks.
- `resources/views/vendor/pagination/simple-tailwind.blade.php` — swaps the simple paginator over to the shared pagination control component.
- `resources/views/vendor/pagination/tailwind.blade.php` — swaps the full paginator over to the shared pagination control component.
- `routes/admin.php` — registers the controller-backed admin mutation routes beside the existing Livewire pages.
- `tests/Concerns/InteractsWithRemoteCatalog.php` — hardens remote catalog test helpers for missing connections and missing schema pieces.
- `tests/Feature/Feature/Admin/AdminCatalogCrudTest.php` — covers the restored admin title controller routes.
- `tests/Feature/Feature/Admin/AdminCatalogReadonlyPagesTest.php` — verifies catalog-only mode blocks controller-backed admin writes with 501 responses.
- `tests/Feature/Feature/Admin/MediaAssetUploadTest.php` — now expects media and moderation controller routes to be present.
- `tests/Feature/Feature/Admin/TitleFormViewTest.php` — verifies the title form receives prepared genre selection data.
- `tests/Feature/Feature/BrowseTitlesPageLocalRenderTest.php` — verifies show-all collections respect the configured page size.
- `tests/Feature/Feature/ButtonComponentTest.php` — verifies buttons and dropdown items no longer inject the old `wire:loading.attr` bridge.
- `tests/Feature/Feature/FrontendInteractionConventionTest.php` — locks in the shared Alpine registration and usage conventions.
- `tests/Feature/Feature/Livewire/SeasonWatchProgressTest.php` — makes the season watch-progress test setup choose stable seeded seasons.
- `tests/Feature/Feature/Livewire/TitleBrowserChartViewDataTest.php` — verifies chart and browser payloads use the configured page-size limit.
- `tests/Feature/Feature/LivewireConfigurationContractTest.php` — verifies the published Livewire configuration contract.
- `tests/Feature/Feature/LivewireLoadingStateConventionTest.php` — verifies the slot-based loading and keyed-loop conventions across Livewire views.
- `tests/Feature/Feature/PortalRouteRegistrationTest.php` — verifies the admin mutation routes exist and point to controller endpoints.
- `tests/Feature/Feature/PublicMysqlCatalogSmokeTest.php` — updates smoke assertions to match the current discovery page copy.
- `tests/Feature/Feature/Seo/SlugGenerationTest.php` — adds coverage for catalog-style genre slugs that do not resolve.
- `tests/Feature/Feature/TitleMediaArchiveShellContractTest.php` — adds shell-contract coverage for archive summary cards and trailer metadata badges.
- `tests/Feature/Feature/TitlePagePayloadFallbackTest.php` — verifies title pages can fall back to catalog credit directors and still render missing collections safely.
- `tests/Feature/Feature/Ui/AlpinePrimitiveRenderingTest.php` — verifies the shared Alpine UI primitives render the expected hooks.
- `tests/Feature/Feature/Ui/PaginationRenderingTest.php` — verifies the island paginator renders through the shared pagination control.
- `tests/TestCase.php` — teaches the shared test harness to skip gracefully when a remote catalog table is missing.
- `tests/Unit/Models/TitleTest.php` — covers catalog-only auto-detection, remote admin title queries, and the updated title-model behavior.

### Why This Matters
This update makes the project easier to extend without stumbling over hidden coupling. Admin writes now have a clear Laravel surface, catalog-only mode behaves more honestly, public pages can show fuller data with fewer weird gaps, and the frontend primitives now share one interaction language instead of a pile of slightly different inline scripts. That combination should make future changes faster, safer, and easier to test.

---
