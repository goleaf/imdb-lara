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
        $this->assertFalse(Route::has('admin.titles.store'));
        $this->assertFalse(Route::has('admin.titles.update'));
        $this->assertFalse(Route::has('admin.titles.destroy'));
        $this->assertFalse(Route::has('admin.people.store'));
        $this->assertFalse(Route::has('admin.people.update'));
        $this->assertFalse(Route::has('admin.people.destroy'));
        $this->assertFalse(Route::has('admin.people.professions.store'));
        $this->assertFalse(Route::has('admin.professions.update'));
        $this->assertFalse(Route::has('admin.professions.destroy'));
        $this->assertFalse(Route::has('admin.credits.store'));
        $this->assertFalse(Route::has('admin.credits.update'));
        $this->assertFalse(Route::has('admin.credits.destroy'));
        $this->assertFalse(Route::has('admin.genres.store'));
        $this->assertFalse(Route::has('admin.genres.update'));
        $this->assertFalse(Route::has('admin.genres.destroy'));
        $this->assertFalse(Route::has('admin.titles.seasons.store'));
        $this->assertFalse(Route::has('admin.seasons.update'));
        $this->assertFalse(Route::has('admin.seasons.destroy'));
        $this->assertFalse(Route::has('admin.seasons.episodes.store'));
        $this->assertFalse(Route::has('admin.episodes.update'));
        $this->assertFalse(Route::has('admin.episodes.destroy'));
        $this->assertFalse(Route::has('admin.titles.media-assets.store'));
        $this->assertFalse(Route::has('admin.people.media-assets.store'));
        $this->assertFalse(Route::has('admin.media-assets.update'));
        $this->assertFalse(Route::has('admin.media-assets.destroy'));
        $this->assertFalse(Route::has('admin.reviews.update'));
        $this->assertFalse(Route::has('admin.reports.update'));
        $this->assertFalse(Route::has('admin.contributions.update'));
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
}
