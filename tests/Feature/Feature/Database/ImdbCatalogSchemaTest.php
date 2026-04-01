<?php

namespace Tests\Feature\Feature\Database;

use App\ListVisibility;
use App\Models\Company;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Rating;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\Models\User;
use App\Models\UserList;
use App\ReviewStatus;
use App\TitleType;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImdbCatalogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_catalog_tables_and_columns_exist(): void
    {
        foreach ([
            'titles',
            'people',
            'genres',
            'companies',
            'credits',
            'media_assets',
            'title_statistics',
            'ratings',
            'reviews',
            'review_votes',
            'user_lists',
            'list_items',
            'reports',
            'moderation_actions',
            'genre_title',
            'company_title',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), sprintf('Missing table [%s].', $table));
        }

        $this->assertTrue(Schema::hasColumns('titles', [
            'name',
            'original_name',
            'slug',
            'title_type',
            'release_year',
            'runtime_minutes',
            'plot_outline',
            'synopsis',
            'origin_country',
            'original_language',
            'is_published',
        ]));

        $this->assertTrue(Schema::hasColumns('people', [
            'name',
            'slug',
            'biography',
            'known_for_department',
            'birth_date',
            'birth_place',
            'is_published',
        ]));

        $this->assertTrue(Schema::hasColumns('reviews', [
            'user_id',
            'title_id',
            'headline',
            'body',
            'contains_spoilers',
            'status',
            'moderated_by',
            'moderated_at',
            'published_at',
        ]));
    }

    public function test_title_relationships_load_genres_companies_people_media_and_statistics(): void
    {
        $title = Title::factory()->create([
            'title_type' => TitleType::Movie,
        ]);
        $genre = Genre::factory()->create();
        $company = Company::factory()->create();
        $person = Person::factory()->create();
        $asset = MediaAsset::factory()->for($title, 'mediable')->poster()->create();
        $statistic = TitleStatistic::factory()->for($title)->create();

        $title->genres()->attach($genre);
        $title->companies()->attach($company, ['relationship' => 'production']);
        $title->credits()->create([
            'person_id' => $person->id,
            'department' => 'Cast',
            'job' => 'Actor',
            'character_name' => 'Captain Mira Sol',
            'billing_order' => 1,
            'is_principal' => true,
        ]);

        $title->load([
            'genres',
            'companies',
            'credits.person',
            'mediaAssets',
            'statistic',
        ]);

        $this->assertTrue($title->genres->contains($genre));
        $this->assertTrue($title->companies->contains($company));
        $this->assertSame('Captain Mira Sol', $title->credits->first()?->character_name);
        $this->assertTrue($title->credits->first()?->person->is($person));
        $this->assertTrue($title->mediaAssets->contains($asset));
        $this->assertTrue($title->statistic->is($statistic));
    }

    public function test_ratings_are_unique_per_user_and_title(): void
    {
        $user = User::factory()->create();
        $title = Title::factory()->create();

        Rating::factory()->for($user)->for($title)->create();

        $this->expectException(QueryException::class);

        Rating::factory()->for($user)->for($title)->create();
    }

    public function test_reviews_lists_and_reports_are_related_to_users_titles_and_moderators(): void
    {
        $user = User::factory()->create();
        $moderator = User::factory()->moderator()->create();
        $title = Title::factory()->create();

        $review = Review::factory()
            ->for($user, 'author')
            ->for($title)
            ->published()
            ->create([
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
            ]);

        $vote = ReviewVote::factory()->for($review)->for($moderator)->helpful()->create();
        $list = UserList::factory()->for($user)->public()->create([
            'visibility' => ListVisibility::Public,
        ]);

        $list->items()->create([
            'title_id' => $title->id,
            'notes' => 'Essential sci-fi viewing.',
            'position' => 1,
        ]);

        $report = Report::factory()->for($moderator, 'reporter')->for($review, 'reportable')->open()->create();

        $review->load(['author', 'moderator', 'votes', 'title']);
        $list->load('items.title');
        $report->load(['reporter', 'reportable']);

        $this->assertSame(ReviewStatus::Published, $review->status);
        $this->assertTrue($review->author->is($user));
        $this->assertTrue($review->moderator->is($moderator));
        $this->assertTrue($review->votes->contains($vote));
        $this->assertTrue($review->title->is($title));
        $this->assertSame(ListVisibility::Public, $list->visibility);
        $this->assertTrue($list->items->first()->title->is($title));
        $this->assertTrue($report->reporter->is($moderator));
        $this->assertTrue($report->reportable->is($review));
    }

    public function test_review_votes_are_unique_per_review_and_user(): void
    {
        $review = Review::factory()->create();
        $user = User::factory()->create();

        ReviewVote::factory()->for($review)->for($user)->create();

        $this->expectException(QueryException::class);

        ReviewVote::factory()->for($review)->for($user)->create();
    }
}
