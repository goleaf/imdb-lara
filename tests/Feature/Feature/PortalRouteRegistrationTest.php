<?php

namespace Tests\Feature\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PortalRouteRegistrationTest extends TestCase
{
    public function test_portal_route_names_are_registered_for_livewire_surfaces(): void
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
}
