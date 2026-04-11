## 🗓️ 2026-04-11 — The catalog went local-first and the admin got safer

Hey! Here is what changed today in this project:

### What's New
The catalog now leans much harder on the app's own `titles`, `people`, `credits`, and `seasons` data instead of the old IMDb-shaped mirror schema. Public archive pages for AKA attributes, certificates, companies, and company credit attributes also gained Livewire-powered filters, so searches and filter changes now update live without bouncing through old GET forms. A new set of environment flags makes it easier to control whether Screenbase runs in catalog-only mode and which shell shortcuts should be exposed.

### What Was Improved
Moderation queues now sort with indexed `status_priority` columns instead of custom SQL sorting, which makes the code cleaner and the queue queries friendlier to the database. Title, person, season, credit, and episode loaders were updated to read from the local schema and shared catalog relations, which trims legacy branching and reduces how much special-case data loading each page has to do. Heavier embedded Livewire surfaces now sit inside islands, and Vite is configured to split Livewire and UI-interaction code into clearer chunks. Tests were also rewritten around local factories and Livewire shells so they match the current browser surface instead of older write-heavy flows.

### What Was Removed or Cleaned Up
A large chunk of browser-side admin mutation UI was removed from catalog-only surfaces and replaced with clear read-only guidance that points editors back to the upstream sync workflow. Several raw SQL `CASE` orderings and old request-bound filter parsers were replaced with model fields and normalized filter arrays. The legacy IMDb table-drop migration now skips unit tests, and multiple tests no longer depend on remote catalog fixtures or manual seeding hacks.

### Files That Changed
- `.env.example` — added Screenbase environment toggles for catalog-only mode, legacy imports, and shell shortcuts.
- `app/Actions/Admin/BuildAdminContributionsIndexQueryAction.php` — switched contribution queue sorting to the stored `status_priority` column.
- `app/Actions/Admin/BuildAdminReportsIndexQueryAction.php` — switched report queue sorting to the stored `status_priority` column.
- `app/Actions/Admin/DeletePersonAction.php` — routes person media cleanup through a storage-aware delete action before soft deleting the person.
- `app/Actions/Admin/DeleteTitleAction.php` — routes title media cleanup through a storage-aware delete action before soft deleting the title.
- `app/Actions/Catalog/BuildPersonFilmographyQueryAction.php` — rebuilt the filmography query around local credits, local title statistics, and local person professions.
- `app/Actions/Catalog/BuildPublicPeopleIndexQueryAction.php` — moved people directory sorting and popularity handling onto local `people` fields.
- `app/Actions/Catalog/BuildPublicTitleIndexQueryAction.php` — moved browse filters and sorts onto local title columns and local `title_statistics`.
- `app/Actions/Catalog/GetFeaturedInterestCategoriesAction.php` — returns an empty collection when catalog-only mode is disabled.
- `app/Actions/Catalog/GetPeopleDirectorySnapshotAction.php` — derives profession snapshot counts from `person_professions` instead of the legacy profession taxonomy tables.
- `app/Actions/Catalog/GetPublicPeopleFilterOptionsAction.php` — builds public profession filters from `person_professions`.
- `app/Actions/Catalog/LoadAkaAttributeDetailsAction.php` — accepts normalized filter arrays so Livewire can drive archive filtering.
- `app/Actions/Catalog/LoadCertificateAttributeDetailsAction.php` — accepts normalized filter arrays so Livewire can drive archive filtering.
- `app/Actions/Catalog/LoadCertificateRatingDetailsAction.php` — accepts normalized filter arrays so Livewire can drive archive filtering.
- `app/Actions/Catalog/LoadCompanyCreditAttributeDetailsAction.php` — accepts normalized filter arrays so Livewire can drive archive filtering.
- `app/Actions/Catalog/LoadCompanyDetailsAction.php` — accepts normalized filter arrays for the company archive shell.
- `app/Actions/Catalog/LoadEpisodeDetailsAction.php` — rewired episode detail loading to local seasons, local episode metadata, local genres, and local credits.
- `app/Actions/Catalog/LoadPersonDetailsAction.php` — rewired person details to local alternate names, local credits, and local fallback collections.
- `app/Actions/Catalog/LoadSeasonDetailsAction.php` — rewired season details to local season and episode records.
- `app/Actions/Catalog/LoadTitleCastAction.php` — switched cast grouping to department-based credits and only hydrates remote catalog data when catalog-only mode is on.
- `app/Actions/Catalog/LoadTitleDetailsAction.php` — swapped several legacy interest, award, country, and episode lookups for local-first data and empty fallbacks where local data is not ready yet.
- `app/Actions/Home/GetAwardsSpotlightNominationsAction.php` — disables the awards spotlight outside catalog-only mode.
- `app/Actions/Home/GetHeroSpotlightAction.php` — uses department-based featured credits and media-asset-backed title loading.
- `app/Actions/Home/GetLatestTrailerTitlesAction.php` — finds trailers through unified media assets instead of the old video tables.
- `app/Actions/Lists/BuildAccountWatchlistQueryAction.php` — filters watchlists by `release_year` instead of the legacy `startyear` column.
- `app/Actions/Search/BuildDiscoveryViewDataAction.php` — reuses shared catalog card relations for discovery results.
- `app/Actions/Search/BuildSearchPublicListsQueryAction.php` — removes the raw SQL relevance ordering block from public list search.
- `app/Actions/Search/BuildSearchResultsViewDataAction.php` — only loads interest-category search results when catalog-only mode is enabled.
- `app/Actions/Search/GetGlobalSearchSuggestionsAction.php` — hides interest-category suggestions outside catalog-only mode.
- `app/Actions/Search/GetSearchFilterOptionsAction.php` — builds country and language filter options from local title fields and stops serving legacy interest category options.
- `app/Actions/Seo/GetSitemapDataAction.php` — rebuilds sitemap queries around local slugs, local title fields, local seasons, and local people records.
- `app/Actions/Seo/ResolvePageShellViewDataAction.php` — checks admin shortcut access through `canAccessAdminPanel()`.
- `app/Enums/ContributionStatus.php` — adds a priority helper for contribution status ordering.
- `app/Enums/ReportStatus.php` — adds a priority helper for report status ordering.
- `app/Http/Controllers/Admin/CatalogAdminController.php` — centralizes catalog create, update, and delete writes behind dedicated form requests and action classes.
- `app/Http/Controllers/Admin/MediaAssetAdminController.php` — centralizes title and person media-asset writes and enforces catalog-only write blocking.
- `app/Http/Controllers/Admin/ModerationAdminController.php` — centralizes review, report, and contribution moderation updates behind controller actions.
- `app/Http/Requests/Admin/StoreSeasonRequest.php` — normalizes series type authorization when title types may already be hydrated as enums.
- `app/Http/Requests/Admin/UpdateTitleRequest.php` — normalizes current and next title-type validation across enum and string inputs.
- `app/Livewire/Account/WatchlistBrowser.php` — precomputes watchlist empty states, summary badges, public links, and per-item action metadata for a leaner view.
- `app/Livewire/Pages/Account/ListsPage.php` — switches account list page branching from route-name checks to the presence of a bound list model.
- `app/Livewire/Pages/Admin/CreditsPage.php` — simplifies admin credit create/edit pages into read-only shells without heavy form-option loading.
- `app/Livewire/Pages/Admin/EpisodesPage.php` — trims extra catalog-only relation loading from the admin episode shell.
- `app/Livewire/Pages/Admin/GenresPage.php` — replaces route-name branching with dedicated index, create, and edit rendering paths.
- `app/Livewire/Pages/Admin/MediaAssetsPage.php` — replaces route-name branching with dedicated index and edit rendering paths.
- `app/Livewire/Pages/Admin/PeoplePage.php` — prepares draft profession and media records for the admin people shell.
- `app/Livewire/Pages/Admin/SeasonsPage.php` — prepares a draft episode record with local defaults for the admin season shell.
- `app/Livewire/Pages/Admin/TitlesPage.php` — prepares draft season and media records for the admin title shell.
- `app/Livewire/Pages/Auth/AuthPage.php` — centralizes auth page rendering through an explicit view parameter instead of route-name checks.
- `app/Livewire/Pages/Public/AkaAttributePage.php` — moves archive filters into URL-backed Livewire state with pagination resets.
- `app/Livewire/Pages/Public/BrowseTitlesPage.php` — keeps country and theme filters in URL-backed Livewire state and rebuilds browse links through route helpers.
- `app/Livewire/Pages/Public/CertificateAttributePage.php` — moves archive filters into URL-backed Livewire state.
- `app/Livewire/Pages/Public/CertificateRatingPage.php` — moves archive filters into URL-backed Livewire state.
- `app/Livewire/Pages/Public/CompanyCreditAttributePage.php` — moves archive filters into URL-backed Livewire state.
- `app/Livewire/Pages/Public/CompanyPage.php` — moves archive filters into URL-backed Livewire state.
- `app/Livewire/Pages/Public/InterestCategoriesPage.php` — switches between index and detail rendering by checking for a bound interest category instead of route names.
- `app/Livewire/Pages/Public/PeoplePage.php` — switches between index and detail rendering by checking for a bound person instead of route names.
- `app/Livewire/Pages/Public/TitlePage.php` — uses the local season relation when redirecting canonical episode URLs.
- `app/Livewire/Pages/Public/UserPage.php` — uses a bound list model to decide between public profile and public list rendering and access checks.
- `app/Models/Contribution.php` — stores and casts `status_priority` whenever a contribution is saved.
- `app/Models/Credit.php` — migrates the credit model onto local tables, adds soft deletes and factory support, and keeps compatibility aliases for legacy attributes.
- `app/Models/Episode.php` — uses the shared ordered credit scope and casts `deleted_at`.
- `app/Models/Person.php` — migrates the person model onto local tables, adds soft deletes and factory support, and keeps compatibility helpers for legacy IMDb-shaped attributes.
- `app/Models/PersonProfession.php` — adds a reusable ordered scope and a `name` accessor.
- `app/Models/Report.php` — stores and casts `status_priority` whenever a report is saved.
- `app/Models/Season.php` — migrates the season model onto local tables, adds soft deletes, and uses slug-based route binding.
- `app/Models/Title.php` — migrates the title model onto local tables, unifies media/statistics relations, and keeps compatibility helpers for legacy IMDb-shaped attributes.
- `config/screenbase.php` — makes Screenbase surface flags environment-driven.
- `database/migrations/2026_04_11_041757_drop_legacy_title_catalog_tables_from_imdb_mysql.php` — skips destructive IMDb mirror table drops during unit tests.
- `resources/views/admin/credits/create.blade.php` — replaces the create form with catalog-sync guidance.
- `resources/views/admin/credits/edit.blade.php` — replaces the edit form with catalog-sync guidance.
- `resources/views/admin/episodes/edit.blade.php` — replaces episode editing with a read-only guidance panel.
- `resources/views/admin/genres/create.blade.php` — replaces genre creation with a read-only guidance panel.
- `resources/views/admin/genres/edit.blade.php` — replaces genre editing and deleting with a read-only guidance panel.
- `resources/views/admin/media-assets/edit.blade.php` — replaces media editing with a read-only guidance panel.
- `resources/views/admin/media-assets/index.blade.php` — keeps inspection UI but removes delete/edit mutations in favor of inspect-only controls.
- `resources/views/admin/people/create.blade.php` — replaces person creation with upstream-sync guidance.
- `resources/views/admin/people/edit.blade.php` — replaces biography, profession, media, and credit editing with a read-only guidance panel.
- `resources/views/admin/seasons/_form.blade.php` — adds optional field prefixes so the season form partial can be reused for nested payloads.
- `resources/views/admin/seasons/edit.blade.php` — replaces season and episode management controls with a read-only guidance panel.
- `resources/views/admin/titles/_form.blade.php` — switches title-form selects and genre checkboxes onto shared Sheaf form controls.
- `resources/views/admin/titles/create.blade.php` — clarifies upstream-sync guidance and simplifies the action layout.
- `resources/views/admin/titles/edit.blade.php` — trims writable title editing down to core metadata plus delete, while keeping quick links for inspection.
- `resources/views/aka-attributes/show.blade.php` — converts the archive filter form into live Livewire controls with a reset action.
- `resources/views/certificates/attributes/show.blade.php` — converts the archive filter form into live Livewire controls with a reset action.
- `resources/views/certificates/ratings/show.blade.php` — converts the archive filter form into live Livewire controls with a reset action.
- `resources/views/companies/show.blade.php` — converts the archive filter form into live Livewire controls with a reset action.
- `resources/views/company-credit-attributes/show.blade.php` — converts the archive filter form into live Livewire controls with a reset action.
- `resources/views/components/ui/dropdown/item.blade.php` — adds scoped Livewire loading attributes and loading-state styling for dropdown actions.
- `resources/views/livewire/account/watchlist-browser.blade.php` — wraps the watchlist browser in a Livewire island and consumes precomputed summary and action state data.
- `resources/views/livewire/admin/contribution-moderation-card.blade.php` — changes moderation notes to `wire:model.live.blur`.
- `resources/views/livewire/admin/report-moderation-card.blade.php` — changes resolution notes to `wire:model.live.blur`.
- `resources/views/livewire/admin/review-moderation-card.blade.php` — changes moderation notes to `wire:model.live.blur`.
- `resources/views/livewire/auth/logout-button.blade.php` — scopes logout loading behavior through `wire:target`.
- `resources/views/livewire/lists/manage-list.blade.php` — wraps the manage-list surface in a Livewire island.
- `resources/views/livewire/people/filmography-panel.blade.php` — wraps the person filmography panel in a Livewire island.
- `resources/views/titles/show.blade.php` — relabels the primary video action and shows the selected video caption beside it.
- `tests/Feature/Feature/Admin/AdminCatalogCrudTest.php` — replaces CRUD mutation coverage with assertions that browser mutation routes are no longer registered.
- `tests/Feature/Feature/Admin/AdminCatalogReadonlyPagesTest.php` — rebuilds readonly-page coverage around local factories instead of remote catalog fixtures.
- `tests/Feature/Feature/Admin/AdminModerationQueuesTest.php` — switches moderation queue coverage to local title factories and removes manual seeding hacks.
- `tests/Feature/Feature/Admin/MediaAssetUploadTest.php` — replaces upload mutation coverage with assertions that media mutation routes are no longer registered.
- `tests/Feature/Feature/Admin/TitleFormViewTest.php` — adds coverage for the shared Sheaf form controls in the title form partial.
- `tests/Feature/Feature/AkaAttributePageTest.php` — asserts Livewire filter-shell markers instead of GET-form behavior.
- `tests/Feature/Feature/BrowseTitlesPageLocalRenderTest.php` — rewrites browse-page rendering tests around mocked browser actions and local title models.
- `tests/Feature/Feature/CertificateAttributePageTest.php` — asserts Livewire filter-shell markers instead of GET-form behavior.
- `tests/Feature/Feature/CertificateRatingPageTest.php` — asserts Livewire filter-shell markers instead of GET-form behavior.
- `tests/Feature/Feature/CompanyCreditAttributePageTest.php` — asserts Livewire filter-shell markers instead of GET-form behavior.
- `tests/Feature/Feature/CompanyPageTest.php` — asserts Livewire filter-shell markers instead of GET-form behavior.
- `tests/Feature/Feature/Feature/Admin/AdminReviewModerationQueueTest.php` — switches the review moderation queue test to local title factories and removes manual title seeding.
- `tests/Feature/Feature/HomepageTest.php` — adds `RefreshDatabase` so homepage coverage runs against the local application state cleanly.
- `tests/Feature/Feature/LivewireLoadingStateConventionTest.php` — adds coverage for Livewire islands around heavier embedded views.
- `tests/Feature/Feature/PortalRouteRegistrationTest.php` — expands route assertions to cover the removed admin mutation endpoints.
- `tests/TestCase.php` — automatically enables catalog-only surface mode for tests that opt into that contract.
- `vite.config.js` — adds Lightning CSS minification and manual chunks for Livewire and interaction bundles.
- `app/Actions/Admin/DeleteCreditAction.php` — adds a reusable credit deletion action.
- `app/Actions/Admin/DeleteMediaAssetAction.php` — adds a reusable media deletion action that also removes stored files.
- `app/Actions/Admin/SavePersonProfessionAction.php` — adds a reusable person-profession save action with primary-profession normalization.
- `database/migrations/2026_04_11_161816_add_status_priority_columns_to_reports_and_contributions_tables.php` — adds indexed priority columns and backfills existing report and contribution rows.
- `routes/admin.php` — restores controller-backed catalog, media, and moderation mutation routes alongside the Livewire admin pages.
- `tests/Feature/Feature/PublicArchiveLivewireFilterShellTest.php` — adds dedicated Livewire-shell coverage for public archive filters.
- `changelog/changelog-2026-04-11.md` — adds this human-readable project changelog entry.

### Why This Matters
This update keeps the app moving away from fragile legacy IMDb-shaped data access and toward a cleaner local-first catalog model, while also making the browser admin safer by removing write paths that should now flow through the upstream sync. On the public side, archive filters feel faster and more modern because they update live through Livewire, and on the maintenance side the codebase sheds raw SQL sort logic, remote-fixture test hacks, and several old schema assumptions that were getting harder to support.

---
