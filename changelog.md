## 🗓️ 2026-04-11 — A public changes page, smarter breadcrumbs, and cleaner route boundaries

Hey! Here is what changed today in this project:

### What's New
Screenbase now has a public `Changes` page that renders straight from the root `changelog.md`, so release notes finally live inside the product instead of only in the repository. The public surface also now formally includes latest reviews, public lists, and public user pages, and the footer points people to the changelog so they can actually discover what shipped.

### What Was Improved
Breadcrumbs got much smarter: shared breadcrumb items can now infer the right icon from the label or URL, which removes a lot of repetitive view wiring while making navigation feel more polished. The long-form changelog page also received dedicated editorial styling, the public list detail page now links its owner breadcrumb back to the public profile route, and the auth entry screens now reuse the shared button component for their placeholder social controls. Account and admin shell data is also cleaner now, with explicit portal-shell flags and flattened navbar items shared from one place so the Blade layer does less recomputing on its own. Public review and list queries now reuse the shared title card loading helpers too, Livewire list creation now authorizes suspended users correctly, and the welcome scaffold now uses the Livewire script config expected by the manual bundling setup.

### What Was Removed or Cleaned Up
The route layer is less crowded now that auth, account, and admin definitions were split out of `routes/web.php` into dedicated files. The test harness also stopped guessing whether the app is running in catalog-only mode by inspecting route registration, and the lockfile dropped an unnecessary root package name entry.

### Files That Changed
- `app/Actions/Content/LoadChangelogPageAction.php` — added the action that reads `changelog.md`, splits it into dated entries, converts markdown to safe HTML, and builds the public changes-page payload.
- `app/Actions/Home/GetLatestReviewFeedAction.php` — switched the latest reviews feed over to shared title card query helpers.
- `app/Actions/Layout/BuildFooterAction.php` — added footer legal-link data so the site footer can expose the new Changes page.
- `app/Actions/Layout/ResolveBreadcrumbIconAction.php` — added a shared icon resolver for breadcrumb labels and paths.
- `app/Actions/Lists/BuildPublicListsIndexQueryAction.php` — simplified public list loading and reused the shared title card query helpers.
- `app/Actions/Lists/BuildUserListItemsQueryAction.php` — switched user list item title loading over to the shared title card query helpers.
- `app/Actions/Lists/GetAccountWatchlistFilterOptionsAction.php` — switched watchlist filter option building to collection-based title data instead of repeated query clones.
- `app/Actions/Seo/ResolvePageShellViewDataAction.php` — added explicit auth and portal shell state flags so layouts can react without guessing.
- `app/Actions/Users/BuildPublicUserRatingsQueryAction.php` — switched public user ratings to the shared title card query helpers.
- `app/Actions/Users/BuildPublicUserReviewsQueryAction.php` — switched public user reviews to the shared title card query helpers.
- `app/Actions/Users/LoadAccountDashboardAction.php` — switched dashboard title cards and recent ratings over to the shared title card query helpers.
- `app/Actions/Users/LoadPublicUserProfileAction.php` — switched public profile highlights over to the shared title card query helpers.
- `app/Providers/ViewServiceProvider.php` — started sharing flattened account and admin navbar items plus the current portal user with shell views.
- `changelog.md` — added this new top-of-file release note entry for the current update.
- `package-lock.json` — removed the root package name field from the lockfile metadata.
- `resources/css/app.css` — added dedicated changelog stream styles plus auth control and remember-checkbox refinements.
- `resources/views/auth/login.blade.php` — switched the login page’s placeholder social entry buttons over to the shared button component.
- `resources/views/auth/register.blade.php` — switched the register page’s placeholder social entry buttons over to the shared button component.
- `resources/views/changes/index.blade.php` — added the public Blade view that renders the changelog as a readable editorial stream.
- `resources/views/layouts/partials/account-navbar.blade.php` — switched the account top navbar to the pre-flattened shared navigation items.
- `resources/views/layouts/partials/account-sidebar.blade.php` — switched the account sidebar to the shared portal user payload instead of fetching auth data inline.
- `resources/views/layouts/partials/admin-navbar.blade.php` — switched the admin top navbar to the pre-flattened shared navigation items.
- `resources/views/layouts/partials/admin-sidebar.blade.php` — switched the admin sidebar to the shared portal user payload instead of fetching auth data inline.
- `resources/views/layouts/partials/app-shell.blade.php` — switched the shell template over to explicit shell-state flags instead of recomputing them in Blade.
- `resources/views/welcome.blade.php` — switched the default scaffold to `@livewireScriptConfig` for manual bundling.
- `resources/views/components/ui/breadcrumbs/item.blade.php` — taught breadcrumb items to auto-resolve icons when no explicit icon is passed.
- `resources/views/components/ui/footer.blade.php` — added footer rendering for the new legal link group, including Changes.
- `resources/views/lists/show.blade.php` — linked the list-owner breadcrumb back to the public user page.
- `routes/web.php` — registered the public changes route, expanded the public route surface, and pulled in the split route files.
- `routes/auth.php` — moved auth route definitions into their own file.
- `routes/account.php` — moved authenticated account routes into their own file.
- `routes/admin.php` — moved admin dashboard, moderation, and catalog-management routes into their own file.
- `tests/Feature/Feature/ChangelogPageTest.php` — verified the changes page renders the markdown-driven editorial layout.
- `tests/Feature/Feature/Auth/AuthenticationFlowTest.php` — added coverage for the updated auth-page member entry controls and shared CTA structure.
- `app/Livewire/Lists/CreateListForm.php` — added explicit list-creation authorization inside the Livewire create-list component.
- `app/Livewire/Titles/CustomListPicker.php` — added authorization, locked title IDs, and computed data loaders for the inline list picker.
- `tests/Feature/Feature/PortalRouteRegistrationTest.php` — verified auth, account, admin, and public portal routes are all registered.
- `tests/Feature/Feature/PortalSurfaceSmokeTest.php` — smoke-tested auth, account, latest reviews, public lists, and public user pages.
- `tests/Feature/Feature/PublicRouteArchitectureTest.php` — updated route-surface expectations to include Changes, lists, users, and reviews.
- `tests/Feature/Feature/Lists/CustomListFlowTest.php` — covered the public list owner breadcrumb and blocked suspended users from creating lists through Livewire list components.
- `tests/Feature/Feature/SharedPublicLayoutRenderTest.php` — confirmed the shared public footer shows the Changes link and the welcome scaffold uses the Livewire script config.
- `tests/Feature/Feature/TitleDetailExperienceTest.php` — relaxed one title-page assertion so encoded punctuation is matched correctly.
- `tests/Feature/Feature/Ui/BreadcrumbIconRenderingTest.php` — covered automatic breadcrumb icon rendering.
- `tests/TestCase.php` — switched catalog-only detection to the `screenbase.catalog_only` config flag.
- `tests/Unit/Actions/Content/LoadChangelogPageActionTest.php` — covered changelog parsing, ordering, excerpt extraction, and separator cleanup.
- `tests/Unit/Actions/ResolveBreadcrumbIconActionTest.php` — covered exact-label icons, path-driven icons, and admin/account dashboard differences.

### Why This Matters
This update turns project history into part of the product experience while also cleaning up the navigation and routing layer behind the scenes. People can now find release notes from the UI, shared breadcrumbs stay consistent without manual icon work, and the route surface is easier to extend without letting `routes/web.php` become a dumping ground.

---

## 🗓️ 2026-04-11 — AKA archives, richer title pages, and real portal shells

Hey! Here is what changed today in this project:

### What's New
The catalog now has dedicated public AKA attribute and company credit attribute archives, so people can open metadata markers and immediately browse the linked titles, companies, categories, countries, and imported records behind them. Title detail pages also grew into much more human-friendly editorial pages, with better genre cards, interest-category links, featured cast cards, cleaner certificate sections, and richer company-credit linking. On top of that, the account and admin areas now have proper sidebar-based portal shells instead of feeling like the public site with a few shortcuts bolted on.

### What Was Improved
Livewire page payloads were normalized so empty collections and optional values stop leaking awkward edge cases into views. Shared UI controls were tightened up too: buttons now expose a consistent loading state, flag icons are resolved through a dedicated action instead of ad hoc Blade logic, native selects can be reused across moderation screens, and the public lists page now follows the same loading-state pattern as the rest of the browsing UI. There is also an important import-side fix that deduplicates company-credit bridge rows before insert, which protects company country and attribute relationships from duplicate writes.

### What Was Removed or Cleaned Up
Several raw-data table sections on title pages were replaced with cards, archive links, and explanations that read like product UI instead of an internal dump. Old `wire:loading.remove` patterns were cleaned out in favor of a single `data-loading` convention that works across multiple Livewire screens. The project also stopped repeating bare `<select>` styling and inline flag-resolution logic in views, which makes the rendering layer much easier to maintain.

### Files That Changed
- `app/Actions/Catalog/LoadAkaAttributeDetailsAction.php` — added the page loader that builds filtered AKA archive records, summaries, and SEO data.
- `app/Actions/Catalog/LoadCompanyCreditAttributeDetailsAction.php` — added the page loader for company credit attribute archives with company, category, country, and title filters.
- `app/Actions/Catalog/LoadCompanyDetailsAction.php` — turned company credit attribute badges into archive links from company detail pages.
- `app/Actions/Catalog/LoadTitleDetailsAction.php` — expanded title-page payloads with humanized AKA, certificate, company-credit-attribute, genre, and interest-category entries.
- `app/Actions/Import/ImportImdbCatalogTitlePayloadAction.php` — deduplicated company-credit bridge rows before inserts reach the database.
- `app/Actions/Layout/ResolveFlagViewDataAction.php` — centralized country and language flag lookup plus inline SVG ID scoping.
- `app/Actions/Lists/GetAccountWatchlistFilterOptionsAction.php` — added icon-aware watchlist state and sort options.
- `app/Actions/Seo/ResolvePageShellViewDataAction.php` — simplified shell utility detection and exposed a single `hasShellUtilities` flag.
- `app/Enums/AkaAttributeValue.php` — mapped raw AKA attribute values to friendly labels and descriptions.
- `app/Livewire/Catalog/TitleBrowser.php` — prepared richer chart-card payload objects for title ranking views.
- `app/Livewire/Pages/Concerns/NormalizesPageViewData.php` — introduced helpers for collection defaults and safe fallback values.
- `app/Livewire/Pages/Concerns/RendersPageView.php` — taught page rendering how to build portal navbars, sidebars, and default robots rules.
- `app/Livewire/Pages/Public/AkaAttributePage.php` — added the new public AKA attribute Livewire page.
- `app/Livewire/Pages/Public/AwardNominationPage.php` — normalized award-nomination payload collections before rendering.
- `app/Livewire/Pages/Public/CertificateAttributePage.php` — normalized certificate-attribute page payload defaults.
- `app/Livewire/Pages/Public/CertificateRatingPage.php` — normalized certificate-rating page payload defaults.
- `app/Livewire/Pages/Public/CompanyCreditAttributePage.php` — added the new public company credit attribute Livewire page.
- `app/Livewire/Pages/Public/CompanyPage.php` — normalized company page payload defaults.
- `app/Livewire/Pages/Public/ListsPage.php` — moved the public list index to computed view data and aligned it with the shared loading pattern.
- `app/Livewire/Pages/Public/TitleMediaArchivePage.php` — normalized archive payloads and built trailer archive item view data.
- `app/Livewire/Pages/Public/TitleMediaPage.php` — normalized media payloads and prepared archive cards, section links, and trailer lists.
- `app/Livewire/Pages/Public/TitlePage.php` — normalized the huge title-page payload and assembled featured cast, award nominee, and director cards.
- `app/Livewire/People/FilmographyPanel.php` — added a dedicated lazy-loading placeholder view.
- `app/Models/AkaAttribute.php` — added slug routing plus friendly label and description helpers.
- `app/Models/CompanyCreditAttribute.php` — added slug routing for company credit attribute archive pages.
- `app/Models/Genre.php` — added published-title counts and reusable description helpers.
- `app/Models/MovieAka.php` — added a direct `title()` relationship for AKA archive loading.
- `app/Models/Title.php` — expanded eager loads for counted genres and richer interest-category relations.
- `app/Providers/ViewServiceProvider.php` — started sharing navigation data with the new account and admin sidebars.
- `changelog.md` — added this human-readable release entry for the full update.
- `resources/js/app.js` — loaded the theme runtime script globally.
- `resources/js/globals/theme.js` — made theme runtime opt-in and synced the browser color scheme for portal shells.
- `resources/views/admin/reports/index.blade.php` — replaced ad hoc report controls with reusable fields, native selects, and a cleaner moderation form.
- `resources/views/admin/reviews/index.blade.php` — refreshed moderation filters and edit controls with the new form primitives.
- `resources/views/aka-attributes/show.blade.php` — created the new public AKA attribute archive page layout.
- `resources/views/awards/nominations/show.blade.php` — aligned the award nomination page with normalized collection data.
- `resources/views/certificates/attributes/show.blade.php` — aligned the certificate-attribute page with normalized collection data.
- `resources/views/certificates/ratings/show.blade.php` — aligned the certificate-rating page with normalized collection data.
- `resources/views/companies/show.blade.php` — aligned the company page with normalized collection data and linked attribute badges into the new archive pages.
- `resources/views/company-credit-attributes/show.blade.php` — created the new public company credit attribute archive page layout.
- `resources/views/components/catalog/aka-attribute-chip.blade.php` — added reusable chips for linked AKA attribute navigation.
- `resources/views/components/catalog/chart-title-card.blade.php` — switched chart cards to consume the richer card payload object.
- `resources/views/components/ui/button/index.blade.php` — rebuilt button loading behavior around shared `data-loading` selectors.
- `resources/views/components/ui/flag.blade.php` — moved flag rendering over to the new resolver action.
- `resources/views/components/ui/native-select.blade.php` — added a reusable styled native select component.
- `resources/views/layouts/account.blade.php` — wired the account layout into the new sidebar slot.
- `resources/views/layouts/admin.blade.php` — wired the admin layout into the new sidebar slot.
- `resources/views/layouts/partials/account-navbar.blade.php` — reduced account top navigation to compact shortcut items.
- `resources/views/layouts/partials/account-sidebar.blade.php` — added the member workspace sidebar.
- `resources/views/layouts/partials/admin-navbar.blade.php` — reduced admin top navigation to compact shortcut items.
- `resources/views/layouts/partials/admin-sidebar.blade.php` — added the admin workspace sidebar.
- `resources/views/layouts/partials/app-shell.blade.php` — introduced the portal shell structure, runtime theme bootstrapping, and updated toast placement.
- `resources/views/lists/index.blade.php` — converted public list browsing to the shared loading-state shell pattern.
- `resources/views/livewire/account/watchlist-browser.blade.php` — switched watchlist filters and skeletons to icon-aware controls and `data-loading` wrappers.
- `resources/views/livewire/catalog/interest-category-browser.blade.php` — converted the browser to the shared loading-state shell pattern.
- `resources/views/livewire/catalog/people-browser.blade.php` — converted the people browser to the shared loading-state shell pattern.
- `resources/views/livewire/catalog/title-browser.blade.php` — passed the full chart card payload into each chart card.
- `resources/views/livewire/lists/manage-list.blade.php` — applied the new loading-state convention and cleaner list-management badges.
- `resources/views/livewire/pagination/island-simple.blade.php` — dropped repeated disabled wiring in favor of CSS-driven loading states.
- `resources/views/livewire/people/filmography-panel.blade.php` — refreshed the filmography panel loading and result rendering flow.
- `resources/views/livewire/placeholders/filmography-panel.blade.php` — added a dedicated filmography skeleton placeholder.
- `resources/views/livewire/reviews/title-review-list.blade.php` — moved review-list loading feedback to a dedicated slot marker.
- `resources/views/livewire/search/discovery-filters.blade.php` — converted discovery results to the shared loading-state shell pattern.
- `resources/views/livewire/search/global-search.blade.php` — split global-search loading and results into explicit data slots.
- `resources/views/livewire/search/search-results.blade.php` — converted the search results page to the shared loading-state shell pattern.
- `resources/views/livewire/titles/rating-panel.blade.php` — removed button-specific loading wiring and relied on the shared button behavior.
- `resources/views/livewire/titles/review-composer.blade.php` — removed button-specific loading wiring and relied on the shared button behavior.
- `resources/views/people/show.blade.php` — lazy-loaded the filmography panel.
- `resources/views/titles/media-archive.blade.php` — switched trailer archive rendering over to prepared archive item data.
- `resources/views/titles/media.blade.php` — rebuilt media-page archive cards, section links, and lightbox offsets from normalized view data.
- `resources/views/titles/show.blade.php` — replaced raw internal tables with richer genre, interest, AKA, cast, and season UI.
- `routes/web.php` — registered the public AKA attribute and company credit attribute archive routes.
- `tests/Feature/Feature/Admin/AdminAccessTest.php` — asserted that admin pages render inside the new portal shell.
- `tests/Feature/Feature/AkaAttributePageTest.php` — covered the new AKA attribute archive page.
- `tests/Feature/Feature/ButtonComponentTest.php` — covered shared button loading-state behavior.
- `tests/Feature/Feature/CompanyCreditAttributePageTest.php` — covered the new company credit attribute archive page.
- `tests/Feature/Feature/Feature/Livewire/TitleReviewListTest.php` — asserted the review-list loading slot markup.
- `tests/Feature/Feature/FlagComponentTest.php` — verified flag rendering, invalid-code suppression, and scoped SVG IDs.
- `tests/Feature/Feature/Import/ImportImdbCatalogTitlePayloadActionTest.php` — covered duplicate company-credit bridge-row protection.
- `tests/Feature/Feature/Livewire/TitleBrowserChartViewDataTest.php` — verified the new chart-card payload shape.
- `tests/Feature/Feature/LivewireLoadingStateConventionTest.php` — enforced the shared `data-loading` convention and lazy filmography rendering.
- `tests/Feature/Feature/NativeSelectComponentTest.php` — covered the new native select component.
- `tests/Feature/Feature/Search/GlobalSearchLivewireTest.php` — asserted the new global-search loading and result slots.
- `tests/Feature/Feature/Seo/CatalogPageMetadataTest.php` — updated metadata fixtures for the expanded title-page payload.
- `tests/Feature/Feature/Seo/SlugGenerationTest.php` — updated slug-generation fixtures for the expanded title-page payload.
- `tests/Feature/Feature/TitleDetailExperienceTest.php` — updated expectations for the richer title detail experience.
- `tests/Feature/Feature/TitlePagePayloadFallbackTest.php` — updated payload fallbacks for the expanded title-page data structure.
- `tests/Feature/Feature/Users/ProfileAndDashboardTest.php` — asserted that account pages render inside the new portal shell.
- `tests/Unit/Actions/Lists/GetAccountWatchlistFilterOptionsActionTest.php` — covered the new watchlist filter icons.

### Why This Matters
This update makes the catalog feel much less like an internal data dump and much more like a polished product. People can navigate AKA metadata as a first-class archive, title pages explain relationships instead of just listing them, staff and members get proper workspace shells, and the shared Livewire/UI patterns are now consistent enough to build on safely.

---
