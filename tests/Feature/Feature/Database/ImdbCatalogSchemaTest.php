<?php

namespace Tests\Feature\Feature\Database;

use App\Enums\ContributionAction;
use App\Enums\ContributionStatus;
use App\Enums\MediaKind;
use App\Enums\TitleRelationshipType;
use App\Models\Award;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Contribution;
use App\Models\Episode;
use App\Models\Person;
use App\Models\PersonImage;
use App\Models\PersonProfession;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleImage;
use App\Models\TitleRelationship;
use App\Models\TitleTranslation;
use App\Models\TitleVideo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImdbCatalogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_expanded_catalog_tables_and_columns_exist(): void
    {
        foreach ([
            'titles',
            'title_translations',
            'people',
            'person_professions',
            'genres',
            'companies',
            'credits',
            'seasons',
            'episodes',
            'imdb_title_imports',
            'media_assets',
            'title_statistics',
            'ratings',
            'reviews',
            'review_votes',
            'user_lists',
            'list_items',
            'title_relationships',
            'awards',
            'award_events',
            'award_categories',
            'award_nominations',
            'contributions',
            'reports',
            'moderation_actions',
            'notifications',
            'genre_title',
            'company_title',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), sprintf('Missing table [%s].', $table));
        }

        $this->assertTrue(Schema::hasColumns('titles', [
            'imdb_id',
            'imdb_type',
            'runtime_seconds',
            'sort_title',
            'canonical_title_id',
            'meta_title',
            'meta_description',
            'search_keywords',
            'imdb_genres',
            'imdb_interests',
            'imdb_origin_countries',
            'imdb_spoken_languages',
            'imdb_payload',
            'deleted_at',
        ]));

        $this->assertTrue(Schema::hasColumns('title_translations', [
            'locale',
            'localized_title',
            'localized_slug',
            'localized_plot_outline',
            'localized_synopsis',
        ]));

        $this->assertTrue(Schema::hasColumns('credits', [
            'person_profession_id',
            'episode_id',
            'credited_as',
            'imdb_source_group',
            'deleted_at',
        ]));

        $this->assertTrue(Schema::hasColumns('list_items', [
            'watch_state',
            'started_at',
            'watched_at',
            'rewatch_count',
        ]));

        $this->assertTrue(Schema::hasColumns('media_assets', [
            'provider',
            'provider_key',
            'language',
            'duration_seconds',
            'metadata',
            'published_at',
            'deleted_at',
        ]));

        $this->assertTrue(Schema::hasColumns('title_statistics', [
            'rating_distribution',
            'metacritic_score',
            'metacritic_review_count',
        ]));

        $this->assertTrue(Schema::hasColumns('people', [
            'imdb_id',
            'imdb_alternative_names',
            'imdb_primary_professions',
            'imdb_payload',
        ]));

        $this->assertTrue(Schema::hasColumns('imdb_title_imports', [
            'imdb_id',
            'source_url',
            'storage_path',
            'payload_hash',
            'payload',
            'downloaded_at',
            'imported_at',
        ]));
    }

    public function test_catalog_relationships_support_translations_media_seasons_awards_and_contributions(): void
    {
        $user = User::factory()->create();
        $series = Title::factory()->series()->create([
            'name' => 'Static Bloom',
            'slug' => 'static-bloom',
        ]);
        $relatedTitle = Title::factory()->movie()->create([
            'name' => 'Northern Signal',
            'slug' => 'northern-signal',
        ]);
        $season = Season::factory()->for($series, 'series')->create([
            'season_number' => 1,
            'slug' => 'static-bloom-season-1',
        ]);
        $episodeTitle = Title::factory()->episode()->create([
            'name' => 'Static Bloom: Pilot',
            'slug' => 'static-bloom-pilot',
        ]);
        $episode = Episode::factory()
            ->for($episodeTitle, 'title')
            ->for($series, 'series')
            ->for($season, 'season')
            ->create([
                'season_number' => 1,
                'episode_number' => 1,
            ]);
        $person = Person::factory()->create([
            'name' => 'Ava Mercer',
            'slug' => 'ava-mercer',
        ]);
        $profession = PersonProfession::factory()->for($person)->primary()->create([
            'department' => 'Cast',
            'profession' => 'Actor',
        ]);

        $series->credits()->create([
            'person_id' => $person->id,
            'department' => 'Cast',
            'job' => 'Actor',
            'character_name' => 'Tess Mora',
            'billing_order' => 1,
            'is_principal' => true,
            'person_profession_id' => $profession->id,
            'episode_id' => $episode->id,
            'credited_as' => 'Special Guest Star',
        ]);

        $translation = TitleTranslation::factory()->for($series)->create([
            'locale' => 'lt',
            'localized_title' => 'Statinis Žydėjimas',
            'localized_slug' => 'statinis-zydejimas',
        ]);

        $titleImage = TitleImage::factory()->for($series, 'mediable')->create([
            'kind' => MediaKind::Poster,
        ]);
        $titleVideo = TitleVideo::factory()->for($series, 'mediable')->create([
            'kind' => MediaKind::Trailer,
        ]);
        $personImage = PersonImage::factory()->for($person, 'mediable')->create([
            'kind' => MediaKind::Headshot,
        ]);

        $relationship = TitleRelationship::factory()->create([
            'from_title_id' => $series->id,
            'to_title_id' => $relatedTitle->id,
            'relationship_type' => TitleRelationshipType::Similar,
        ]);

        $award = Award::factory()->create([
            'name' => 'Celestial Screen Awards',
            'slug' => 'celestial-screen-awards',
        ]);
        $event = AwardEvent::factory()->for($award)->create([
            'name' => '2025 Celestial Screen Awards',
            'slug' => '2025-celestial-screen-awards',
            'year' => 2025,
        ]);
        $category = AwardCategory::factory()->for($award)->create([
            'name' => 'Best Episode',
            'slug' => 'best-episode',
            'recipient_scope' => 'episode',
        ]);
        $nomination = AwardNomination::factory()->for($event)->for($category, 'awardCategory')->create([
            'title_id' => $series->id,
            'episode_id' => $episode->id,
            'credited_name' => $episodeTitle->name,
        ]);

        $contribution = Contribution::factory()->for($user)->for($series, 'contributable')->create([
            'action' => ContributionAction::Update,
            'status' => ContributionStatus::Approved,
        ]);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'catalog.review-approved',
            'data' => [
                'title' => $series->name,
            ],
        ]);

        $series->load([
            'translations',
            'seasons.episodes.title',
            'credits.person',
            'credits.profession',
            'credits.episode.title',
            'titleImages',
            'titleVideos',
            'outgoingRelationships.toTitle',
            'awardNominations.awardEvent',
            'contributions',
        ]);
        $person->load([
            'professions',
            'personImages',
        ]);

        $this->assertTrue($series->translations->contains($translation));
        $this->assertTrue($series->seasons->first()->episodes->first()->title->is($episodeTitle));
        $this->assertSame('Special Guest Star', $series->credits->first()->credited_as);
        $this->assertTrue($series->credits->first()->profession->is($profession));
        $this->assertTrue($series->credits->first()->episode->is($episode));
        $this->assertTrue($series->titleImages->contains($titleImage));
        $this->assertTrue($series->titleVideos->contains($titleVideo));
        $this->assertTrue($person->personImages->contains($personImage));
        $this->assertTrue($series->outgoingRelationships->first()->toTitle->is($relatedTitle));
        $this->assertTrue($series->awardNominations->first()->is($nomination));
        $this->assertTrue($series->contributions->first()->is($contribution));
        $this->assertSame(1, $user->notifications()->count());
        $this->assertTrue($relationship->fromTitle->is($series));
    }
}
