<?php

namespace Tests\Feature\Feature;

use App\Livewire\Pages\Admin\AkaAttributeCreatePage;
use App\Livewire\Pages\Admin\AkaAttributeEditPage;
use App\Livewire\Pages\Admin\AkaAttributesIndexPage;
use App\Livewire\Pages\Admin\AkaTypeCreatePage;
use App\Livewire\Pages\Admin\AkaTypeEditPage;
use App\Livewire\Pages\Admin\AkaTypesIndexPage;
use App\Livewire\Pages\Admin\GenreCreatePage;
use App\Livewire\Pages\Admin\GenreEditPage;
use App\Livewire\Pages\Admin\GenresIndexPage;
use App\Livewire\Pages\Admin\MediaAssetEditPage;
use App\Livewire\Pages\Admin\MediaAssetsIndexPage;
use App\Livewire\Pages\Admin\PeopleIndexPage;
use App\Livewire\Pages\Admin\PersonCreatePage;
use App\Livewire\Pages\Admin\PersonEditPage;
use App\Livewire\Pages\Admin\TitleCreatePage;
use App\Livewire\Pages\Admin\TitleEditPage;
use App\Livewire\Pages\Admin\TitlesIndexPage;
use App\Livewire\Pages\Auth\LoginPage;
use App\Livewire\Pages\Auth\RegisterPage;
use App\Livewire\Pages\Public\GenreBrowsePage;
use App\Livewire\Pages\Public\MoviesBrowsePage;
use App\Livewire\Pages\Public\SeriesBrowsePage;
use App\Livewire\Pages\Public\TitlesBrowsePage;
use App\Livewire\Pages\Public\TopRatedMoviesPage;
use App\Livewire\Pages\Public\TopRatedSeriesPage;
use App\Livewire\Pages\Public\TrendingPage;
use App\Livewire\Pages\Public\YearBrowsePage;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PortalRouteRegistrationTest extends TestCase
{
    public function test_portal_route_names_are_registered_for_the_full_portal_surface(): void
    {
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('register'));
        $this->assertFalse(Route::has('logout'));
        $this->assertTrue(Route::has('account.dashboard'));
        $this->assertTrue(Route::has('account.watchlist'));
        $this->assertTrue(Route::has('account.lists.index'));
        $this->assertTrue(Route::has('account.lists.show'));
        $this->assertTrue(Route::has('admin.dashboard'));
        $this->assertTrue(Route::has('admin.titles.index'));
        $this->assertTrue(Route::has('admin.titles.create'));
        $this->assertTrue(Route::has('admin.titles.edit'));
        $this->assertTrue(Route::has('admin.aka-attributes.index'));
        $this->assertTrue(Route::has('admin.aka-attributes.create'));
        $this->assertTrue(Route::has('admin.aka-attributes.edit'));
        $this->assertTrue(Route::has('admin.aka-types.index'));
        $this->assertTrue(Route::has('admin.aka-types.create'));
        $this->assertTrue(Route::has('admin.aka-types.edit'));
        $this->assertTrue(Route::has('admin.reviews.index'));
        $this->assertTrue(Route::has('admin.reports.index'));
        $this->assertTrue(Route::has('admin.contributions.index'));
        $this->assertTrue(Route::has('public.lists.index'));
        $this->assertTrue(Route::has('public.lists.show'));
        $this->assertTrue(Route::has('public.users.show'));
        $this->assertTrue(Route::has('public.reviews.latest'));
    }

    public function test_portal_routes_point_to_dedicated_livewire_page_components(): void
    {
        $expectedRouteComponents = [
            'login' => LoginPage::class,
            'register' => RegisterPage::class,
            'admin.titles.index' => TitlesIndexPage::class,
            'admin.titles.create' => TitleCreatePage::class,
            'admin.titles.edit' => TitleEditPage::class,
            'admin.aka-attributes.index' => AkaAttributesIndexPage::class,
            'admin.aka-attributes.create' => AkaAttributeCreatePage::class,
            'admin.aka-attributes.edit' => AkaAttributeEditPage::class,
            'admin.aka-types.index' => AkaTypesIndexPage::class,
            'admin.aka-types.create' => AkaTypeCreatePage::class,
            'admin.aka-types.edit' => AkaTypeEditPage::class,
            'admin.people.index' => PeopleIndexPage::class,
            'admin.people.create' => PersonCreatePage::class,
            'admin.people.edit' => PersonEditPage::class,
            'admin.genres.index' => GenresIndexPage::class,
            'admin.genres.create' => GenreCreatePage::class,
            'admin.genres.edit' => GenreEditPage::class,
            'admin.media-assets.index' => MediaAssetsIndexPage::class,
            'admin.media-assets.edit' => MediaAssetEditPage::class,
            'public.movies.index' => MoviesBrowsePage::class,
            'public.series.index' => SeriesBrowsePage::class,
            'public.titles.index' => TitlesBrowsePage::class,
            'public.genres.show' => GenreBrowsePage::class,
            'public.years.show' => YearBrowsePage::class,
            'public.rankings.movies' => TopRatedMoviesPage::class,
            'public.rankings.series' => TopRatedSeriesPage::class,
            'public.trending' => TrendingPage::class,
        ];

        foreach ($expectedRouteComponents as $routeName => $componentClass) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertSame($componentClass, $route?->getAction('livewire_component'));
        }
    }

    public function test_admin_mutation_routes_are_not_registered(): void
    {
        $routeNames = [
            'admin.titles.store',
            'admin.titles.update',
            'admin.titles.destroy',
            'admin.people.store',
            'admin.people.update',
            'admin.people.destroy',
            'admin.people.professions.store',
            'admin.professions.update',
            'admin.professions.destroy',
            'admin.credits.store',
            'admin.credits.update',
            'admin.credits.destroy',
            'admin.genres.store',
            'admin.genres.update',
            'admin.genres.destroy',
            'admin.titles.seasons.store',
            'admin.seasons.update',
            'admin.seasons.destroy',
            'admin.seasons.episodes.store',
            'admin.episodes.update',
            'admin.episodes.destroy',
            'admin.titles.media-assets.store',
            'admin.people.media-assets.store',
            'admin.media-assets.update',
            'admin.media-assets.destroy',
            'admin.reviews.update',
            'admin.reports.update',
            'admin.contributions.update',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertFalse(Route::has($routeName));
            $this->assertNull(Route::getRoutes()->getByName($routeName));
        }
    }
}
