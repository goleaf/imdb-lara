# IMDb Audit And Plan

Generated: 2026-04-01

## Repository Audit Summary

- Framework: Laravel `12.56.0` on PHP `8.5`.
- Reactive UI: Livewire `4.2.3`, class-based components in `app/Livewire/*` with paired Blade views in `resources/views/livewire/*`.
- Auth: first-party session auth. Login and registration are Livewire forms, guest/auth redirects are configured in `bootstrap/app.php`, and logout is handled by a standard controller action.
- Users and permissions: `users` includes `username`, `bio`, `avatar_path`, `role`, and `status`. Access control uses `UserRole` and `UserStatus` enums, `active` and `admin` middleware aliases, plus model policies.
- Layout and visual system: the app uses Sheaf UI Blade components, Tailwind CSS 4, and local theme tokens in `resources/css/theme.css`. Shared layouts live in `resources/views/layouts/*`.
- Admin area: there is already a first-party `/admin` area for dashboard, titles, reviews, and reports. Filament is not installed; current admin conventions are Laravel + Blade + Livewire.
- Tests: PHPUnit 11 feature tests are already in place for auth, public browse pages, Livewire interactions, lists, moderation, SEO, schema, and seeders.
- Database structure: the schema already covers titles, people, companies, credits, genres, media assets, ratings, reviews, votes, title statistics, lists, list items, reports, and moderation actions.
- Factories and seeders: factories exist for the full catalog and community domain. `DemoCatalogSeeder` provisions representative data.
- Media support: Laravel filesystem support is available through standard disks and the public storage link, but the catalog currently stores media as URL-backed `media_assets` records rather than uploaded files.
- Search support: search and discovery are database-backed through `App\Actions\Search\BuildDiscoveryQueryAction`. There is no Scout or external search engine integration.

## What Can Be Reused Directly

- The existing layout shells for public, account, and admin surfaces.
- The Sheaf UI component library for cards, fields, buttons, nav, badges, breadcrumbs, toasts, modals, tabs, and sidebar primitives.
- Current Livewire conventions: class-based components, server-side validation, and minimal JavaScript.
- The route structure, middleware aliases, auth flow, policies, and user role/status enums.
- The current catalog schema, factories, seeders, and SEO endpoints.
- The database-backed discovery query and the existing public/admin controllers.

## Architectural Gaps

- Media is URL-based today. A production catalog still needs a managed upload pipeline, storage strategy, and admin upload tooling.
- Search is Eloquent-backed only. That is fine for the current seed/demo scale, but a larger catalog may need search indexing later.
- Admin pages are browse/moderation oriented. Full editorial CRUD workflows, relationship editors, and media management still need expansion.
- Aggregate data existed, but lifecycle-driven consistency was incomplete. Phase 1 in this pass hardens that with observers and tests.
- There is no external ingestion/import pipeline yet for large title and people datasets.

## Phased Implementation Plan

### Phase 1: Foundations And Schema

- Harden the current foundation layer: schema validation, seeded baseline data, enum-backed user roles/statuses, and aggregate consistency.
- Register model observers so ratings, reviews, and watchlist items automatically refresh `title_statistics`.
- Keep factories and seeders aligned with the current schema and ensure the foundation test suite stays green.

### Phase 2: Public Browsing

- Expand the home, discover, and browse surfaces using the current public layout and title/person cards.
- Add richer browse filters, pagination tuning, and featured collections without leaving the existing visual system.

### Phase 3: Title Pages

- Continue strengthening title detail pages with richer metadata, credits grouping, media galleries, related titles, and review/rating summaries.
- Add admin editing support for title metadata and title-linked assets.

### Phase 4: People Pages

- Extend people detail pages with better known-for ordering, role grouping, biography editing, and credit filters.
- Add admin editing flows for talent metadata and media.

### Phase 5: Ratings And Reviews

- Keep ratings and reviews fully policy-protected and consistent with title statistics.
- Add stronger moderation states, review surfacing rules, and staff review workflows where needed.

### Phase 6: Watchlists And Lists

- Build on the existing watchlist and custom list support with ordering, editing, notes management, and improved account workflows.
- Add richer list privacy and curator-facing list presentation.

### Phase 7: Search And Discovery

- Extend the current Eloquent discovery pipeline with more facets, better sorting, and scalable indexing preparation.
- Keep URLs SEO-friendly and preserve the current Livewire-driven filter UX.

### Phase 8: Admin CMS

- Grow the existing `/admin` area into a fuller CMS for titles, people, credits, companies, genres, lists, reviews, and media assets.
- Reuse the current admin shell and route structure instead of adding a parallel system.

### Phase 9: Moderation

- Expand reporting, moderation actions, and reviewer tooling for reviews, lists, and future user-generated content.
- Preserve the current report and review queues while adding clearer resolution workflows.

### Phase 10: SEO, Performance, And Testing

- Continue improving sitemap coverage, canonical/meta handling, eager loading, cached aggregates, and test depth.
- Add regression coverage for admin workflows, search filters, SEO output, and any future media upload pipeline.

## Phase 1 Work Started In This Pass

- Added observer-driven title statistic refreshes for ratings, reviews, and watchlist items.
- Added focused foundation tests to prove aggregate consistency.
- Captured the current repository audit and phased plan in this document.
