<?php

namespace Tests\Feature\Feature;

use Illuminate\Support\Facades\Route;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PortalRouteRegistrationTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_portal_route_names_are_registered_for_livewire_surfaces(): void
    {
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('register'));
        $this->assertTrue(Route::has('logout'));
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
        $this->assertTrue(Route::has('public.lists.index'));
        $this->assertTrue(Route::has('public.lists.show'));
        $this->assertTrue(Route::has('public.users.show'));
        $this->assertTrue(Route::has('public.reviews.latest'));
    }
}
