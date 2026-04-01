<?php

namespace Tests\Feature\Feature\Seo;

use App\Enums\ListVisibility;
use App\Models\Genre;
use App\Models\ListItem;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class PageMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_pages_emit_open_graph_and_breadcrumb_metadata(): void
    {
        $title = Title::factory()->movie()->create([
            'name' => 'Northern Signal',
            'slug' => 'northern-signal',
            'meta_title' => 'Northern Signal | Screenbase',
            'meta_description' => 'Editorial metadata for Northern Signal.',
        ]);
        MediaAsset::factory()->for($title, 'mediable')->backdrop()->create([
            'url' => 'https://images.example.test/northern-signal-backdrop.jpg',
            'alt_text' => 'Northern Signal backdrop',
        ]);

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('public.titles.show', $title).'">', false)
            ->assertSee('<meta property="og:type" content="video.movie">', false)
            ->assertSee('<meta property="og:title" content="Northern Signal | Screenbase">', false)
            ->assertSee('<meta property="og:image" content="https://images.example.test/northern-signal-backdrop.jpg">', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee(route('public.titles.show', $title), false);
    }

    public function test_title_pages_point_canonical_metadata_to_the_canonical_title_when_present(): void
    {
        $canonicalTitle = Title::factory()->movie()->create([
            'name' => 'Primary Signal',
            'slug' => 'primary-signal',
        ]);
        $alternateTitle = Title::factory()->movie()->create([
            'name' => 'Primary Signal Alternate',
            'slug' => 'primary-signal-alternate',
            'canonical_title_id' => $canonicalTitle->id,
        ]);

        $this->get(route('public.titles.show', $alternateTitle))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('public.titles.show', $canonicalTitle).'">', false);
    }

    public function test_search_and_browse_pages_generate_pagination_aware_canonical_urls(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'Sci-Fi',
            'slug' => 'sci-fi',
        ]);
        $title = Title::factory()->movie()->create([
            'name' => 'Signal North',
            'slug' => 'signal-north',
        ]);
        $title->genres()->attach($genre);
        Title::factory()->count(12)->movie()->create();

        $searchQuery = [
            'q' => 'Signal',
            'genre' => 'sci-fi',
            'sort' => 'rating',
            'titles' => 2,
        ];
        ksort($searchQuery);
        $expectedSearchCanonical = route('public.search').'?'.Arr::query($searchQuery);

        $this->get(route('public.search', [
            'q' => 'Signal',
            'genre' => 'sci-fi',
            'sort' => 'rating',
            'titles' => 2,
        ]))
            ->assertOk()
            ->assertSee('href="'.e($expectedSearchCanonical).'"', false);

        $this->get(route('public.movies.index', ['movies' => 2]))
            ->assertOk()
            ->assertSee('href="'.e(route('public.movies.index', ['movies' => 2])).'"', false);
    }

    public function test_person_and_public_list_pages_emit_entity_metadata(): void
    {
        $person = Person::factory()->create([
            'name' => 'Ava Mercer',
            'slug' => 'ava-mercer',
            'meta_title' => 'Ava Mercer',
            'meta_description' => 'Ava Mercer creator profile.',
            'is_published' => true,
        ]);
        MediaAsset::factory()->for($person, 'mediable')->headshot()->create([
            'url' => 'https://images.example.test/ava-mercer-headshot.jpg',
            'alt_text' => 'Ava Mercer headshot',
        ]);

        $this->get(route('public.people.show', $person))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('public.people.show', $person).'">', false)
            ->assertSee('<meta property="og:type" content="profile">', false)
            ->assertSee('<meta property="og:image" content="https://images.example.test/ava-mercer-headshot.jpg">', false);

        $owner = User::factory()->create([
            'name' => 'Curator',
            'username' => 'curator',
        ]);
        $listTitle = Title::factory()->movie()->create([
            'name' => 'Weekend Signal',
            'slug' => 'weekend-signal',
        ]);
        MediaAsset::factory()->for($listTitle, 'mediable')->poster()->create([
            'url' => 'https://images.example.test/weekend-signal-poster.jpg',
            'alt_text' => 'Weekend Signal poster',
        ]);
        $list = UserList::factory()->for($owner)->create([
            'name' => 'Weekend Marathon',
            'slug' => 'weekend-marathon',
            'visibility' => ListVisibility::Public,
            'description' => 'A public weekend queue.',
        ]);
        ListItem::factory()->for($list, 'userList')->for($listTitle, 'title')->create([
            'position' => 1,
        ]);

        $this->get(route('public.lists.show', [$owner, $list]))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('public.lists.show', [$owner, $list]).'">', false)
            ->assertSee('<meta property="og:image" content="https://images.example.test/weekend-signal-poster.jpg">', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }
}
