## 🗓️ 2026-04-12 — Catalog Discovery and People Pages Behave Better in MySQL Mode

Hey! Here is what changed today in this project:

### What's New
The catalog-only MySQL path now understands more of the remote IMDb-style data it is already receiving. People pages can surface known-for titles from the dedicated bridge table, popularity ranking data is available on person records, and trailer listings can fall back to catalog video relations when local media assets are not the source of truth. Demo data also got a friendlier face, with named sample records and real poster and headshot media so local browsing feels closer to the real product.

### What Was Improved
Discovery and browse coverage now leans on action output and rendered page contracts instead of brittle Livewire internals, which should make the test suite far less noisy during UI refactors. Title, person, credit, genre, Livewire filmography option formatting, and media helpers were tightened so catalog-only mode keeps important metadata like provider keys, publish dates, rating formats, and route binding behavior intact. Interest category sorting and matched-interest counts also now read from real catalog-aware columns instead of placeholders, so the public catalog should rank content more honestly. Pagination rendering also has direct regression coverage now, which should make shared button markup changes safer.

### What Was Removed or Cleaned Up
A large amount of test-only mocking and fragile DOM-coupled assertions was removed from the discovery and public browse tests. The result is a slimmer test surface that checks user-visible behavior and action payloads without overfitting to Livewire markup that is expected to evolve.

### Files That Changed
- `app/Actions/Catalog/BuildPersonFilmographyQueryAction.php` — made person filmography queries adapt to catalog-only schemas, including remote popularity ranking and profession fallback behavior.
- `app/Actions/Catalog/LoadInterestCategoryDetailsAction.php` — switched interest category ordering to shared catalog-aware title columns.
- `app/Actions/Home/GetLatestTrailerTitlesAction.php` — taught the trailers query to use catalog video relations in MySQL catalog mode and keep local media assets as the fallback.
- `app/Livewire/People/FilmographyPanel.php` — simplified profession option key generation in the filmography panel.
- `app/Models/CatalogMediaAsset.php` — added provider, provider key, and publish date support so normalized media objects keep more metadata.
- `app/Models/Credit.php` — added typed accessors for remote credit fields like profession ids, episode ids, and credited-as values.
- `app/Models/Genre.php` — improved genre route binding so catalog-only slugs and numeric id-style URLs both resolve cleanly.
- `app/Models/MediaAsset.php` — added a reusable conversion helper for turning local media assets into catalog media assets.
- `app/Models/Person.php` — expanded catalog-only person behavior with popularity ranking, known-for bridge support, better route binding, publish detection, and shared media conversion.
- `app/Models/Title.php` — replaced placeholder matched-interest counts with real counts and preserved richer media metadata during catalog conversion.
- `app/Models/TitleStatistic.php` — normalized display ratings to consistent two-decimal strings.
- `database/seeders/DemoCatalogSeeder.php` — added friendlier named demo records and seeded poster and headshot assets for local browsing.
- `tests/Concerns/BootstrapsImdbMysqlSqlite.php` — added the known-for bridge table to the SQLite-backed IMDb test schema.
- `tests/Feature/Feature/DiscoverPageTest.php` — removed deep action mocking and asserted the real discover page shell instead.
- `tests/Feature/Feature/Lists/CustomListFlowTest.php` — tightened the public profile link assertion so it only counts actual links.
- `tests/Feature/Feature/Livewire/DiscoveryFiltersTest.php` — rewrote discovery coverage around action payloads and lazy-shell contracts instead of fragile component markup.
- `tests/Feature/Feature/PublicBrowsePagesTest.php` — split the big browse smoke test into smaller focused checks and updated lazy-loading expectations.
- `tests/Feature/Feature/Ui/PaginationRenderingTest.php` — added regression coverage for shared pagination links and Livewire island pagination buttons.
- `tests/TestCase.php` — now skips legacy import feature tests automatically when the legacy import pipeline is disabled.
- `tests/Unit/Models/PersonTest.php` — added coverage for partially loaded person media assets still producing a usable preferred headshot.
- `tests/Unit/Models/TitleTest.php` — added coverage for partially loaded title media assets still producing a usable preferred poster.
- `changelog/changelog-2026-04-12.md` — added this human-readable summary of the day’s work.

### Why This Matters
This update makes the catalog-only path more trustworthy and easier to maintain. Public pages and background helpers now preserve more of the remote catalog’s shape, while the tests check the behavior that matters without tying the suite to brittle implementation details. That combination should make future catalog work safer, especially in the MySQL-backed mode this project is actively pushing toward.

---
