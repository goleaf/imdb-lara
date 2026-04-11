## 🗓️ 2026-04-11 — Livewire moderation lands while the catalog admin turns read only

Hey! Here is what changed today in this project:

### What's New
The moderation queues for reviews, reports, and contributions now run through dedicated Livewire cards instead of plain patch forms. Moderators can update status inline, see confirmation feedback immediately, and keep the queue fresh without falling back to the older route-driven flow.

The public changes page also moved to a file-based publishing system. New release notes can now live as dated markdown files inside `changelog/`, and the site will sort, summarize, and render them automatically as a newest-first editorial stream.

### What Was Improved
Catalog-only mode is much clearer across admin create and edit pages now. Instead of showing forms that cannot safely write to the remote-backed catalog, the UI explains that writes are paused, keeps the Livewire shell active, and lets admins inspect data without pretending those actions still work.

Livewire forms were tightened up as well: auth, list creation, list reporting, review reporting, ratings, and spoiler toggles now use clearer validation hooks, calmer blur-based field updates, and consistent checkbox components. Public list pages were also improved so previews, counts, and pagination ignore unpublished titles instead of leaking hidden catalog entries into the public UI.

### What Was Removed or Cleaned Up
The old direct mutation routes for moderation, catalog editing, and logout were stripped out where Livewire now owns the interaction or where writes should stay paused entirely. Several duplicated eager-load definitions were pulled into shared model helpers so the moderation queues stop repeating the same relation wiring in multiple places.

The oversized demo catalog seeder was cut back to lightweight user setup, and a few core models were moved away from remote IMDb-only table assumptions toward the local app schema. That cleanup reduces fake seed data, makes tests and factories more honest, and keeps this project aligned with the current catalog architecture.

### Files That Changed
- `app/Actions/Admin/BuildAdminReportsIndexQueryAction.php` — points the reports queue at the shared report moderation relation map.
- `app/Actions/Admin/BuildAdminReviewsIndexQueryAction.php` — points the reviews queue at the shared review moderation relation map.
- `app/Actions/Admin/UpdateReportStatusAction.php` — reloads reports through the shared moderation relation map before saving.
- `app/Actions/Content/LoadChangelogPageAction.php` — reads dated markdown files, extracts titles and dates, and builds richer release-page metadata.
- `app/Actions/Lists/BuildPublicListsIndexQueryAction.php` — filters unpublished titles out of public list previews and list-size counts.
- `app/Actions/Lists/BuildUserListItemsQueryAction.php` — filters unpublished titles out of per-list item queries.
- `app/Actions/Lists/LoadPublicUserListAction.php` — counts only published titles on public list pages.
- `app/Actions/Titles/RefreshTitleStatisticsAction.php` — reuses or initializes the local statistics record instead of recalculating remote-backed numbers.
- `app/Livewire/Admin/ContributionModerationCard.php` — adds an inline Livewire card for contribution moderation.
- `app/Livewire/Admin/ReportModerationCard.php` — adds an inline Livewire card for report moderation and content actions.
- `app/Livewire/Admin/ReviewModerationCard.php` — adds an inline Livewire card for review moderation.
- `app/Livewire/Auth/LogoutButton.php` — adds a shared Livewire logout action for buttons and dropdown items.
- `app/Livewire/Forms/Auth/LoginUserForm.php` — moves login validation onto Livewire attributes for field-level feedback.
- `app/Livewire/Forms/Auth/RegisterUserForm.php` — adds attribute-based validation, including required password confirmation.
- `app/Livewire/Forms/Lists/CreateUserListForm.php` — adds attribute-based validation for list creation fields.
- `app/Livewire/Forms/Reviews/ReportReviewForm.php` — adds attribute-based validation for report fields.
- `app/Livewire/Forms/Titles/RatingForm.php` — adds attribute-based validation for rating input.
- `app/Livewire/Pages/Admin/ContributionsPage.php` — refreshes the queue after inline moderation updates.
- `app/Livewire/Pages/Admin/CreditsPage.php` — serves read-only credit pages in catalog-only mode.
- `app/Livewire/Pages/Admin/EpisodesPage.php` — serves read-only episode pages and loads local season and title relations.
- `app/Livewire/Pages/Admin/GenresPage.php` — serves read-only genre edit pages when writes are paused.
- `app/Livewire/Pages/Admin/MediaAssetsPage.php` — serves read-only media asset pages when writes are paused.
- `app/Livewire/Pages/Admin/PeoplePage.php` — serves read-only people pages in catalog-only mode.
- `app/Livewire/Pages/Admin/ReportsPage.php` — refreshes the queue after inline moderation updates.
- `app/Livewire/Pages/Admin/ReviewsPage.php` — moves queue filters into URL-backed Livewire state and refreshes after moderation.
- `app/Livewire/Pages/Admin/SeasonsPage.php` — serves read-only season pages and loads local series relations.
- `app/Livewire/Pages/Admin/TitlesPage.php` — serves read-only title pages in catalog-only mode.
- `app/Livewire/Pages/Concerns/RendersPageView.php` — injects catalog-only state into admin views from one shared place.
- `app/Livewire/Pages/Public/ChangesPage.php` — adds a Livewire wrapper for the public changes page and its SEO data.
- `app/Models/Episode.php` — reshapes episodes around local title, series, season, and credit relations.
- `app/Models/Genre.php` — converts genres to the local app model with factories, stored slugs, descriptions, and slug-or-id route binding.
- `app/Models/PersonProfession.php` — converts person professions to the local app model with simpler fields and factory support.
- `app/Models/Report.php` — adds shared relation helpers for report moderation queues.
- `app/Models/Review.php` — adds shared relation helpers for review moderation queues and reportable review loading.
- `app/Models/TitleStatistic.php` — converts title statistics to the local app model while keeping compatibility accessors.
- `database/seeders/DemoCatalogSeeder.php` — removes the giant fake catalog build and keeps lightweight user seeding.
- `resources/css/app.css` — refreshes the home hero media sizing and the public changes page timeline styling.
- `resources/views/admin/contributions/index.blade.php` — replaces inline patch forms with the Livewire contribution moderation card.
- `resources/views/admin/credits/create.blade.php` — swaps the create form for a paused-state panel in catalog-only mode.
- `resources/views/admin/credits/edit.blade.php` — swaps the edit form for a paused-state panel in catalog-only mode.
- `resources/views/admin/episodes/edit.blade.php` — swaps episode editing for a paused-state panel in catalog-only mode.
- `resources/views/admin/genres/create.blade.php` — swaps the create form for a paused-state panel in catalog-only mode.
- `resources/views/admin/genres/edit.blade.php` — swaps genre editing for a paused-state panel in catalog-only mode.
- `resources/views/admin/media-assets/edit.blade.php` — swaps asset editing for a paused-state panel in catalog-only mode.
- `resources/views/admin/media-assets/index.blade.php` — makes media assets inspect-only when catalog writes are paused.
- `resources/views/admin/people/create.blade.php` — swaps the create form for a paused-state panel in catalog-only mode.
- `resources/views/admin/people/edit.blade.php` — swaps person editing for a paused-state panel in catalog-only mode.
- `resources/views/admin/reports/index.blade.php` — replaces inline patch forms with the Livewire report moderation card.
- `resources/views/admin/reviews/index.blade.php` — replaces the old filter form and patch forms with Livewire filters and cards.
- `resources/views/admin/seasons/edit.blade.php` — swaps season editing for a paused-state panel in catalog-only mode.
- `resources/views/admin/titles/create.blade.php` — swaps the create form for a paused-state panel in catalog-only mode.
- `resources/views/admin/titles/edit.blade.php` — swaps title editing for a paused-state panel in catalog-only mode.
- `resources/views/changes/index.blade.php` — rebuilds the changes page markup around the new editorial stream.
- `resources/views/components/account/⚡profile-settings-panel.blade.php` — replaces a custom checkbox block with the shared checkbox card component.
- `resources/views/components/admin/catalog-write-disabled-panel.blade.php` — adds a reusable panel that explains paused catalog writes.
- `resources/views/home.blade.php` — adds hero media hooks and stable poster and placeholder wrappers.
- `resources/views/layouts/partials/account-sidebar.blade.php` — replaces the logout form with the shared Livewire logout button.
- `resources/views/layouts/partials/admin-sidebar.blade.php` — replaces the logout form with the shared Livewire logout button.
- `resources/views/layouts/partials/app-shell.blade.php` — replaces every logout form with the shared Livewire logout button.
- `resources/views/livewire/admin/contribution-moderation-card.blade.php` — adds the contribution moderation card markup.
- `resources/views/livewire/admin/report-moderation-card.blade.php` — adds the report moderation card markup.
- `resources/views/livewire/admin/review-moderation-card.blade.php` — adds the review moderation card markup.
- `resources/views/livewire/auth/login-form.blade.php` — moves login inputs to blur-driven field updates.
- `resources/views/livewire/auth/logout-button.blade.php` — adds shared logout button and dropdown-item rendering.
- `resources/views/livewire/auth/register-form.blade.php` — moves register inputs to blur-driven field updates.
- `resources/views/livewire/lists/create-list-form.blade.php` — moves list creation inputs to blur-driven validation.
- `resources/views/livewire/lists/report-list-form.blade.php` — moves list reporting details to blur-driven validation.
- `resources/views/livewire/reviews/report-review-form.blade.php` — moves review reporting details to blur-driven validation.
- `resources/views/livewire/titles/custom-list-picker.blade.php` — moves inline list creation fields to blur-driven validation.
- `resources/views/livewire/titles/rating-panel.blade.php` — moves score entry to blur-driven validation.
- `resources/views/livewire/titles/review-composer.blade.php` — upgrades the spoiler toggle to the shared checkbox component with helper copy.
- `routes/admin.php` — removes old mutation endpoints and keeps the admin area focused on Livewire pages and read-only shells.
- `routes/auth.php` — removes the dedicated POST logout route.
- `routes/web.php` — moves the public changes route from a closure to a Livewire page.
- `tests/Feature/Feature/Admin/AdminCatalogReadonlyPagesTest.php` — adds coverage for paused catalog screens and read-only media browsing.
- `tests/Feature/Feature/Admin/AdminModerationQueuesTest.php` — updates moderation coverage for Livewire queue cards and local-catalog helpers.
- `tests/Feature/Feature/Auth/AuthenticationFlowTest.php` — updates auth coverage for the Livewire logout flow and blur-validated forms.
- `tests/Feature/Feature/ChangelogPageTest.php` — updates the changes page assertions for the new editorial shell.
- `tests/Feature/Feature/Feature/Admin/AdminReviewModerationQueueTest.php` — updates review queue coverage for the new Livewire filter state.
- `tests/Feature/Feature/Feature/Livewire/ReviewComposerTest.php` — adds assertions for the new spoiler checkbox card copy.
- `tests/Feature/Feature/HomepageTest.php` — adds hero media assertions and makes mock genres route-safe with slugs.
- `tests/Feature/Feature/Lists/CustomListFlowTest.php` — adds live field-validation coverage for list creation.
- `tests/Feature/Feature/Lists/ListManagementTest.php` — adds coverage that public list previews, counts, and pagination ignore unpublished titles.
- `tests/Feature/Feature/Livewire/ProfileSettingsPanelTest.php` — adds assertions for the shared checkbox card on profile settings.
- `tests/Feature/Feature/Livewire/RatingPanelTest.php` — adds live field-validation coverage for score updates.
- `tests/Feature/Feature/Moderation/ListReportingFlowTest.php` — adds live field-validation coverage for list report details.
- `tests/Feature/Feature/Moderation/ReviewReportingFlowTest.php` — adds live field-validation coverage for review report details.
- `tests/Feature/Feature/PortalRouteRegistrationTest.php` — updates route expectations after removing logout and patch endpoints.
- `tests/Feature/Feature/PublicRouteArchitectureTest.php` — updates the public changes page assertion for the new shell.
- `tests/Unit/Actions/Content/LoadChangelogPageActionTest.php` — adds coverage for file-based changelog entries.
- `changelog/changelog-2026-04-11.md` — records this release in the new dated changelog format.

### Why This Matters
This update makes the app much more honest about what is editable, much more responsive for moderators, and much less likely to expose unpublished catalog data in public list surfaces. It also brings the project closer to a sustainable local-app architecture by replacing duplicated moderation logic, cleaning up remote-only model assumptions, and giving the team a simple release-note workflow that grows one dated markdown file at a time.

---
