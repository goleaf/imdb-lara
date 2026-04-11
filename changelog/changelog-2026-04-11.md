## 🗓️ 2026-04-11 — Catalog-Only Admin and Search Tightening

Hey! Here is what changed today in this project:

### What's New
The admin write flows now lean fully on Livewire pages and cards instead of a parallel set of POST, PATCH, and DELETE controller endpoints. That means titles, people, credits, seasons, episodes, media assets, and moderation actions all behave through the same page components the team is already using in the browser. Search also became more context-aware, so global suggestions can now inherit the filters a visitor is already using on the discovery screen. The shared Screenbase brand block also became more reusable, which lets auth pages and shell layouts share the same branded header structure instead of duplicating markup.

### What Was Improved
The catalog-only mode is much safer now because the title, person, credit, genre, and filter queries understand the remote IMDb-shaped tables directly instead of assuming the local app schema is always present. Title saves now also normalize local-style attributes into the remote movie shape before persistence, which closes a nasty gap for catalog-backed write flows. Media uploads keep cleaner metadata, get stable ULID-based filenames, and read image dimensions back from the stored file instead of the temp upload. Several heavier Livewire components were also reshaped around computed view data, which keeps rendering logic more predictable and easier to test.

### What Was Removed or Cleaned Up
The old admin mutation controllers and their companion HTTP mutation routes were removed, because they were duplicating behavior that now lives inside Livewire pages and moderation cards. The test suite was cleaned up around that same shift, so route assertions now explicitly prove those browser-facing mutation routes are gone. A few catalog-only preview paths were also cleaned up to avoid trying to hydrate missing optional attributes from remote records.

### Files That Changed
- `.env.example` — changed the example default database connection from `sqlite` to `imdb_mysql` so local setup matches the catalog-first app mode.
- `app/Actions/Admin/SaveMediaAssetAction.php` — fixed metadata merge order, switched upload naming to ULID-based filenames, and measured image dimensions from the stored file contents.
- `app/Actions/Catalog/BuildPublicPeopleIndexQueryAction.php` — taught people sorting to use the catalog-only schema cleanly, including safer fallback ordering for popularity, name, credits, and awards.
- `app/Actions/Catalog/BuildPublicTitleIndexQueryAction.php` — remapped title discovery filters and sort logic to the remote `movies` and `movie_ratings` tables when catalog-only mode is enabled.
- `app/Actions/Lists/BuildPublicListsIndexQueryAction.php` — added a guard that returns no public list results when catalog-only titles cannot be queried from the same connection.
- `app/Actions/Search/BuildGlobalSearchViewDataAction.php` — added optional title filters so global search suggestions can stay in sync with the current discovery context.
- `app/Actions/Search/BuildSearchResultsViewDataAction.php` — wrapped the people results lane in exception handling so a bad catalog lookup does not take down the whole search page.
- `app/Actions/Search/GetGlobalSearchSuggestionsAction.php` — split people and title suggestion loading, passed through contextual filters, and made people suggestions fail soft with reporting.
- `app/Actions/Search/GetSearchFilterOptionsAction.php` — sourced years, countries, languages, and interest categories from catalog-only lookup tables when local titles are not the source of truth.
- `app/Actions/Titles/RefreshTitleStatisticsAction.php` — rebuilt title statistics from ratings, reviews, watchlists, and episode counts while preserving existing award and Metacritic fields.
- `app/Http/Controllers/Admin/CatalogAdminController.php` — removed the old admin mutation controller because create, update, and delete flows now live in Livewire pages.
- `app/Http/Controllers/Admin/MediaAssetAdminController.php` — removed the old HTTP media asset mutation controller in favor of Livewire-driven media editing.
- `app/Http/Controllers/Admin/ModerationAdminController.php` — removed the old moderation mutation controller because the moderation cards now own those writes.
- `app/Livewire/Account/WatchlistBrowser.php` — moved the heavy watchlist render payload into a computed `viewData()` method.
- `app/Livewire/Admin/PersonProfessionEditor.php` — renamed the editable profession field to `professionName` and tightened the checks for whether a profession record already exists.
- `app/Livewire/Admin/ReviewModerationCard.php` — centralized review refresh logic so counts and relations reload consistently before and after moderation changes.
- `app/Livewire/Pages/Admin/Concerns/ValidatesFormRequests.php` — made Livewire form-request validation respect optional `after()` hooks and bind a synthetic route to the request instance.
- `app/Livewire/Pages/Admin/Concerns/InteractsWithCatalogPersonState.php` — added shared helpers for safely reading optional person attributes and dates from catalog-only records.
- `app/Livewire/Pages/Admin/Concerns/InteractsWithCatalogTitleState.php` — added shared helpers for safely reading optional title attributes and dates from catalog-only records.
- `app/Livewire/Pages/Admin/CreditsPage.php` — made the credit editor render safely in catalog-only mode without assuming the local relational shape is always available.
- `app/Livewire/Pages/Admin/EpisodesPage.php` — switched episode editing to the new catalog-title helper and safer preview filling for catalog-only records.
- `app/Livewire/Pages/Admin/GenresPage.php` — made genre form state safe for catalog-only rows that do not actually carry a `description` attribute.
- `app/Livewire/Pages/Admin/MediaAssetsPage.php` — stopped preview models from carrying uploaded file objects and hid direct URLs when an asset is backed by a stored upload.
- `app/Livewire/Pages/Admin/PeoplePage.php` — used the new catalog-person helper and safer preview payloads so people edits still render when remote-only fields are missing.
- `app/Livewire/Pages/Admin/SeasonsPage.php` — made season edit previews work in catalog-only mode and trimmed draft episode previews down to fillable attributes.
- `app/Livewire/Pages/Admin/TitlesPage.php` — added catalog-only-safe preview payloads, safer draft media previews, and helper-driven optional title field access.
- `app/Livewire/People/FilmographyPanel.php` — moved filmography view assembly into locked and computed Livewire state instead of rebuilding everything inline in `render()`.
- `app/Livewire/Search/GlobalSearch.php` — captured the current discovery filters and passed them into global search suggestions.
- `app/Models/CatalogMediaAsset.php` — started from sensible default catalog media values before overlaying remote attributes.
- `app/Models/Credit.php` — mapped credits onto the catalog-only `name_credits` table, foreign keys, ordering, and category behavior when remote data is in charge.
- `app/Models/Genre.php` — added catalog-only connection support, safer numeric route binding, and a fallback slug when a remote genre row does not provide one.
- `app/Models/MovieRating.php` — exposed `average_rating` and `rating_count` style accessors over the catalog vote fields.
- `app/Models/Person.php` — mapped the model to remote catalog tables and columns, added catalog-only bindings and searches, and merged remote image data into person media fallbacks.
- `app/Models/Season.php` — tightened numeric route binding by qualifying the key column explicitly.
- `app/Models/Title.php` — added broad catalog-only mapping for remote movie columns, relations, search, sorting, ratings, countries, languages, summaries, media fallbacks, and save-time attribute normalization.
- `app/Providers/AppServiceProvider.php` — turned on full Eloquent strict mode outside production instead of only preventing lazy loading.
- `config/queue.php` — aligned queue batching and failed-job database defaults with `imdb_mysql`.
- `resources/views/admin/people/edit.blade.php` — updated the embedded profession editor usage to pass the renamed `professionRecord` prop.
- `resources/views/components/auth/member-entry-card.blade.php` — swapped the auth card heading over to the shared brand component instead of hand-written logo and copy markup.
- `resources/views/components/ui/brand/index.blade.php` — expanded the shared brand component to support optional descriptions, copy wrappers, and slot-based logo detection.
- `resources/views/layouts/partials/account-sidebar.blade.php` — moved the member sidebar heading onto the richer shared brand component.
- `resources/views/layouts/partials/admin-sidebar.blade.php` — moved the admin sidebar heading onto the richer shared brand component.
- `resources/views/layouts/partials/app-shell.blade.php` — replaced repeated top-level brand markup with the shared brand component in both shell header variants.
- `resources/views/livewire/admin/person-profession-editor.blade.php` — bound the form to `professionName` and switched the button and delete checks to persisted-record detection.
- `routes/admin.php` — removed the browser-exposed HTTP mutation routes so the admin surface now exposes page routes only.
- `tests/Feature/Feature/Admin/AdminCatalogCrudTest.php` — rewrote the admin CRUD coverage around Livewire page actions and profession editors instead of removed HTTP mutation routes.
- `tests/Feature/Feature/Admin/AdminCatalogReadonlyPagesTest.php` — added explicit catalog-only fixture seeding and verified the read-only admin pages against remote-style IDs and relations.
- `tests/Feature/Feature/Admin/MediaAssetUploadTest.php` — rewrote media upload and moderation coverage around Livewire page actions and moderation cards instead of old HTTP routes.
- `tests/Feature/Feature/LivewireLoadingStateConventionTest.php` — added assertions that the heavier Livewire components now expose computed view-data methods.
- `tests/Feature/Feature/PortalRouteRegistrationTest.php` — flipped the route assertions to prove the old admin mutation routes are gone while the page routes still exist.
- `tests/Unit/Actions/Search/BuildSearchTitleResultsQueryActionTest.php` — added a focused regression test that checks catalog-only title search targets the remote movies schema.
- `changelog/changelog-2026-04-11.md` — refreshed the daily changelog entry so it documents this current admin, search, and catalog-only refactor accurately.

### Why This Matters
This update makes the project more coherent. The admin panel now writes through the same Livewire surfaces it renders, the catalog-only mode is much less brittle when it needs to read from remote IMDb-shaped tables, and search keeps more of a visitor’s context instead of dropping them into generic suggestions. That combination reduces duplicated paths, makes remote-data mode safer, and gives the tests a much clearer contract to enforce.

---
