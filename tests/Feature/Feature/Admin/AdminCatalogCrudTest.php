<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\TitleType;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminCatalogCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_catalog_mutation_routes_are_registered(): void
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
        ];

        foreach ($routeNames as $routeName) {
            $this->assertTrue(Route::has($routeName), $routeName.' should be registered.');
        }
    }

    public function test_editor_can_create_update_and_delete_titles_with_genres(): void
    {
        $editor = User::factory()->editor()->create();
        $firstGenre = Genre::factory()->create(['name' => 'Mystery', 'slug' => 'mystery']);
        $secondGenre = Genre::factory()->create(['name' => 'Noir', 'slug' => 'noir']);

        $this->actingAs($editor)
            ->post(route('admin.titles.store'), [
                'name' => 'Shadow Harbor',
                'original_name' => 'Shadow Harbor',
                'slug' => 'shadow-harbor',
                'title_type' => TitleType::Movie->value,
                'release_year' => 2024,
                'end_year' => null,
                'release_date' => '2024-05-01',
                'runtime_minutes' => 118,
                'age_rating' => 'PG-13',
                'origin_country' => 'US',
                'original_language' => 'en',
                'is_published' => '1',
                'genre_ids' => [$firstGenre->id],
                'plot_outline' => 'A detective returns home.',
                'synopsis' => 'A full synopsis for Shadow Harbor.',
                'tagline' => 'Everyone owes the harbor something.',
                'meta_title' => 'Shadow Harbor',
                'meta_description' => 'Mystery drama.',
                'search_keywords' => 'harbor, mystery',
            ])
            ->assertRedirect();

        $title = Title::query()->where('slug', 'shadow-harbor')->firstOrFail();

        $this->assertSame(TitleType::Movie, $title->title_type);
        $this->assertDatabaseHas('genre_title', [
            'title_id' => $title->id,
            'genre_id' => $firstGenre->id,
        ]);

        $this->actingAs($editor)
            ->patch(route('admin.titles.update', $title), [
                'name' => 'Shadow Harbor: Director Cut',
                'original_name' => 'Shadow Harbor',
                'slug' => 'shadow-harbor-directors-cut',
                'title_type' => TitleType::Movie->value,
                'release_year' => 2025,
                'end_year' => null,
                'release_date' => '2025-01-10',
                'runtime_minutes' => 124,
                'age_rating' => 'R',
                'origin_country' => 'GB',
                'original_language' => 'en',
                'is_published' => '0',
                'genre_ids' => [$secondGenre->id],
                'plot_outline' => 'Updated outline.',
                'synopsis' => 'Updated synopsis.',
                'tagline' => 'Updated tagline.',
                'meta_title' => 'Shadow Harbor Director Cut',
                'meta_description' => 'Updated SEO description.',
                'search_keywords' => 'director cut',
            ])
            ->assertRedirect(route('admin.titles.edit', $title->fresh()));

        $title->refresh();

        $this->assertSame('Shadow Harbor: Director Cut', $title->name);
        $this->assertSame('shadow-harbor-directors-cut', $title->slug);
        $this->assertFalse($title->is_published);
        $this->assertDatabaseMissing('genre_title', [
            'title_id' => $title->id,
            'genre_id' => $firstGenre->id,
        ]);
        $this->assertDatabaseHas('genre_title', [
            'title_id' => $title->id,
            'genre_id' => $secondGenre->id,
        ]);

        $this->actingAs($editor)
            ->delete(route('admin.titles.destroy', $title))
            ->assertRedirect(route('admin.titles.index'));

        $this->assertSoftDeleted('titles', ['id' => $title->id]);
    }

    public function test_editor_can_manage_people_professions_and_credits_with_validation_guards(): void
    {
        $editor = User::factory()->editor()->create();
        $series = Title::factory()->series()->create();
        $otherSeries = Title::factory()->series()->create();
        $season = Season::factory()->for($series, 'series')->create();
        $otherSeason = Season::factory()->for($otherSeries, 'series')->create();
        $episodeTitle = Title::factory()->episode()->create();
        $otherEpisodeTitle = Title::factory()->episode()->create();
        $episode = Episode::factory()
            ->for($episodeTitle, 'title')
            ->for($series, 'series')
            ->for($season, 'season')
            ->create([
                'season_number' => $season->season_number,
            ]);
        $otherEpisode = Episode::factory()
            ->for($otherEpisodeTitle, 'title')
            ->for($otherSeries, 'series')
            ->for($otherSeason, 'season')
            ->create([
                'season_number' => $otherSeason->season_number,
            ]);

        $this->actingAs($editor)
            ->post(route('admin.people.store'), [
                'name' => 'Ava Mercer',
                'alternate_names' => 'A. Mercer',
                'slug' => 'ava-mercer',
                'known_for_department' => 'Acting',
                'popularity_rank' => 18,
                'birth_date' => '1988-04-12',
                'death_date' => null,
                'birth_place' => 'Vilnius, Lithuania',
                'death_place' => null,
                'nationality' => 'Lithuanian',
                'short_biography' => 'Award-winning actor.',
                'biography' => 'Longer person biography.',
                'meta_title' => 'Ava Mercer',
                'meta_description' => 'Actor biography.',
                'search_keywords' => 'ava, mercer',
                'is_published' => '1',
            ])
            ->assertRedirect();

        $person = Person::query()->where('slug', 'ava-mercer')->firstOrFail();
        $otherPerson = Person::factory()->create();

        $this->actingAs($editor)
            ->post(route('admin.people.professions.store', $person), [
                'department' => 'Cast',
                'profession' => 'Actor',
                'is_primary' => '1',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.people.edit', $person));

        $profession = PersonProfession::query()->where('person_id', $person->id)->firstOrFail();
        $otherProfession = PersonProfession::factory()->for($otherPerson)->create();

        $this->actingAs($editor)
            ->from(route('admin.credits.create'))
            ->post(route('admin.credits.store'), [
                'title_id' => $series->id,
                'person_id' => $person->id,
                'person_profession_id' => $otherProfession->id,
                'department' => 'Cast',
                'job' => 'Actor',
                'character_name' => 'Detective Vale',
                'billing_order' => 1,
                'credited_as' => 'Ava Mercer',
                'is_principal' => '1',
                'episode_id' => $otherEpisode->id,
            ])
            ->assertRedirect(route('admin.credits.create'))
            ->assertSessionHasErrors(['person_profession_id', 'episode_id']);

        $this->actingAs($editor)
            ->post(route('admin.credits.store'), [
                'title_id' => $series->id,
                'person_id' => $person->id,
                'person_profession_id' => $profession->id,
                'department' => 'Cast',
                'job' => 'Actor',
                'character_name' => 'Detective Vale',
                'billing_order' => 1,
                'credited_as' => 'Ava Mercer',
                'is_principal' => '1',
                'episode_id' => $episode->id,
            ])
            ->assertRedirect();

        $credit = Credit::query()
            ->where('title_id', $series->id)
            ->where('person_id', $person->id)
            ->firstOrFail();

        $this->actingAs($editor)
            ->patch(route('admin.professions.update', $profession), [
                'department' => 'Cast',
                'profession' => 'Lead Actor',
                'is_primary' => '1',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.people.edit', $person));

        $this->actingAs($editor)
            ->patch(route('admin.credits.update', $credit), [
                'title_id' => $series->id,
                'person_id' => $person->id,
                'person_profession_id' => $profession->id,
                'department' => 'Cast',
                'job' => 'Lead Actor',
                'character_name' => 'Detective Vale',
                'billing_order' => 2,
                'credited_as' => 'Ava Mercer',
                'is_principal' => '1',
                'episode_id' => $episode->id,
            ])
            ->assertRedirect(route('admin.credits.edit', $credit));

        $profession->refresh();
        $credit->refresh();

        $this->assertSame('Lead Actor', $profession->profession);
        $this->assertSame('Lead Actor', $credit->job);
        $this->assertSame(2, $credit->billing_order);

        $this->actingAs($editor)
            ->delete(route('admin.credits.destroy', $credit))
            ->assertRedirect(route('admin.titles.edit', $series));

        $this->assertSoftDeleted('credits', ['id' => $credit->id]);

        $this->actingAs($editor)
            ->delete(route('admin.professions.destroy', $profession))
            ->assertRedirect(route('admin.people.edit', $person));

        $this->assertDatabaseMissing('person_professions', ['id' => $profession->id]);

        $this->actingAs($editor)
            ->delete(route('admin.people.destroy', $person))
            ->assertRedirect(route('admin.people.index'));

        $this->assertSoftDeleted('people', ['id' => $person->id]);
    }

    public function test_editor_can_manage_genres_seasons_and_episodes_and_cannot_break_tv_hierarchy(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->post(route('admin.genres.store'), [
                'name' => 'Sci-Fi',
                'slug' => 'sci-fi',
                'description' => 'Science fiction.',
            ])
            ->assertRedirect();

        $genre = Genre::query()->where('slug', 'sci-fi')->firstOrFail();

        $this->actingAs($editor)
            ->patch(route('admin.genres.update', $genre), [
                'name' => 'Science Fiction',
                'slug' => 'science-fiction',
                'description' => 'Updated description.',
            ])
            ->assertRedirect(route('admin.genres.edit', $genre->fresh()));

        $genre->refresh();
        $this->assertSame('Science Fiction', $genre->name);

        $series = Title::factory()->series()->create();

        $this->actingAs($editor)
            ->post(route('admin.titles.seasons.store', $series), [
                'season' => [
                    'name' => 'Season 1',
                    'slug' => 'series-season-1',
                    'season_number' => 1,
                    'summary' => 'The first season.',
                    'release_year' => 2024,
                    'meta_title' => 'Season 1',
                    'meta_description' => 'Season 1 overview.',
                ],
            ])
            ->assertRedirect();

        $season = Season::query()->where('slug', 'series-season-1')->firstOrFail();

        $this->actingAs($editor)
            ->patch(route('admin.seasons.update', $season), [
                'name' => 'Season One',
                'slug' => 'season-one',
                'season_number' => 1,
                'summary' => 'Updated season summary.',
                'release_year' => 2025,
                'meta_title' => 'Season One',
                'meta_description' => 'Updated season overview.',
            ])
            ->assertRedirect(route('admin.seasons.edit', $season));

        $season->refresh();
        $this->assertSame('Season One', $season->name);

        $this->actingAs($editor)
            ->post(route('admin.seasons.episodes.store', $season), [
                'episode' => [
                    'name' => 'Pilot',
                    'original_name' => 'Pilot',
                    'slug' => 'series-pilot',
                    'release_year' => 2025,
                    'release_date' => '2025-01-01',
                    'runtime_minutes' => 48,
                    'age_rating' => 'TV-14',
                    'origin_country' => 'US',
                    'original_language' => 'en',
                    'season_number' => 1,
                    'episode_number' => 1,
                    'absolute_number' => 1,
                    'production_code' => 'S1E1',
                    'aired_at' => '2025-01-01',
                    'is_published' => '1',
                    'plot_outline' => 'Pilot outline.',
                    'synopsis' => 'Pilot synopsis.',
                    'tagline' => 'The beginning.',
                    'meta_title' => 'Pilot',
                    'meta_description' => 'Pilot description.',
                    'search_keywords' => 'pilot',
                ],
            ])
            ->assertRedirect();

        $episode = Episode::query()->where('series_id', $series->id)->firstOrFail();
        $episodeTitle = $episode->title()->firstOrFail();

        $this->actingAs($editor)
            ->patch(route('admin.episodes.update', $episode), [
                'name' => 'Pilot Part I',
                'original_name' => 'Pilot',
                'slug' => 'series-pilot-part-i',
                'release_year' => 2025,
                'release_date' => '2025-01-08',
                'runtime_minutes' => 50,
                'age_rating' => 'TV-14',
                'origin_country' => 'US',
                'original_language' => 'en',
                'season_number' => 1,
                'episode_number' => 1,
                'absolute_number' => 1,
                'production_code' => 'S1E1A',
                'aired_at' => '2025-01-08',
                'is_published' => '1',
                'plot_outline' => 'Updated outline.',
                'synopsis' => 'Updated synopsis.',
                'tagline' => 'Still the beginning.',
                'meta_title' => 'Pilot Part I',
                'meta_description' => 'Updated episode description.',
                'search_keywords' => 'pilot part i',
            ])
            ->assertRedirect(route('admin.episodes.edit', $episode));

        $episode->refresh();
        $episodeTitle->refresh();

        $this->assertSame('Pilot Part I', $episodeTitle->name);
        $this->assertSame('series-pilot-part-i', $episodeTitle->slug);

        $this->actingAs($editor)
            ->from(route('admin.titles.edit', $series))
            ->patch(route('admin.titles.update', $series), [
                'name' => $series->name,
                'original_name' => $series->original_name,
                'slug' => $series->slug,
                'title_type' => TitleType::Movie->value,
                'release_year' => $series->release_year,
                'end_year' => $series->end_year,
                'release_date' => optional($series->release_date)->toDateString(),
                'runtime_minutes' => $series->runtime_minutes,
                'age_rating' => $series->age_rating,
                'origin_country' => $series->origin_country,
                'original_language' => $series->original_language,
                'is_published' => $series->is_published ? '1' : '0',
                'genre_ids' => [],
                'plot_outline' => $series->plot_outline,
                'synopsis' => $series->synopsis,
                'tagline' => $series->tagline,
                'meta_title' => $series->meta_title,
                'meta_description' => $series->meta_description,
                'search_keywords' => $series->search_keywords,
            ])
            ->assertRedirect(route('admin.titles.edit', $series))
            ->assertSessionHasErrors(['title_type']);

        $this->actingAs($editor)
            ->from(route('admin.titles.edit', $episodeTitle))
            ->patch(route('admin.titles.update', $episodeTitle), [
                'name' => $episodeTitle->name,
                'original_name' => $episodeTitle->original_name,
                'slug' => $episodeTitle->slug,
                'title_type' => TitleType::Movie->value,
                'release_year' => $episodeTitle->release_year,
                'end_year' => $episodeTitle->end_year,
                'release_date' => optional($episodeTitle->release_date)->toDateString(),
                'runtime_minutes' => $episodeTitle->runtime_minutes,
                'age_rating' => $episodeTitle->age_rating,
                'origin_country' => $episodeTitle->origin_country,
                'original_language' => $episodeTitle->original_language,
                'is_published' => $episodeTitle->is_published ? '1' : '0',
                'genre_ids' => [],
                'plot_outline' => $episodeTitle->plot_outline,
                'synopsis' => $episodeTitle->synopsis,
                'tagline' => $episodeTitle->tagline,
                'meta_title' => $episodeTitle->meta_title,
                'meta_description' => $episodeTitle->meta_description,
                'search_keywords' => $episodeTitle->search_keywords,
            ])
            ->assertRedirect(route('admin.titles.edit', $episodeTitle))
            ->assertSessionHasErrors(['title_type']);

        $this->actingAs($editor)
            ->delete(route('admin.episodes.destroy', $episode))
            ->assertRedirect(route('admin.seasons.edit', $season));

        $this->assertSoftDeleted('episodes', ['id' => $episode->id]);
        $this->assertSoftDeleted('titles', ['id' => $episodeTitle->id]);

        $this->actingAs($editor)
            ->delete(route('admin.seasons.destroy', $season))
            ->assertRedirect(route('admin.titles.edit', $series));

        $this->assertSoftDeleted('seasons', ['id' => $season->id]);

        $this->actingAs($editor)
            ->delete(route('admin.genres.destroy', $genre))
            ->assertRedirect(route('admin.genres.index'));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }
}
