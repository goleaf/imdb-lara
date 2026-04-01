<?php

namespace Tests\Feature\Feature\Seo;

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
        $title = Title::factory()->create([
            'name' => 'Northern Signal',
            'slug' => 'northern-signal',
        ]);
        $person = Person::factory()->create([
            'name' => 'Ari Vale',
            'slug' => 'ari-vale',
        ]);
        $list = UserList::factory()->for($user)->public()->create([
            'name' => 'Weekend Marathon',
            'slug' => 'weekend-marathon',
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('public.home'), false)
            ->assertSee(route('public.titles.show', $title), false)
            ->assertSee(route('public.people.show', $person), false)
            ->assertSee(route('public.lists.show', [$user, $list]), false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('sitemap'));
    }
}
