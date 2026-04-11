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
