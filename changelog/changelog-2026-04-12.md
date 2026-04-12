## 🗓️ 2026-04-12 — The catalog got safer and source imports learned how to resume

Hey! Here is what changed today in this project:

### What's New
A brand-new `imdb:migrate-source-database` command can now copy shared tables from another database into this app without losing its place if the run gets interrupted. It keeps per-table checkpoints, prints real progress as it runs, and knows how to pick back up from the last saved cursor instead of starting from zero again. The admin area also finished a cleanup pass: the old controller mutation layer is gone, so the write surface is now centered around the Livewire pages and shared Blade form components that the team is already using.

### What Was Improved
The public catalog now handles missing IMDb people, credit, and nominee tables much more gracefully. Instead of throwing query errors or rendering half-broken pages, the people browser, title cast page, award views, search suggestions, and homepage widgets can fall back to empty-but-valid states when a remote table is unavailable. Personal title tracking also got stricter, so suspended users can no longer sneak through watchlist, rating, or watched-state mutations, and the moderation cards now show the related title names more reliably.

### What Was Removed or Cleaned Up
The legacy admin controllers, their catalog-only mutation guard trait, and the old admin mutation routes were removed because that duplicate write path was no longer needed. A few Blade partials were cleaned up to use the shared `x-ui.native-select` control instead of repeating the same raw `<select>` markup over and over, and one leftover inline fallback in the title page template was dropped because the data is already prepared upstream.

### Files That Changed
- `app/Actions/Catalog/GetPeopleDirectorySnapshotAction.php` — now returns safe zeroed people metrics when the remote people or credits tables are missing.
- `app/Actions/Catalog/GetPublicPeopleFilterOptionsAction.php` — now returns empty profession filters instead of querying unavailable remote people tables.
- `app/Actions/Catalog/LoadAwardNominationDetailsAction.php` — only loads nominee people when the nominee tables exist and reads people labels from a safe loaded collection helper.
- `app/Actions/Catalog/LoadAwardsArchiveAction.php` — gives the awards archive the same safe nominee-loading behavior and avoids touching missing people relations.
- `app/Actions/Catalog/LoadEpisodeDetailsAction.php` — skips credit hydration entirely when the remote credits table is unavailable.
- `app/Actions/Catalog/LoadPersonDetailsAction.php` — guards known-for titles, collaborator lookups, and preview credits behind catalog table availability checks.
- `app/Actions/Catalog/LoadTitleCastAction.php` — returns empty cast and crew paginators with valid SEO data when catalog credits are unavailable.
- `app/Actions/Catalog/LoadTitleDetailsAction.php` — now zeroes title credit counts and previews when the remote credits table cannot be used.
- `app/Actions/Home/GetAwardsSpotlightNominationsAction.php` — only eager loads nominee people when those remote tables are actually present.
- `app/Actions/Home/GetHeroSpotlightAction.php` — stops trying to hydrate hero credits when the catalog credits table is missing.
- `app/Actions/Home/GetPopularPeopleAction.php` — returns an empty people rail when the remote people directory is unavailable.
- `app/Actions/Search/BuildSearchResultsViewDataAction.php` — skips people search work when the app is in catalog mode without people tables.
- `app/Actions/Search/GetGlobalSearchSuggestionsAction.php` — applies the same people-directory guard to global search suggestions.
- `app/Actions/Database/MigrateSourceDatabaseAction.php` — adds the resumable shared-table importer with cursor checkpoints, progress callbacks, retry support, and per-table reports.
- `app/Actions/Database/RunDatabaseMigrationTransactionAction.php` — retries deadlocks and lock timeouts during migration writes instead of failing immediately.
- `app/Console/Commands/ImdbMigrateSourceDatabaseCommand.php` — exposes the new import flow as an Artisan command with progress output and resume/reset options.
- `app/Http/Controllers/Admin/Concerns/BlocksCatalogOnlyAdminMutations.php` — deleted the old trait because the legacy admin controller write layer was removed.
- `app/Http/Controllers/Admin/CreditController.php` — deleted the legacy credit mutation controller now that admin writes stay on the Livewire surface.
- `app/Http/Controllers/Admin/EpisodeController.php` — deleted the legacy episode mutation controller.
- `app/Http/Controllers/Admin/GenreController.php` — deleted the legacy genre mutation controller.
- `app/Http/Controllers/Admin/MediaAssetController.php` — deleted the legacy media asset mutation controller.
- `app/Http/Controllers/Admin/ModerationController.php` — deleted the legacy moderation mutation controller.
- `app/Http/Controllers/Admin/PersonController.php` — deleted the legacy person mutation controller.
- `app/Http/Controllers/Admin/PersonProfessionController.php` — deleted the legacy profession mutation controller.
- `app/Http/Controllers/Admin/SeasonController.php` — deleted the legacy season mutation controller.
- `app/Http/Controllers/Admin/TitleController.php` — deleted the legacy title mutation controller.
- `app/Http/Controllers/Controller.php` — deleted the unused base controller because no HTTP controllers remain in this repo.
- `app/Livewire/Admin/ReportModerationCard.php` — now computes the reported review title in PHP and passes clean view data into the Blade template.
- `app/Livewire/Admin/ReviewModerationCard.php` — now computes the displayed review title in PHP instead of leaving that work inside Blade.
- `app/Livewire/Catalog/PeopleBrowser.php` — shows a clear unavailable state and an empty paginator when the remote people directory is missing.
- `app/Livewire/Pages/Public/PeoplePage.php` — renders the people index with prepared SEO data when the catalog has titles but no people tables.
- `app/Livewire/Titles/RatingPanel.php` — now authorizes title tracking mutations and adds an explicit view return type.
- `app/Livewire/Titles/WatchStatePanel.php` — now authorizes watch-state mutations and adds an explicit view return type.
- `app/Livewire/Titles/WatchlistToggle.php` — now authorizes watchlist mutations and adds an explicit view return type.
- `app/Models/AwardNomination.php` — adds safe nominee-people availability checks and a `loadedPeople()` helper for relation-aware rendering.
- `app/Models/Credit.php` — adds a `catalogCreditsAvailable()` helper and only projects character relations when that remote table exists.
- `app/Models/DatabaseMigrationState.php` — stores source import cursor state, copied row counts, status, and last error details.
- `app/Models/Person.php` — adds a `catalogPeopleAvailable()` helper and makes detail metrics conditional on which remote tables exist.
- `app/Models/Title.php` — only exposes catalog credits relations when the remote credits table is available.
- `app/Policies/TitlePolicy.php` — adds a `track` ability so personal title interactions can block suspended users.
- `database/migrations/2026_04_11_225723_create_database_migration_states_table.php` — creates the checkpoint table for resumable source imports.
- `resources/views/admin/credits/_form.blade.php` — switches admin credit selects over to the shared native-select component.
- `resources/views/admin/episodes/_form.blade.php` — switches the episode publish-status select over to the shared native-select component.
- `resources/views/admin/media-assets/_form.blade.php` — switches the media kind select over to the shared native-select component.
- `resources/views/admin/people/_form.blade.php` — switches the person publish-status select over to the shared native-select component.
- `resources/views/livewire/admin/report-moderation-card.blade.php` — now renders the related review title from prepared Livewire view data.
- `resources/views/livewire/admin/review-moderation-card.blade.php` — now renders the related review title from prepared Livewire view data.
- `resources/views/titles/show.blade.php` — removes an inline fallback collection assignment that is now handled before the view renders.
- `routes/admin.php` — removes the old admin controller mutation routes and leaves the admin surface as Livewire pages only.
- `tests/Concerns/InteractsWithRemoteCatalog.php` — improves remote-catalog skipping and sample-record lookup when some IMDb tables are missing.
- `tests/Feature/Feature/Admin/AdminCatalogCrudTest.php` — now verifies that the removed admin controller mutation routes stay unregistered.
- `tests/Feature/Feature/Admin/AdminCatalogReadonlyPagesTest.php` — now verifies that catalog-only mode exposes no title mutation route.
- `tests/Feature/Feature/Admin/AdminFormPartialsViewTest.php` — adds coverage for the shared native-select usage in admin form partials.
- `tests/Feature/Feature/Admin/AdminModerationQueuesTest.php` — adds assertions that moderation cards show the related title names.
- `tests/Feature/Feature/Admin/MediaAssetUploadTest.php` — now verifies that the removed admin media and moderation controller routes stay unregistered.
- `tests/Feature/Feature/AwardNominationPageTest.php` — now guards nominee-people expectations behind remote table availability checks.
- `tests/Feature/Feature/AwardsArchiveExperienceTest.php` — now guards archive nominee-people expectations behind remote table availability checks.
- `tests/Feature/Feature/Database/ImdbSourceDatabaseMigrationCommandProgressTest.php` — verifies that the new import command prints useful progress metrics.
- `tests/Feature/Feature/Database/ImdbSourceDatabaseMigrationCommandTest.php` — verifies that the new import command can resume from a saved cursor state.
- `tests/Feature/Feature/Livewire/PeopleBrowserTest.php` — skips unavailable remote-table cases and relaxes popularity assertions to fit real catalog data better.
- `tests/Feature/Feature/Livewire/TitleInteractionTest.php` — adds coverage proving suspended users cannot mutate title tracking widgets.
- `tests/Feature/Feature/PeopleDetailExperienceTest.php` — skips the person detail test when remote people profiles are unavailable.
- `tests/Feature/Feature/PortalRouteRegistrationTest.php` — now verifies that the removed admin mutation routes are not registered.
- `tests/Feature/Feature/Search/GlobalSearchLivewireTest.php` — reuses the public people query action and skips ranked-person expectations when catalog tables are missing.
- `tests/Feature/Feature/Search/SearchExperienceTest.php` — switches search assertions over to Livewire view data and adds safer people-ranking guards.
- `tests/Unit/Database/RunDatabaseMigrationTransactionActionTest.php` — verifies retry behavior for retryable and non-retryable database exceptions.
- `changelog/changelog-2026-04-12.md` — rewrites today’s changelog entry so it matches the actual source-import, admin-cleanup, and catalog-fallback changes.

### Why This Matters
This update makes the project much more honest about the data it has and much less fragile when the remote IMDb schema is incomplete or temporarily unreachable. At the same time, it gives the team a safer way to seed shared tables from another database without restarting long imports from scratch, which should save a lot of frustration during local setup, recovery work, and environment refreshes.

---
