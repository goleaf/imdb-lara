## 🗓️ 2026-04-11 — Livewire Admin Lookups and Catalog-Only Data Tightening

Hey! Here is what changed today in this project:

### What's New
The admin area can now manage imported AKA attributes, AKA types, and award categories through dedicated Livewire pages instead of leaving those lookup tables hidden behind the import pipeline. The title detail page also gained a much more useful AKA types section that explains what each type means and shows which alternate titles actually use it. On the import side, IMDb AKA payloads can now create both lookup rows and bridge rows for AKA types, so the catalog carries more of the source data all the way through to the public UI. The demo seeder was also expanded so local installs have a fuller mix of titles, seasons, episodes, awards, contributions, watchlist activity, and notifications to browse.

### What Was Improved
Catalog-only mode is much better aligned with the remote IMDb-style schema now. Titles, people, credits, professions, ratings, awards, and media helpers all understand the remote column names more directly, which means fewer translation gaps between local app code and imported catalog data. Search suggestions, people filter options, hero spotlight credits, and title/person detail loaders were also tightened so they reuse the new catalog-aware projections instead of assuming the local schema. The global search shell now leans harder on shared UI buttons and empty-state components, which gives the overlay a cleaner structure and a steadier contract for future styling or behavior changes.

### What Was Removed or Cleaned Up
The old controller-based admin mutation endpoints were removed from the admin route surface because the write flows now live on Livewire pages. Several embedded Livewire Blade views also dropped their island wrappers, which simplifies rendering and shifts the tests toward direct component behavior. A stale changelog entry that described a different refactor was replaced with this one so the project history actually matches the current tree.

### Files That Changed
- `app/Actions/Admin/BuildAdminAkaAttributesIndexQueryAction.php` — added the admin listing query builder for searchable AKA attribute pages.
- `app/Actions/Admin/BuildAdminAkaTypesIndexQueryAction.php` — added the admin listing query builder for searchable AKA type pages.
- `app/Actions/Admin/BuildAdminAwardCategoriesIndexQueryAction.php` — added the admin listing query builder for searchable award category pages.
- `app/Actions/Admin/SaveAkaAttributeAction.php` — added the save action that normalizes and persists AKA attribute edits.
- `app/Actions/Admin/SaveAkaTypeAction.php` — added the save action that normalizes and persists AKA type edits.
- `app/Actions/Admin/SaveAwardCategoryAction.php` — added the save action that normalizes and persists award category edits.
- `app/Actions/Catalog/BuildPersonFilmographyQueryAction.php` — switched filmography queries onto shared credit projections and relation loading.
- `app/Actions/Catalog/BuildTitleCreditsQueryAction.php` — made title credit loading use catalog-aware column projections and remote-safe ordering.
- `app/Actions/Catalog/GetPeopleDirectorySnapshotAction.php` — taught the people snapshot to count professions from remote catalog profession terms.
- `app/Actions/Catalog/GetPublicPeopleFilterOptionsAction.php` — taught people filters to read professions from remote profession lookup rows in catalog-only mode.
- `app/Actions/Catalog/LoadAkaAttributeDetailsAction.php` — refactored AKA archive loading to use new reusable movie AKA scopes and relations.
- `app/Actions/Catalog/LoadEpisodeDetailsAction.php` — updated episode credit loading to use the shared catalog-aware credit projections.
- `app/Actions/Catalog/LoadPersonDetailsAction.php` — updated collaborator and preview credit queries to work against remote catalog credit columns.
- `app/Actions/Catalog/LoadTitleCastAction.php` — changed cast and crew loading to use shared credit scopes and catalog-safe existence checks.
- `app/Actions/Catalog/LoadTitleDetailsAction.php` — added richer AKA type entries and reused the new title detail relation helpers.
- `app/Actions/Home/GetHeroSpotlightAction.php` — reused the shared catalog-aware credit projections for hero spotlight credits.
- `app/Actions/Import/ImportImdbCatalogTitlePayloadAction.php` — added AKA type syncing and reused lookup row helpers for AKA attributes and award categories.
- `app/Actions/Layout/BuildTopNavigationAction.php` — added admin navigation entries for AKA attributes, AKA types, and award categories.
- `app/Actions/Layout/ResolveBreadcrumbIconAction.php` — added breadcrumb icon handling for the new admin lookup pages.
- `app/Actions/Lists/DetachTitleFromUserListAction.php` — normalized remaining list item positions with direct updates instead of resaving each model instance.
- `app/Actions/Search/GetDiscoveryTitleSuggestionsAction.php` — rebuilt discovery suggestions with a lighter catalog-aware query and filtered out episode rows.
- `app/Http/Controllers/Admin/CatalogAdminController.php` — removed the old controller-based catalog mutation endpoints.
- `app/Http/Controllers/Admin/MediaAssetAdminController.php` — removed the old controller-based media mutation endpoints.
- `app/Http/Controllers/Admin/ModerationAdminController.php` — removed the old controller-based moderation mutation endpoints.
- `app/Http/Requests/Admin/StoreAkaAttributeRequest.php` — added create validation and authorization for AKA attribute pages.
- `app/Http/Requests/Admin/StoreAkaTypeRequest.php` — added create validation and authorization for AKA type pages.
- `app/Http/Requests/Admin/StoreAwardCategoryRequest.php` — added create validation and authorization for award category pages.
- `app/Http/Requests/Admin/UpdateAkaAttributeRequest.php` — added update validation and authorization for AKA attribute pages.
- `app/Http/Requests/Admin/UpdateAkaTypeRequest.php` — added update validation and authorization for AKA type pages.
- `app/Http/Requests/Admin/UpdateAwardCategoryRequest.php` — added update validation and authorization for award category pages.
- `app/Livewire/Pages/Admin/AkaAttributeCreatePage.php` — added the Livewire create page wrapper for AKA attributes.
- `app/Livewire/Pages/Admin/AkaAttributeEditPage.php` — added the Livewire edit page wrapper for AKA attributes.
- `app/Livewire/Pages/Admin/AkaAttributesIndexPage.php` — added the Livewire index page wrapper for AKA attributes.
- `app/Livewire/Pages/Admin/AkaAttributesPage.php` — added the shared Livewire CRUD page logic for AKA attribute management.
- `app/Livewire/Pages/Admin/AkaTypeCreatePage.php` — added the Livewire create page wrapper for AKA types.
- `app/Livewire/Pages/Admin/AkaTypeEditPage.php` — added the Livewire edit page wrapper for AKA types.
- `app/Livewire/Pages/Admin/AkaTypesIndexPage.php` — added the Livewire index page wrapper for AKA types.
- `app/Livewire/Pages/Admin/AkaTypesPage.php` — added the shared Livewire CRUD page logic for AKA type management.
- `app/Livewire/Pages/Admin/AwardCategoriesIndexPage.php` — added the Livewire index page wrapper for award categories.
- `app/Livewire/Pages/Admin/AwardCategoriesPage.php` — added the shared Livewire CRUD page logic for award category management.
- `app/Livewire/Pages/Admin/AwardCategoryCreatePage.php` — added the Livewire create page wrapper for award categories.
- `app/Livewire/Pages/Admin/AwardCategoryEditPage.php` — added the Livewire edit page wrapper for award categories.
- `app/Livewire/Pages/Public/TitlePage.php` — switched the title page payload contract from raw AKA type rows to richer AKA type entries.
- `app/Models/AkaAttribute.php` — added admin listing scopes, lookup row helpers, and usage-count helpers for AKA attributes.
- `app/Models/AkaType.php` — added admin listing scopes, lookup row helpers, usage counts, and presentation helpers for AKA types.
- `app/Models/AwardCategory.php` — added admin listing scopes, lookup row helpers, usage counts, and presentation helpers for award categories.
- `app/Models/AwardNomination.php` — added title-detail query scopes for remote award nominations.
- `app/Models/Credit.php` — expanded credit mapping so catalog-only reads and writes work against remote name credit columns and character bridges.
- `app/Models/Genre.php` — made genre-to-title relations and description fallback logic safer in catalog-only mode.
- `app/Models/MovieAka.php` — added scopes and relations for filtering, loading, and ordering AKA records plus their types.
- `app/Models/MovieAkaAttribute.php` — added reusable scopes for attribute filtering and stable ordering.
- `app/Models/MovieAkaType.php` — added the new bridge model for movie AKA type assignments.
- `app/Models/Person.php` — expanded remote person mapping, profession relations, award access, and save normalization for catalog-only mode.
- `app/Models/PersonProfession.php` — expanded profession mapping so remote profession bridges can be read, sorted, and persisted cleanly.
- `app/Models/Title.php` — greatly expanded catalog-only title mapping, remote persistence normalization, resolved helper collections, and media/AKA/award relations.
- `app/Models/TitleStatistic.php` — mapped title statistics cleanly onto remote movie rating columns and save behavior.
- `app/Policies/AkaAttributePolicy.php` — added catalog management authorization for AKA attribute pages.
- `app/Policies/AkaTypePolicy.php` — added catalog management authorization for AKA type pages.
- `app/Policies/AwardCategoryPolicy.php` — added catalog management authorization for award category pages.
- `app/Providers/ViewServiceProvider.php` — exposed permissions for the new admin lookup pages to the shared navigation builder.
- `database/migrations/2026_04_11_120000_create_movie_aka_types_table.php` — added the remote bridge table for storing AKA type assignments per movie AKA.
- `database/seeders/DemoCatalogSeeder.php` — expanded the demo seed so local environments get richer titles, seasons, awards, contributions, watchlist data, and notifications.
- `resources/views/admin/aka-attributes/_form.blade.php` — added the shared form partial for AKA attribute fields.
- `resources/views/admin/aka-attributes/create.blade.php` — added the AKA attribute create screen and catalog-only write-disabled state.
- `resources/views/admin/aka-attributes/edit.blade.php` — added the AKA attribute edit screen, usage badge, and delete action.
- `resources/views/admin/aka-attributes/index.blade.php` — added the AKA attribute listing screen with search and archive links.
- `resources/views/admin/aka-types/_form.blade.php` — added the shared form partial for AKA type fields.
- `resources/views/admin/aka-types/create.blade.php` — added the AKA type create screen and catalog-only write-disabled state.
- `resources/views/admin/aka-types/edit.blade.php` — added the AKA type edit screen, usage badge, and delete action.
- `resources/views/admin/aka-types/index.blade.php` — added the AKA type listing screen with search.
- `resources/views/admin/award-categories/_form.blade.php` — added the shared form partial for award category fields.
- `resources/views/admin/award-categories/create.blade.php` — added the award category create screen and catalog-only write-disabled state.
- `resources/views/admin/award-categories/edit.blade.php` — added the award category edit screen, linked nomination badge, and delete action.
- `resources/views/admin/award-categories/index.blade.php` — added the award category listing screen with search.
- `resources/views/admin/titles/_form.blade.php` — preserved selected genre ids more safely when title genre relations are not already loaded.
- `resources/views/livewire/account/watchlist-browser.blade.php` — removed the outer island wrapper from the watchlist browser view.
- `resources/views/livewire/catalog/interest-category-browser.blade.php` — removed the outer island wrapper from the interest category browser view.
- `resources/views/livewire/catalog/people-browser.blade.php` — removed the outer island wrapper from the people browser view.
- `resources/views/livewire/catalog/title-browser.blade.php` — removed the outer island wrapper from the title browser view.
- `resources/views/livewire/lists/manage-list.blade.php` — removed the outer island wrapper from the list management view.
- `resources/views/livewire/people/filmography-panel.blade.php` — removed the outer island wrapper from the filmography panel view.
- `resources/views/livewire/search/global-search.blade.php` — switched the search overlay to shared button and empty-state components with explicit control slots.
- `resources/views/titles/show.blade.php` — replaced the raw AKA type table with a richer explanation of type meaning and linked AKA usage.
- `routes/admin.php` — removed legacy controller mutation routes and added Livewire routes for AKA attributes, AKA types, and award categories.
- `tests/Concerns/BootstrapsImdbMysqlSqlite.php` — expanded the SQLite-backed IMDb test schema with the new lookup, bridge, and remote-support tables.
- `tests/Concerns/BuildsCatalogTitleFixtures.php` — expanded catalog title and poster fixtures with the fields needed by the richer title mapping.
- `tests/Feature/ExampleTest.php` — updated the homepage smoke test to match the current home page content.
- `tests/Feature/Feature/Account/WatchlistBrowserTest.php` — updated watchlist browser tests for direct Livewire rendering without island loading.
- `tests/Feature/Feature/Admin/AdminAkaAttributeCrudTest.php` — added CRUD coverage for the new AKA attribute Livewire admin pages.
- `tests/Feature/Feature/Admin/AdminAkaTypeCrudTest.php` — added CRUD coverage for the new AKA type Livewire admin pages.
- `tests/Feature/Feature/Admin/AdminAwardCategoryCrudTest.php` — added CRUD coverage for the new award category Livewire admin pages.
- `tests/Feature/Feature/Admin/AdminCatalogCrudTest.php` — updated route assertions to prove the legacy admin mutation routes are gone.
- `tests/Feature/Feature/Admin/AdminCatalogReadonlyPagesTest.php` — added catalog-only read-only coverage for the new admin lookup pages.
- `tests/Feature/Feature/Admin/MediaAssetUploadTest.php` — updated route assertions to prove media and moderation controller routes are gone.
- `tests/Feature/Feature/BrowseTitlesPageLocalRenderTest.php` — updated title browser rendering tests to run without Livewire lazy loading.
- `tests/Feature/Feature/BrowseTopRatedSeriesPageLocalRenderTest.php` — updated the top-rated series browser test to run without Livewire lazy loading.
- `tests/Feature/Feature/CatalogExplorerPageTest.php` — updated catalog explorer fallback expectations and direct rendering behavior.
- `tests/Feature/Feature/Database/ImdbCatalogSchemaTest.php` — updated relationship coverage to use media assets instead of older image-specific models.
- `tests/Feature/Feature/Import/ImportImdbCatalogTitlePayloadActionTest.php` — added import coverage for AKA attributes, AKA types, and award category lookup syncing.
- `tests/Feature/Feature/InterestCategoryDirectoryTest.php` — updated interest category directory rendering tests to run without Livewire lazy loading.
- `tests/Feature/Feature/Lists/ListManagementTest.php` — updated list management tests for direct Livewire rendering without island loading.
- `tests/Feature/Feature/Livewire/PersonFilmographyPanelTest.php` — updated filmography panel tests for direct component rendering.
- `tests/Feature/Feature/LivewireLoadingStateConventionTest.php` — removed island-wrapper expectations and extended computed view-data coverage.
- `tests/Feature/Feature/PeopleDirectorySnapshotTest.php` — added coverage for remote profession filters and adjusted catalog-only fixture creation.
- `tests/Feature/Feature/PortalRouteRegistrationTest.php` — updated route coverage for the new lookup pages and the removal of controller mutation routes.
- `tests/Feature/Feature/Search/GlobalSearchShellContractTest.php` — added shell contract coverage for the global search overlay controls and empty states.
- `tests/Feature/Feature/TitlePagePayloadFallbackTest.php` — added title page coverage for the richer AKA type payload entries.
- `tests/Unit/Actions/Catalog/HydrateTitleCastCatalogActionTest.php` — tightened title statistic assertions around formatted aggregate ratings.
- `tests/Unit/Actions/Search/GetDiscoveryTitleSuggestionsActionTest.php` — added coverage that discovery suggestions exclude episode rows.
- `tests/Unit/Models/InterestCategoryTest.php` — unguarded models in setup so interest category model helpers can be exercised more directly.
- `tests/Unit/Models/TitleTest.php` — added coverage for flattened AKA types and refined remote title relation helper assertions.
- `changelog/changelog-2026-04-11.md` — replaced a stale changelog entry with one that matches the current repo state.

### Why This Matters
This update makes the project easier to operate and harder to break. Admin users can now manage key imported lookup data through the same Livewire admin surface the rest of the tool uses, the public title experience exposes richer imported metadata instead of raw rows, and catalog-only mode is much more faithful to the remote schema it depends on. That combination reduces hidden gaps between import data, admin tools, and what visitors actually see.

---
