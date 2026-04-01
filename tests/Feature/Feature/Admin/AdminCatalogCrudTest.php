<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_update_and_delete_titles_with_genres(): void
    {
        $editor = User::factory()->editor()->create();
        $drama = Genre::factory()->create(['name' => 'Drama', 'slug' => 'drama']);
        $thriller = Genre::factory()->create(['name' => 'Thriller', 'slug' => 'thriller']);

        $this->actingAs($editor)
            ->post(route('admin.titles.store'), [
                'name' => 'Parallax Echo',
                'original_name' => 'Parallax Echo',
                'slug' => 'parallax-echo',
                'title_type' => TitleType::Movie->value,
                'release_year' => 2025,
                'release_date' => '2025-10-03',
                'runtime_minutes' => 121,
                'age_rating' => 'PG-13',
                'plot_outline' => 'An editor uncovers a citywide surveillance scheme.',
                'synopsis' => 'An editor uncovers a citywide surveillance scheme and follows the damage upstream.',
                'tagline' => 'Truth has a burn rate.',
                'origin_country' => 'US',
                'original_language' => 'en',
                'meta_title' => 'Parallax Echo',
                'meta_description' => 'Editorial thriller metadata.',
                'search_keywords' => 'thriller, surveillance',
                'is_published' => true,
                'genre_ids' => [$drama->id, $thriller->id],
            ])
            ->assertRedirect();

        $title = Title::query()->where('slug', 'parallax-echo')->firstOrFail();

        $this->assertSame(TitleType::Movie, $title->title_type);
        $this->assertTrue($title->is_published);
        $this->assertEqualsCanonicalizing([$drama->id, $thriller->id], $title->genres()->pluck('genres.id')->all());

        $this->actingAs($editor)
            ->patch(route('admin.titles.update', $title), [
                'name' => 'Parallax Echo Redux',
                'original_name' => 'Parallax Echo',
                'slug' => 'parallax-echo-redux',
                'title_type' => TitleType::Special->value,
                'release_year' => 2026,
                'release_date' => '2026-02-14',
                'runtime_minutes' => 95,
                'age_rating' => 'R',
                'plot_outline' => 'Updated logline',
                'synopsis' => 'Updated synopsis',
                'tagline' => 'Updated tagline',
                'origin_country' => 'GB',
                'original_language' => 'en',
                'meta_title' => 'Updated meta',
                'meta_description' => 'Updated meta description',
                'search_keywords' => 'updated, special',
                'is_published' => false,
                'genre_ids' => [$thriller->id],
            ])
            ->assertRedirect(route('admin.titles.edit', $title->fresh()));

        $title->refresh();

        $this->assertSame('Parallax Echo Redux', $title->name);
        $this->assertSame('parallax-echo-redux', $title->slug);
        $this->assertSame(TitleType::Special, $title->title_type);
        $this->assertFalse($title->is_published);
        $this->assertSame([$thriller->id], $title->genres()->pluck('genres.id')->all());

        $this->actingAs($editor)
            ->delete(route('admin.titles.destroy', $title))
            ->assertRedirect(route('admin.titles.index'));

        $this->assertSoftDeleted('titles', ['id' => $title->id]);
    }

    public function test_editor_can_manage_people_professions_credits_seasons_episodes_and_media_assets(): void
    {
        $editor = User::factory()->editor()->create();
        $series = Title::factory()->series()->create([
            'name' => 'Northbound',
            'slug' => 'northbound',
        ]);
        $genre = Genre::factory()->create();

        $this->actingAs($editor)
            ->post(route('admin.people.store'), [
                'name' => 'Iris Vale',
                'alternate_names' => 'Iris Vale | Iris V.',
                'slug' => 'iris-vale',
                'short_biography' => 'Award-winning actor and producer.',
                'biography' => 'Full biography for Iris Vale.',
                'known_for_department' => 'Acting',
                'birth_date' => '1988-04-12',
                'death_date' => null,
                'birth_place' => 'Vilnius, Lithuania',
                'death_place' => null,
                'nationality' => 'Lithuanian',
                'popularity_rank' => 12,
                'meta_title' => 'Iris Vale',
                'meta_description' => 'Iris Vale profile.',
                'search_keywords' => 'actor, producer',
                'is_published' => true,
            ])
            ->assertRedirect();

        $person = Person::query()->where('slug', 'iris-vale')->firstOrFail();

        $this->actingAs($editor)
            ->post(route('admin.people.professions.store', $person), [
                'department' => 'Cast',
                'profession' => 'Actor',
                'is_primary' => true,
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.people.edit', $person));

        $profession = PersonProfession::query()->whereBelongsTo($person)->firstOrFail();

        $this->actingAs($editor)
            ->patch(route('admin.professions.update', $profession), [
                'department' => 'Production',
                'profession' => 'Executive Producer',
                'is_primary' => false,
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.people.edit', $person));

        $profession->refresh();
        $this->assertSame('Executive Producer', $profession->profession);

        $this->actingAs($editor)
            ->post(route('admin.credits.store'), [
                'title_id' => $series->id,
                'person_id' => $person->id,
                'person_profession_id' => $profession->id,
                'department' => 'Cast',
                'job' => 'Lead',
                'character_name' => 'Mara Quill',
                'billing_order' => 1,
                'credited_as' => 'Iris Vale',
                'is_principal' => true,
                'episode_id' => null,
            ])
            ->assertRedirect();

        $credit = Credit::query()->whereBelongsTo($series)->whereBelongsTo($person)->firstOrFail();
        $this->assertSame('Mara Quill', $credit->character_name);

        $this->actingAs($editor)
            ->post(route('admin.titles.seasons.store', $series), [
                'name' => 'Season 1',
                'slug' => 'season-1',
                'season_number' => 1,
                'summary' => 'First season summary.',
                'release_year' => 2026,
                'meta_title' => 'Northbound Season 1',
                'meta_description' => 'Season one metadata.',
            ])
            ->assertRedirect();

        $season = Season::query()->whereBelongsTo($series, 'series')->where('slug', 'season-1')->firstOrFail();

        $this->actingAs($editor)
            ->post(route('admin.seasons.episodes.store', $season), [
                'name' => 'Signals in Snow',
                'slug' => 'signals-in-snow',
                'plot_outline' => 'Episode outline.',
                'synopsis' => 'Episode synopsis.',
                'release_year' => 2026,
                'release_date' => '2026-01-10',
                'runtime_minutes' => 54,
                'age_rating' => 'TV-14',
                'origin_country' => 'NO',
                'original_language' => 'en',
                'is_published' => true,
                'season_number' => 1,
                'episode_number' => 1,
                'absolute_number' => 1,
                'production_code' => 'NB101',
                'aired_at' => '2026-01-10',
            ])
            ->assertRedirect();

        $episode = Episode::query()->whereBelongsTo($season)->firstOrFail();

        $this->assertSame(1, $episode->episode_number);
        $this->assertSame(TitleType::Episode, $episode->title->title_type);
        $this->assertSame('Signals in Snow', $episode->title->name);

        $this->actingAs($editor)
            ->post(route('admin.titles.media-assets.store', $series), [
                'kind' => MediaKind::Poster->value,
                'url' => 'https://images.example.test/posters/northbound.jpg',
                'alt_text' => 'Northbound poster',
                'caption' => 'Primary poster',
                'width' => 1200,
                'height' => 1800,
                'provider' => 'internal',
                'provider_key' => 'northbound-poster',
                'language' => 'en',
                'duration_seconds' => null,
                'metadata' => null,
                'is_primary' => true,
                'position' => 0,
                'published_at' => '2026-01-01 00:00:00',
            ])
            ->assertRedirect();

        $mediaAsset = MediaAsset::query()
            ->where('mediable_type', Title::class)
            ->where('mediable_id', $series->id)
            ->firstOrFail();

        $this->assertSame(MediaKind::Poster, $mediaAsset->kind);

        $this->actingAs($editor)
            ->patch(route('admin.people.update', $person), [
                'name' => 'Iris Vale Updated',
                'alternate_names' => 'Iris Vale | Iris V.',
                'slug' => 'iris-vale-updated',
                'short_biography' => 'Updated short bio.',
                'biography' => 'Updated full biography.',
                'known_for_department' => 'Production',
                'birth_date' => '1988-04-12',
                'death_date' => null,
                'birth_place' => 'Vilnius, Lithuania',
                'death_place' => null,
                'nationality' => 'Lithuanian',
                'popularity_rank' => 8,
                'meta_title' => 'Updated Iris Vale',
                'meta_description' => 'Updated profile.',
                'search_keywords' => 'updated, producer',
                'is_published' => true,
            ])
            ->assertRedirect();

        $person->refresh();
        $this->assertSame('Iris Vale Updated', $person->name);
        $this->assertSame('iris-vale-updated', $person->slug);

        $this->actingAs($editor)
            ->delete(route('admin.media-assets.destroy', $mediaAsset))
            ->assertRedirect();
        $this->actingAs($editor)
            ->delete(route('admin.credits.destroy', $credit))
            ->assertRedirect();
        $this->actingAs($editor)
            ->delete(route('admin.episodes.destroy', $episode))
            ->assertRedirect();
        $this->actingAs($editor)
            ->delete(route('admin.seasons.destroy', $season))
            ->assertRedirect();
        $this->actingAs($editor)
            ->delete(route('admin.people.destroy', $person))
            ->assertRedirect();
        $this->actingAs($editor)
            ->delete(route('admin.genres.destroy', $genre))
            ->assertRedirect();

        $this->assertSoftDeleted('media_assets', ['id' => $mediaAsset->id]);
        $this->assertSoftDeleted('credits', ['id' => $credit->id]);
        $this->assertSoftDeleted('titles', ['id' => $episode->title_id]);
        $this->assertSoftDeleted('seasons', ['id' => $season->id]);
        $this->assertSoftDeleted('people', ['id' => $person->id]);
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_editor_can_create_update_and_delete_genres_from_the_admin_cms(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->post(route('admin.genres.store'), [
                'name' => 'Neo Noir',
                'slug' => 'neo-noir',
                'description' => 'Moody urban thrillers.',
            ])
            ->assertRedirect();

        $genre = Genre::query()->where('slug', 'neo-noir')->firstOrFail();

        $this->actingAs($editor)
            ->patch(route('admin.genres.update', $genre), [
                'name' => 'Neo-Noir',
                'slug' => 'neo-noir-updated',
                'description' => 'Updated genre description.',
            ])
            ->assertRedirect(route('admin.genres.edit', $genre->fresh()));

        $genre->refresh();

        $this->assertSame('Neo-Noir', $genre->name);
        $this->assertSame('neo-noir-updated', $genre->slug);

        $this->actingAs($editor)
            ->delete(route('admin.genres.destroy', $genre))
            ->assertRedirect(route('admin.genres.index'));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }
}
