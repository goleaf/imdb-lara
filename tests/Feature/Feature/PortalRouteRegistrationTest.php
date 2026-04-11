<?php

namespace Tests\Feature\Feature;

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
    public function test_portal_route_names_are_registered_for_livewire_surfaces_and_admin_mutations(): void
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
        $this->assertTrue(Route::has('admin.reviews.index'));
        $this->assertTrue(Route::has('admin.reports.index'));
        $this->assertTrue(Route::has('admin.contributions.index'));
        $this->assertTrue(Route::has('admin.titles.store'));
        $this->assertTrue(Route::has('admin.titles.update'));
        $this->assertTrue(Route::has('admin.titles.destroy'));
        $this->assertTrue(Route::has('admin.people.store'));
        $this->assertTrue(Route::has('admin.people.update'));
        $this->assertTrue(Route::has('admin.people.destroy'));
        $this->assertTrue(Route::has('admin.people.professions.store'));
        $this->assertTrue(Route::has('admin.professions.update'));
        $this->assertTrue(Route::has('admin.professions.destroy'));
        $this->assertTrue(Route::has('admin.credits.store'));
        $this->assertTrue(Route::has('admin.credits.update'));
        $this->assertTrue(Route::has('admin.credits.destroy'));
        $this->assertTrue(Route::has('admin.genres.store'));
        $this->assertTrue(Route::has('admin.genres.update'));
        $this->assertTrue(Route::has('admin.genres.destroy'));
        $this->assertTrue(Route::has('admin.titles.seasons.store'));
        $this->assertTrue(Route::has('admin.seasons.update'));
        $this->assertTrue(Route::has('admin.seasons.destroy'));
        $this->assertTrue(Route::has('admin.seasons.episodes.store'));
        $this->assertTrue(Route::has('admin.episodes.update'));
        $this->assertTrue(Route::has('admin.episodes.destroy'));
        $this->assertTrue(Route::has('admin.titles.media-assets.store'));
        $this->assertTrue(Route::has('admin.people.media-assets.store'));
        $this->assertTrue(Route::has('admin.media-assets.update'));
        $this->assertTrue(Route::has('admin.media-assets.destroy'));
        $this->assertTrue(Route::has('admin.reviews.update'));
        $this->assertTrue(Route::has('admin.reports.update'));
        $this->assertTrue(Route::has('admin.contributions.update'));
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
