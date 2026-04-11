# IMDb Importer Analysis

Generated: 2026-04-11

## Purpose

This document is the canonical context for future IMDb importer work in this repository.

Constraints already agreed:

- work on `main` only
- do not create additional branches
- the future importer should expose one Laravel Artisan command only
- that command should not require arguments or options
- implementation must reuse the existing MySQL catalog and existing Laravel code where possible

## Current Application Mode

The repository is currently configured as a catalog reader backed by the existing MySQL IMDb-style schema, not as an active importer.

Key facts:

- `config/screenbase.php`
  - `catalog_only` is `true`
  - `legacy_import_pipeline_enabled` is `false`
- `config/database.php`
  - default connection is still `sqlite`
  - a separate `imdb_mysql` connection is defined for the existing catalog
- `config/services.php`
  - IMDb REST base URL defaults to `https://api.imdbapi.dev`
  - optional GraphQL URL defaults to `https://graph.imdbapi.dev/v1`
  - HTTP caching, inter-request delay, and title/name concurrency are already configurable

Practical implication:

- the public application already reads directly from `imdb_mysql`
- the older import pipeline still exists in code, but it is intentionally blocked by configuration

## Branch And Working Tree State

Observed during analysis:

- current branch: `main`
- the working tree already contains many uncommitted user changes

Implication:

- future importer work on `main` must avoid reverting unrelated changes
- documentation should be treated as the stable source of truth while code is still moving

## Real Runtime Architecture

The active product surface is not the same as the older local-schema plan described in some older docs and migrations.

Current runtime architecture:

- Laravel 12.56.0
- Livewire 4.2.3
- Blade server rendering
- no Filament package installed
- public pages are Livewire page classes in `app/Livewire/Pages/Public/*`
- route registration is in `bootstrap/app.php` and `routes/web.php`
- the app reads catalog entities from the `imdb_mysql` connection

Important files:

- `bootstrap/app.php`
- `routes/web.php`
- `config/database.php`
- `config/services.php`
- `config/screenbase.php`
- `app/Providers/AppServiceProvider.php`

## Active Catalog Model Layer

The models actually used by the running catalog are mapped to the remote MySQL schema.

Primary active models:

- `app/Models/Title.php`
  - connection: `imdb_mysql`
  - table: `movies`
- `app/Models/Person.php`
  - connection: `imdb_mysql`
  - table: `name_basics`
- `app/Models/Credit.php`
  - connection: `imdb_mysql`
  - table: `name_credits`
- `app/Models/TitleStatistic.php`
  - connection: `imdb_mysql`
  - table: `movie_ratings`
- `app/Models/ImdbModel.php`
  - shared base model for generated remote-schema models

There is also a generated-schema layer:

- `app/Models/Movie.php`
- `app/Models/NameBasic.php`
- many other generated `ImdbModel` descendants

Important architectural note:

- the codebase currently contains two representations of the remote catalog:
  - custom app-facing models such as `Title` and `Person`
  - generated raw-schema models such as `Movie` and `NameBasic`
- future importer work should pick one canonical write/read path and avoid mixing both styles inside the same workflow unless there is a clear boundary

## Existing Import Command Layer

There are only two command classes in `app/Console/Commands`:

- `imdb:import-titles-frontier`
- `imdb:generate-schema-models`

Files:

- `app/Console/Commands/ImdbImportTitlesFrontierCommand.php`
- `app/Console/Commands/GenerateImdbSchemaModelsCommand.php`

### `imdb:import-titles-frontier`

Current behavior:

- already has no arguments and no options
- runs up to 5 stabilization passes
- bootstraps:
  - title frontier
  - interest frontier
  - star meter frontier
- delegates real work to `App\Actions\Import\CrawlImdbGraphAction`
- emits verbose live diagnostics

Current blocker:

- `App\Actions\Import\EnsureLegacyImportPipelineIsEnabledAction` throws unless `screenbase.legacy_import_pipeline_enabled` is `true`
- `tests/Feature/Feature/Import/CatalogOnlyImportPipelineGuardTest.php` explicitly asserts that this command must currently fail in catalog-only mode

### `imdb:generate-schema-models`

Purpose:

- introspects a database connection and generates Eloquent models for its tables
- defaults to `--connection=imdb_mysql`

This is documentation-relevant because:

- it explains why the repository already contains a large generated remote-schema model set
- it confirms the project is already designed around an existing external MySQL schema

## Existing Import Action Graph

The legacy importer is not a small command. It is already a layered pipeline.

Main entry:

- `app/Actions/Import/CrawlImdbGraphAction.php`

Core responsibilities:

- frontier pagination
- node queueing
- recursive discovery of connected titles, names, and interests
- artifact storage under a storage root
- run summary generation
- final crawl report generation

Important downloader/importer actions:

- `DownloadImdbTitlePayloadAction`
- `DownloadImdbNamePayloadAction`
- `DownloadImdbInterestPayloadAction`
- `ImportImdbTitlePayloadAction`
- `ImportImdbNamePayloadAction`
- `FetchImdbJsonAction`
- `FetchImdbGraphqlAction`
- `ResolveImdbApiUrlAction`
- `BuildCompactImdbPayloadAction`
- `WriteImdbEndpointImportReportAction`
- `WriteImdbTitleVerificationReportAction`
- `WriteImdbNameVerificationReportAction`
- `LoadImdbImportReportAction`

### Title pipeline

`DownloadImdbTitlePayloadAction` currently builds a bundle per title with:

- title details
- credits
- release dates
- akas
- seasons
- episodes
- images
- videos
- award nominations
- parents guide
- certificates
- company credits
- box office
- optional GraphQL-enriched credits/certificates

It writes:

- one bundle JSON file per title
- endpoint artifact JSON files
- a manifest
- an `imdb_title_imports` tracking row when that table exists

### Name pipeline

`DownloadImdbNamePayloadAction` builds a per-name bundle with:

- details
- images
- filmography
- relationships
- trivia

### Interest pipeline

`DownloadImdbInterestPayloadAction` stores one interest bundle plus manifest.

### Import normalization

`ImportImdbTitlePayloadAction` and `ImportImdbNamePayloadAction` are the normalizers that turn downloaded payloads into database rows.

Important fact:

- these actions are still protected by the legacy-pipeline guard
- in the current repo state, they are not allowed to run in normal catalog-only mode

## Existing Verification And Reporting

The old importer is already built with verification/reporting hooks.

Artifacts produced by the existing pipeline:

- `verification.json` per imported title
- `verification.json` per imported name
- endpoint-level import reports
- crawl run reports
- manifest files for stored artifact bundles

The verification actions compare:

- source counts
- downloaded counts
- stored payload counts
- normalized DB counts
- relation integrity

This is valuable for the future one-command importer because:

- verification already exists and should be preserved instead of re-invented
- the future command can stay argument-free if verification/report locations remain config-driven

## Existing MySQL Catalog Assumption In Tests

The running application is already validated against the remote-style MySQL schema.

Important tests and helpers:

- `tests/Feature/Feature/PublicMysqlCatalogSmokeTest.php`
- `tests/Concerns/InteractsWithRemoteCatalog.php`
- `tests/Concerns/BootstrapsImdbMysqlSqlite.php`
- `tests/Feature/Feature/Database/GenerateImdbSchemaModelsCommandTest.php`

What these prove:

- public pages query `movies`, `name_basics`, `name_credits`, `movie_ratings`, and related remote tables
- tests can remap `imdb_mysql` to SQLite for isolated schema verification
- generated model support for legacy catalog tables is already expected

Implication for the future importer:

- the safest target schema is the existing `imdb_mysql` catalog schema
- importer design should align with the same table names the public app already reads

## IMDb API Surface From Swagger

Source analyzed:

- `https://imdbapi.dev/imdbapi.swagger.yaml`

Observed metadata:

- title: `IMDbAPI`
- version: `2.7.12`
- host: `api.imdbapi.dev`
- path count: `25`

### Swagger endpoint inventory

Title endpoints:

- `GET /titles`
- `GET /titles/{titleId}`
- `GET /titles:batchGet`
- `GET /titles/{titleId}/credits`
- `GET /titles/{titleId}/releaseDates`
- `GET /titles/{titleId}/akas`
- `GET /titles/{titleId}/seasons`
- `GET /titles/{titleId}/episodes`
- `GET /titles/{titleId}/images`
- `GET /titles/{titleId}/videos`
- `GET /titles/{titleId}/awardNominations`
- `GET /titles/{titleId}/parentsGuide`
- `GET /titles/{titleId}/certificates`
- `GET /titles/{titleId}/companyCredits`
- `GET /titles/{titleId}/boxOffice`

Search/chart endpoints:

- `GET /search/titles`
- `GET /chart/starmeter`

Name endpoints:

- `GET /names/{nameId}`
- `GET /names/{nameId}/images`
- `GET /names/{nameId}/filmography`
- `GET /names/{nameId}/relationships`
- `GET /names/{nameId}/trivia`
- `GET /names:batchGet`

Interest endpoints:

- `GET /interests`
- `GET /interests/{interestId}`

### Swagger vs current config overlap

Current `config/services.php` already defines endpoints for:

- titles frontier
- title details
- title credits
- release dates
- akas
- seasons
- episodes
- images
- videos
- award nominations
- parents guide
- certificates
- company credits
- box office
- search titles
- interests frontier
- interest details
- name details
- name images
- name relationships
- name trivia
- name filmography
- star meter chart

Swagger endpoints not currently represented in `config/services.php`:

- `/titles:batchGet`
- `/names:batchGet`

Separate non-swagger API already used in code:

- GraphQL endpoint at `https://graph.imdbapi.dev/v1`

## Import Test Surface

The repository already has broad importer-oriented tests, even though the importer is disabled in the current mode.

Notable coverage:

- frontier crawl command
- interest frontier
- per-title endpoint bundles
- per-name endpoint bundles
- title verification report
- name verification report
- JSON fetch behavior
- GraphQL title-core preload behavior
- schema model generation

Importer-related test files currently present:

- `tests/Feature/Feature/Feature/Import/ImdbGraphCrawlCommandTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbImportStarMeterChartCommandTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbImportTitlesFrontierCommandTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbInterestEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbInterestsFrontierEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbNameDetailsEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbNameFilmographyEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbNameImagesEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbNameRelationshipsEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbNameTriviaEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbNameVerificationReportTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbOptimizeStorageCommandTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbSearchTitlesCommandTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleAkasEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleAwardNominationsEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleBoxOfficeEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleCertificatesEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleCompanyCreditsEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleDownloadCommandTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleEpisodesEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleImagesEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleImportCommandTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleParentsGuideEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleSeasonsEndpointTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleVerificationReportTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbTitleVideosEndpointTest.php`
- `tests/Feature/Feature/Import/CatalogOnlyImportPipelineGuardTest.php`
- `tests/Unit/Actions/Import/FetchImdbGraphqlActionTest.php`
- `tests/Unit/Actions/Import/FetchImdbJsonActionTest.php`

## Important Drift And Mismatch Points

These are the most important facts to preserve before implementation starts.

### 1. Catalog-only mode conflicts with importer mode

Current code explicitly says:

- the app now runs directly against the existing MySQL catalog
- the old import pipeline is disabled on purpose

Future importer work must decide whether to:

- replace the current guard and restore importer mode, or
- keep catalog-only mode and add a new importer path that still writes into the same MySQL schema

### 2. Remote schema is the real production-facing schema

The public app reads from:

- `movies`
- `name_basics`
- `name_credits`
- `movie_ratings`
- many related remote tables

This matters more than the older local migrations for `titles`, `people`, `credits`, and `title_statistics`.

### 3. The codebase contains legacy local-schema artifacts

Examples:

- local migrations for `titles`, `people`, `credits`, `title_statistics`
- import code and verification code still contain assumptions from the earlier local-table approach

Future importer work should avoid accidentally re-targeting obsolete local tables if the application has already standardized on `imdb_mysql`.

### 4. Two remote model layers coexist

Examples:

- `Title` and `Movie`
- `Person` and `NameBasic`

Recommendation:

- future importer work should document which layer is canonical before changing write paths

### 5. Swagger includes batch endpoints that current config ignores

Missing from current endpoint config:

- `titles:batchGet`
- `names:batchGet`

These may become useful later for importer efficiency, but they are not currently integrated.

## Most Likely Direction For The Future One-Command Importer

This is not implementation yet. It is the most defensible direction based on the current code.

Recommended shape:

- keep exactly one public importer command
- keep it argument-free and option-free
- make behavior fully config-driven
- write into the existing `imdb_mysql` schema that the application already reads
- reuse the current downloader, JSON fetch, rate limiting, artifact storage, and verification/report pieces
- either repurpose `imdb:import-titles-frontier` or replace it with a new single canonical importer command

Recommended non-goals for the next step:

- do not add multiple specialized import commands
- do not introduce a second parallel schema
- do not split behavior across branch-specific experiments

## Proposed Canonical File Map For Future Work

Use these files first when implementing the importer:

- `docs/imdb-importer-analysis.md`
- `config/database.php`
- `config/services.php`
- `config/screenbase.php`
- `app/Console/Commands/ImdbImportTitlesFrontierCommand.php`
- `app/Actions/Import/CrawlImdbGraphAction.php`
- `app/Actions/Import/DownloadImdbTitlePayloadAction.php`
- `app/Actions/Import/DownloadImdbNamePayloadAction.php`
- `app/Actions/Import/DownloadImdbInterestPayloadAction.php`
- `app/Actions/Import/ImportImdbTitlePayloadAction.php`
- `app/Actions/Import/ImportImdbNamePayloadAction.php`
- `app/Actions/Import/FetchImdbJsonAction.php`
- `app/Actions/Import/FetchImdbGraphqlAction.php`
- `app/Models/Title.php`
- `app/Models/Person.php`
- `app/Models/Credit.php`
- `app/Models/TitleStatistic.php`
- `tests/Feature/Feature/Import/CatalogOnlyImportPipelineGuardTest.php`
- `tests/Feature/Feature/Feature/Import/ImdbImportTitlesFrontierCommandTest.php`
- `tests/Feature/Feature/PublicMysqlCatalogSmokeTest.php`
- `tests/Concerns/InteractsWithRemoteCatalog.php`

## Decision Summary

If future work follows this document, the next implementation should be based on these facts:

- the app already trusts the existing MySQL catalog as its source of truth
- a single no-argument importer command fits the current command style
- the old importer already provides useful downloading, pagination, caching, GraphQL enrichment, and verification machinery
- the main unresolved design question is not how to call the API, but how to reconcile catalog-only mode with the desire to re-enable importing into the existing MySQL schema cleanly
