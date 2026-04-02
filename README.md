# Screenbase

Screenbase is a server-rendered IMDb-style web platform built with Laravel 12, Livewire 4, Blade, and the existing Sheaf/Tailwind visual system already present in this repository.

It includes:

- public browsing for movies, series, people, genres, years, trending, rankings, trailers, and reviews
- rich title, season, episode, and person detail pages
- ratings, reviews, helpful voting, reporting, watchlists, and custom lists
- search with grouped results and advanced title filters
- public profiles and private account dashboards
- an admin CMS for catalog, media, and moderation workflows
- sitemap and SEO metadata foundations for major public routes

## Stack

- PHP 8.5
- Laravel 12
- Livewire 4
- Blade only for the frontend
- SQLite by default for local development
- Tailwind 4 and the repository's existing Sheaf UI component system

## Local Setup

1. Run the baseline setup script.

```bash
composer run setup
```

This installs dependencies, prepares `.env`, generates the app key, runs migrations, and builds frontend assets.

2. Seed the demo catalog.

```bash
php artisan db:seed --force
```

3. Start the application.

```bash
composer run dev
```

If you prefer separate processes:

```bash
php artisan serve
npm run dev
```

## Default Local Configuration

The repository defaults to an SQLite-first local setup:

- `DB_CONNECTION=sqlite`
- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`

If the SQLite file does not exist yet, create it before migrating:

```bash
touch database/database.sqlite
```

Database-backed cache, session, and queue tables are created by the standard migrations.

## Demo Data

`Database\\Seeders\\DemoCatalogSeeder` creates:

- a production-style sample title/person catalog
- demo seasons and episodes
- reviews, ratings, lists, reports, and contributions
- media assets for posters, backdrops, galleries, and trailers
- privileged accounts for admin workflows

Seeded accounts use the default factory password: `password`

Suggested seeded users:

- `superadmin@example.com`
- `admin@example.com`
- `editor@example.com`
- `moderator@example.com`
- `contributor@example.com`
- `member@example.com`

## Main Routes

Public surface:

- `/`
- `/discover`
- `/movies`
- `/tv-shows`
- `/people`
- `/search`
- `/titles/{slug}`
- `/titles/{slug}/cast`
- `/titles/{slug}/media`
- `/titles/{slug}/metadata`
- `/titles/{slug}/box-office`
- `/titles/{slug}/trivia`
- `/titles/{slug}/parents-guide`
- `/people/{slug}`
- `/awards`
- `/u/{username}`
- `/u/{username}/lists/{slug}`

Account surface:

- `/account`
- `/account/watchlist`
- `/account/lists`
- `/account/settings`

Admin surface:

- `/admin`
- `/admin/titles`
- `/admin/people`
- `/admin/genres`
- `/admin/media-assets`
- `/admin/reviews`
- `/admin/reports`
- `/admin/contributions`

## Feature Overview

### Public product

- homepage rails for spotlight, trending, top rated, TV discovery, people, trailers, reviews, awards, keywords, genres, and charts
- advanced search with grouped results for titles and people plus advanced title filters
- title pages with ratings, reviews, watch-state, custom-list management, awards, media, related titles, and full cast
- TV hierarchy with series, seasons, episodes, episode ratings, and watched progress
- people pages with known-for titles, grouped filmography, collaborators, awards, and galleries

### Authenticated member features

- one rating per user per title with aggregate recalculation
- primary review workflow with spoiler flags, moderation status, helpful votes, and report actions
- private watchlist with public visibility option
- custom lists with public, private, and unlisted visibility
- public profile controls for ratings and watchlist visibility

### Admin and moderation

- CRUD for titles, people, genres, credits, seasons, episodes, and media
- review and report queues for moderators
- contribution queue for proposed catalog edits
- role-aware access for superadmin, admin, editor, moderator, contributor, and regular user

## Production-Quality Notes

- shared catalog filters and homepage datasets now use short-lived cache reads for lower repeated query cost
- non-production environments prevent lazy loading to catch N+1 regressions early
- awards, discovery, homepage, browse, search, and profile surfaces use eager loading, stable pagination, and lightweight cached reads where the data is low-risk
- public metadata is centralized through `app/Actions/Seo/PageSeoData.php` and the shared page-shell pipeline for canonical tags, Open Graph tags, breadcrumbs, and pagination-aware titles
- search, auth, account, and other non-index surfaces can be controlled through the same robots metadata hooks without introducing a third-party SEO package
- public and admin pages both render through the shared `resources/views/layouts/app.blade.php` shell
- admin, account, and public navigation all use the same shell styling primitives and shared layout conventions

## Testing

Run the formatter:

```bash
vendor/bin/pint --dirty --format agent
```

Run the full suite:

```bash
php artisan test --compact
```

Build production assets:

```bash
npm run build
```

## Repository Notes

- The original audit and phased implementation plan lives in [docs/imdb-audit-and-plan.md](docs/imdb-audit-and-plan.md).
- The app intentionally uses first-party Laravel + Livewire patterns instead of an SPA architecture.
- Media uploads rely on Laravel's `public` disk. Run `php artisan storage:link` if you need file-backed images locally.
