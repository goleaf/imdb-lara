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

1. Install PHP and Node dependencies.

```bash
composer install
npm install
```

2. Copy the environment file and generate an application key.

```bash
cp .env.example .env
php artisan key:generate
```

3. Run the database migrations and seed the demo catalog.

```bash
php artisan migrate --seed
```

4. Start the application and asset pipeline.

```bash
composer run dev
```

If you prefer separate processes:

```bash
php artisan serve
npm run dev
```

## Demo Data

The default database seeder runs `Database\\Seeders\\DemoCatalogSeeder`, which creates:

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
- `/tv`
- `/people`
- `/search`
- `/titles/{slug}`
- `/people/{slug}`
- `/u/{username}`
- `/u/{username}/lists/{slug}`

Account surface:

- `/account/dashboard`
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

- homepage rails for spotlight, trending, top rated, trailers, reviews, public lists, genres, and years
- advanced search with grouped results for titles, people, and public lists
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
- major Livewire browse/search/profile surfaces use eager loading and paginated queries
- public and admin pages both render through the shared `resources/views/layouts/app.blade.php` shell

## Testing

Run the formatter:

```bash
vendor/bin/pint --dirty --format agent
```

Run the full suite:

```bash
php artisan test --compact
```

## Repository Notes

- The original audit and phased implementation plan lives in [docs/imdb-audit-and-plan.md](docs/imdb-audit-and-plan.md).
- The app intentionally uses first-party Laravel + Livewire patterns instead of an SPA architecture.
- Media uploads rely on Laravel's `public` disk. Run `php artisan storage:link` if you need file-backed images locally.
