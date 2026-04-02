<?php

namespace Tests\Feature\Feature;

use App\Enums\ListVisibility;
use App\Enums\ProfileVisibility;
use App\Models\Person;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionReadinessSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{title: Title, person: Person, list: UserList, profileUser: User}
     */
    private function seedSmokeDataset(): array
    {
        $this->seed(DemoCatalogSeeder::class);

        $title = Title::query()->publishedCatalog()->orderBy('popularity_rank')->firstOrFail();
        $person = Person::query()->published()->orderBy('popularity_rank')->firstOrFail();
        $list = UserList::query()
            ->where('visibility', ListVisibility::Public)
            ->where('is_watchlist', false)
            ->with('user:id,username')
            ->orderBy('updated_at')
            ->firstOrFail();
        $profileUser = User::query()
            ->where('profile_visibility', ProfileVisibility::Public)
            ->whereHas('publicLists')
            ->firstOrFail();

        return [
            'title' => $title,
            'person' => $person,
            'list' => $list,
            'profileUser' => $profileUser,
        ];
    }

    public function test_seeded_public_surfaces_render_with_the_shared_shell(): void
    {
        $dataset = $this->seedSmokeDataset();
        Livewire::withoutLazyLoading();

        $routes = [
            route('public.home'),
            route('public.discover'),
            route('public.movies.index'),
            route('public.series.index'),
            route('public.people.index'),
            route('public.awards.index'),
            route('public.search', ['q' => 'Signal']),
            route('public.trending'),
            route('public.titles.show', $dataset['title']),
            route('public.people.show', $dataset['person']),
            route('public.lists.show', [$dataset['list']->user, $dataset['list']]),
            route('public.users.show', $dataset['profileUser']),
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);

            $this->assertSame(200, $response->getStatusCode(), $route);
            $response->assertSee('Screenbase', false);
        }
    }

    public function test_seeded_admin_catalog_and_moderation_surfaces_render_for_authorized_roles(): void
    {
        $this->seedSmokeDataset();

        $editor = User::query()->where('email', 'editor@example.com')->firstOrFail();
        $moderator = User::query()->where('email', 'moderator@example.com')->firstOrFail();
        $member = User::query()->where('email', 'member@example.com')->firstOrFail();

        $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard');
        $this->actingAs($editor)->get(route('admin.titles.index'))->assertOk()->assertSee('Titles');
        $this->actingAs($editor)->get(route('admin.people.index'))->assertOk()->assertSee('People');
        $this->actingAs($editor)->get(route('admin.genres.index'))->assertOk()->assertSee('Genres');
        $this->actingAs($editor)->get(route('admin.media-assets.index'))->assertOk()->assertSee('Media');
        $this->actingAs($editor)->get(route('admin.contributions.index'))->assertOk()->assertSee('Contributions');

        $this->actingAs($moderator)->get(route('admin.reviews.index'))->assertOk()->assertSee('Reviews');
        $this->actingAs($moderator)->get(route('admin.reports.index'))->assertOk()->assertSee('Reports');
        $this->actingAs($member)->get(route('account.dashboard'))->assertOk()->assertSee('Dashboard');
        $this->actingAs($member)->get(route('account.settings'))->assertOk()->assertSee('Profile Settings');
    }
}
