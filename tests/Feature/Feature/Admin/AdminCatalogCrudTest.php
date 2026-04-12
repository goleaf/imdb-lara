<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\TitleType;
use App\Livewire\Admin\PersonProfessionEditor;
use App\Livewire\Pages\Admin\CreditsPage;
use App\Livewire\Pages\Admin\EpisodesPage;
use App\Livewire\Pages\Admin\GenreCreatePage;
use App\Livewire\Pages\Admin\GenreEditPage;
use App\Livewire\Pages\Admin\PersonCreatePage;
use App\Livewire\Pages\Admin\PersonEditPage;
use App\Livewire\Pages\Admin\SeasonsPage;
use App\Livewire\Pages\Admin\TitleCreatePage;
use App\Livewire\Pages\Admin\TitleEditPage;
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
use Livewire\Livewire;
use Tests\TestCase;

class AdminCatalogCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_catalog_surface_does_not_register_controller_mutation_routes(): void
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
            $this->assertFalse(Route::has($routeName), $routeName.' should not be registered on the Livewire-only admin surface.');
        }
    }

    public function test_editor_can_create_update_and_delete_titles_with_genres_from_livewire_pages(): void
    {
        $editor = User::factory()->editor()->create();
        $firstGenre = Genre::factory()->create(['name' => 'Mystery', 'slug' => 'mystery']);
        $secondGenre = Genre::factory()->create(['name' => 'Noir', 'slug' => 'noir']);

        $this->actingAs($editor);

        $createPage = Livewire::test(TitleCreatePage::class)
            ->set('name', 'Shadow Harbor')
            ->set('original_name', 'Shadow Harbor')
            ->set('slug', 'shadow-harbor')
            ->set('title_type', TitleType::Movie->value)
            ->set('release_year', 2024)
            ->set('release_date', '2024-05-01')
            ->set('runtime_minutes', 118)
            ->set('age_rating', 'PG-13')
            ->set('origin_country', 'US')
            ->set('original_language', 'en')
            ->set('is_published', true)
            ->set('genre_ids', [$firstGenre->id])
            ->set('plot_outline', 'A detective returns home.')
            ->set('synopsis', 'A full synopsis for Shadow Harbor.')
            ->set('tagline', 'Everyone owes the harbor something.')
            ->set('meta_title', 'Shadow Harbor')
            ->set('meta_description', 'Mystery drama.')
            ->set('search_keywords', 'harbor, mystery')
            ->call('saveTitle');

        $title = Title::query()->where('slug', 'shadow-harbor')->firstOrFail();

        $createPage->assertRedirect(route('admin.titles.edit', $title));
        $this->assertSame(TitleType::Movie, $title->title_type);
        $this->assertDatabaseHas('genre_title', [
            'title_id' => $title->id,
            'genre_id' => $firstGenre->id,
        ]);

        $editPage = Livewire::test(TitleEditPage::class, ['title' => $title])
            ->set('name', 'Shadow Harbor: Director Cut')
            ->set('slug', 'shadow-harbor-directors-cut')
            ->set('release_year', 2025)
            ->set('release_date', '2025-01-10')
            ->set('runtime_minutes', 124)
            ->set('age_rating', 'R')
            ->set('origin_country', 'GB')
            ->set('is_published', false)
            ->set('genre_ids', [$secondGenre->id])
            ->set('plot_outline', 'Updated outline.')
            ->set('synopsis', 'Updated synopsis.')
            ->set('tagline', 'Updated tagline.')
            ->set('meta_title', 'Shadow Harbor Director Cut')
            ->set('meta_description', 'Updated SEO description.')
            ->set('search_keywords', 'director cut')
            ->call('saveTitle');

        $title->refresh();

        $editPage->assertRedirect(route('admin.titles.edit', $title));
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

        Livewire::test(TitleEditPage::class, ['title' => $title])
            ->call('deleteTitle')
            ->assertRedirect(route('admin.titles.index'));

        $this->assertSoftDeleted('titles', ['id' => $title->id]);
    }

    public function test_editor_can_manage_people_professions_and_credits_from_livewire_pages(): void
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

        $this->actingAs($editor);

        $createPersonPage = Livewire::test(PersonCreatePage::class)
            ->set('name', 'Ava Mercer')
            ->set('alternate_names', 'A. Mercer')
            ->set('slug', 'ava-mercer')
            ->set('known_for_department', 'Acting')
            ->set('popularity_rank', 18)
            ->set('birth_date', '1988-04-12')
            ->set('birth_place', 'Vilnius, Lithuania')
            ->set('nationality', 'Lithuanian')
            ->set('short_biography', 'Award-winning actor.')
            ->set('biography', 'Longer person biography.')
            ->set('meta_title', 'Ava Mercer')
            ->set('meta_description', 'Actor biography.')
            ->set('search_keywords', 'ava, mercer')
            ->set('is_published', true)
            ->call('savePerson');

        $person = Person::query()->where('slug', 'ava-mercer')->firstOrFail();
        $createPersonPage->assertRedirect(route('admin.people.edit', $person));

        $otherPerson = Person::factory()->create();
        $otherProfession = PersonProfession::factory()->for($otherPerson)->create();

        $createProfessionEditor = Livewire::test(PersonProfessionEditor::class, [
            'person' => $person,
            'defaultSortOrder' => 0,
        ])
            ->set('department', 'Cast')
            ->set('professionName', 'Actor')
            ->set('is_primary', true)
            ->set('sort_order', 0)
            ->call('save')
            ->assertHasNoErrors();

        $createProfessionEditor->assertSet('professionRecord.person_id', $person->id);

        $profession = PersonProfession::query()->where('person_id', $person->id)->firstOrFail();

        Livewire::test(CreditsPage::class)
            ->set('title_id', $series->id)
            ->set('person_id', $person->id)
            ->set('person_profession_id', $otherProfession->id)
            ->set('department', 'Cast')
            ->set('job', 'Actor')
            ->set('character_name', 'Detective Vale')
            ->set('billing_order', 1)
            ->set('credited_as', 'Ava Mercer')
            ->set('is_principal', true)
            ->set('episode_id', $otherEpisode->id)
            ->call('saveCredit')
            ->assertHasErrors(['person_profession_id', 'episode_id']);

        $createCreditPage = Livewire::test(CreditsPage::class)
            ->set('title_id', $series->id)
            ->set('person_id', $person->id)
            ->set('person_profession_id', $profession->id)
            ->set('department', 'Cast')
            ->set('job', 'Actor')
            ->set('character_name', 'Detective Vale')
            ->set('billing_order', 1)
            ->set('credited_as', 'Ava Mercer')
            ->set('is_principal', true)
            ->set('episode_id', $episode->id)
            ->call('saveCredit');

        $credit = Credit::query()
            ->where('title_id', $series->id)
            ->where('person_id', $person->id)
            ->firstOrFail();

        $createCreditPage->assertRedirect(route('admin.credits.edit', $credit));

        Livewire::test(PersonProfessionEditor::class, [
            'person' => $person,
            'professionRecord' => $profession,
        ])
            ->set('department', 'Cast')
            ->set('professionName', 'Lead Actor')
            ->set('is_primary', true)
            ->set('sort_order', 0)
            ->call('save')
            ->assertHasNoErrors();

        $editCreditPage = Livewire::test(CreditsPage::class, ['credit' => $credit])
            ->set('person_profession_id', $profession->id)
            ->set('job', 'Lead Actor')
            ->set('billing_order', 2)
            ->call('saveCredit');

        $profession->refresh();
        $credit->refresh();

        $editCreditPage->assertRedirect(route('admin.credits.edit', $credit));
        $this->assertSame('Lead Actor', $profession->profession);
        $this->assertSame('Lead Actor', $credit->job);
        $this->assertSame(2, $credit->billing_order);

        Livewire::test(CreditsPage::class, ['credit' => $credit])
            ->call('deleteCredit')
            ->assertRedirect(route('admin.dashboard'));

        Livewire::test(PersonEditPage::class, ['person' => $person])
            ->call('deletePerson')
            ->assertRedirect(route('admin.people.index'));

        $this->assertSoftDeleted('people', ['id' => $person->id]);
    }

    public function test_editor_can_create_update_and_delete_genres_from_livewire_pages(): void
    {
        $editor = User::factory()->editor()->create();
        $this->actingAs($editor);

        $createPage = Livewire::test(GenreCreatePage::class)
            ->set('name', 'Psychological Thriller')
            ->set('slug', 'psychological-thriller')
            ->set('description', 'Mind-bending suspense stories.')
            ->call('saveGenre');

        $genre = Genre::query()->where('slug', 'psychological-thriller')->firstOrFail();
        $createPage->assertRedirect(route('admin.genres.edit', $genre));

        $editPage = Livewire::test(GenreEditPage::class, ['genre' => $genre])
            ->set('name', 'Psychological Noir')
            ->set('slug', 'psychological-noir')
            ->set('description', 'Dark suspense taxonomy.')
            ->call('saveGenre');

        $genre->refresh();

        $editPage->assertRedirect(route('admin.genres.edit', $genre));
        $this->assertSame('Psychological Noir', $genre->name);
        $this->assertSame('psychological-noir', $genre->slug);

        Livewire::test(GenreEditPage::class, ['genre' => $genre])
            ->call('deleteGenre')
            ->assertRedirect(route('admin.genres.index'));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_editor_can_manage_seasons_and_episodes_from_livewire_pages(): void
    {
        $editor = User::factory()->editor()->create();
        $series = Title::factory()->series()->create([
            'name' => 'Atlas Station',
            'slug' => 'atlas-station',
            'title_type' => TitleType::Series,
        ]);

        $this->actingAs($editor);

        Livewire::test(TitleEditPage::class, ['title' => $series])
            ->set('season.name', 'Season 1')
            ->set('season.slug', 'atlas-station-season-1')
            ->set('season.season_number', 1)
            ->set('season.release_year', 2024)
            ->call('saveSeason');

        $season = Season::query()->where('slug', 'atlas-station-season-1')->firstOrFail();

        Livewire::test(SeasonsPage::class, ['season' => $season])
            ->set('name', 'Season One')
            ->set('slug', 'atlas-station-season-one')
            ->call('saveSeason');

        $season->refresh();
        $this->assertSame('Season One', $season->name);

        Livewire::test(SeasonsPage::class, ['season' => $season])
            ->set('episode.name', 'Pilot')
            ->set('episode.slug', 'atlas-station-pilot')
            ->set('episode.release_year', 2024)
            ->set('episode.is_published', true)
            ->set('episode.season_number', 1)
            ->set('episode.episode_number', 1)
            ->call('saveEpisode');

        $episode = Episode::query()
            ->where('season_id', $season->id)
            ->where('episode_number', 1)
            ->firstOrFail();

        Livewire::test(EpisodesPage::class, ['episode' => $episode])
            ->set('name', 'Pilot Part I')
            ->set('slug', 'atlas-station-pilot-part-i')
            ->set('episode_number', 2)
            ->call('saveEpisode');

        $episode->refresh();
        $episode->load('title');

        $this->assertSame('Pilot Part I', $episode->title?->name);
        $this->assertSame('atlas-station-pilot-part-i', $episode->title?->slug);
        $this->assertSame(2, $episode->episode_number);

        Livewire::test(EpisodesPage::class, ['episode' => $episode])
            ->call('deleteEpisode')
            ->assertRedirect(route('admin.seasons.edit', $season));

        Livewire::test(SeasonsPage::class, ['season' => $season])
            ->call('deleteSeason')
            ->assertRedirect(route('admin.titles.edit', $series));

        $this->assertSoftDeleted('seasons', ['id' => $season->id]);
    }
}
