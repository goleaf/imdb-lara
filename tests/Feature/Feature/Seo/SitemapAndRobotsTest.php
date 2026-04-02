<?php

namespace Tests\Feature\Feature\Seo;

use App\Models\Genre;
use App\Models\ListItem;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapAndRobotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_and_robots_include_public_catalog_endpoints(): void
    {
        $user = User::factory()->create([
            'username' => 'curator',
        ]);
        $genre = Genre::factory()->create([
            'name' => 'Thriller',
            'slug' => 'thriller',
        ]);
        $title = Title::factory()->create([
            'name' => 'Northern Signal',
            'slug' => 'northern-signal',
            'release_year' => 2024,
        ]);
        $alternateTitle = Title::factory()->create([
            'name' => 'Northern Signal Alternate',
            'slug' => 'northern-signal-alternate',
            'canonical_title_id' => $title->id,
        ]);
        $title->genres()->attach($genre);
        MediaAsset::factory()->for($title, 'mediable')->poster()->create();
        $person = Person::factory()->create([
            'name' => 'Ari Vale',
            'slug' => 'ari-vale',
        ]);
        $list = UserList::factory()->for($user)->public()->create([
            'name' => 'Weekend Marathon',
            'slug' => 'weekend-marathon',
        ]);
        ListItem::factory()->for($list, 'userList')->for($title, 'title')->create([
            'position' => 1,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('public.home'), false)
            ->assertSee(route('public.awards.index'), false)
            ->assertSee(route('public.lists.index'), false)
            ->assertSee(route('public.genres.show', $genre), false)
            ->assertSee(route('public.years.show', ['year' => 2024]), false)
            ->assertSee(route('public.titles.show', $title), false)
            ->assertDontSee(route('public.titles.show', $alternateTitle), false)
            ->assertSee(route('public.people.show', $person), false)
            ->assertSee(route('public.users.show', $user), false)
            ->assertSee(route('public.lists.show', [$user, $list]), false)
            ->assertDontSee(route('public.search'), false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('sitemap'));
    }
}
