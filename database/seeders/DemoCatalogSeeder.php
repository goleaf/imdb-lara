<?php

namespace Database\Seeders;

use App\Actions\Lists\EnsureWatchlistAction;
use App\Enums\ContributionAction;
use App\Enums\ContributionStatus;
use App\Models\Award;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Contribution;
use App\Models\Episode;
use App\Models\ListItem;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleTranslation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Platform Owner',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
        ]);

        User::factory()->admin()->create([
            'name' => 'Admin Curator',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $editor = User::factory()->editor()->create([
            'name' => 'Catalog Editor',
            'username' => 'editor',
            'email' => 'editor@example.com',
        ]);

        User::factory()->moderator()->create([
            'name' => 'Review Moderator',
            'username' => 'moderator',
            'email' => 'moderator@example.com',
        ]);

        $member = User::factory()->create([
            'name' => 'Member Viewer',
            'username' => 'member',
            'email' => 'member@example.com',
        ]);

        $contributors = User::factory()->count(2)->contributor()->create()
            ->prepend(User::factory()->contributor()->create([
                'name' => 'Lead Contributor',
                'username' => 'contributor',
                'email' => 'contributor@example.com',
            ]))
            ->values();

        $movieTitles = Title::factory()->count(5)->movie()->create();
        $seriesTitles = Title::factory()->count(3)->series()->create();
        $episodeTitles = Title::factory()->count(4)->episode()->create();

        $seasons = collect([
            Season::factory()->for($seriesTitles[0], 'series')->create([
                'name' => 'Season 1',
                'slug' => Str::slug($seriesTitles[0]->name.' season 1'),
                'season_number' => 1,
            ]),
            Season::factory()->for($seriesTitles[1], 'series')->create([
                'name' => 'Season 1',
                'slug' => Str::slug($seriesTitles[1]->name.' season 1'),
                'season_number' => 1,
            ]),
            Season::factory()->for($seriesTitles[2], 'series')->create([
                'name' => 'Season 1',
                'slug' => Str::slug($seriesTitles[2]->name.' season 1'),
                'season_number' => 1,
            ]),
        ]);

        $episodes = collect();

        $episodes->push(Episode::factory()->for($episodeTitles[0], 'title')->for($seriesTitles[0], 'series')->for($seasons[0], 'season')->create([
            'season_number' => 1,
            'episode_number' => 1,
            'absolute_number' => 1,
        ]));
        $episodes->push(Episode::factory()->for($episodeTitles[1], 'title')->for($seriesTitles[0], 'series')->for($seasons[0], 'season')->create([
            'season_number' => 1,
            'episode_number' => 2,
            'absolute_number' => 2,
        ]));
        $episodes->push(Episode::factory()->for($episodeTitles[2], 'title')->for($seriesTitles[1], 'series')->for($seasons[1], 'season')->create([
            'season_number' => 1,
            'episode_number' => 1,
            'absolute_number' => 1,
        ]));
        $episodes->push(Episode::factory()->for($episodeTitles[3], 'title')->for($seriesTitles[2], 'series')->for($seasons[2], 'season')->create([
            'season_number' => 1,
            'episode_number' => 1,
            'absolute_number' => 1,
        ]));

        Person::factory()->count(8)->create();

        TitleTranslation::factory()->for($movieTitles[0])->create([
            'locale' => 'lt',
        ]);
        TitleTranslation::factory()->for($seriesTitles[0])->create([
            'locale' => 'fr',
        ]);

        $award = Award::factory()->create([
            'name' => 'Screenbase Honors',
            'slug' => 'screenbase-honors',
        ]);
        $event = AwardEvent::factory()->for($award)->create([
            'name' => '2025 Screenbase Honors',
            'slug' => '2025-screenbase-honors',
            'year' => 2025,
        ]);

        $categories = collect([
            AwardCategory::factory()->for($award)->create([
                'name' => 'Best Picture',
                'slug' => 'best-picture',
            ]),
            AwardCategory::factory()->for($award)->create([
                'name' => 'Best Series',
                'slug' => 'best-series',
            ]),
            AwardCategory::factory()->for($award)->create([
                'name' => 'Best Episode',
                'slug' => 'best-episode',
                'recipient_scope' => 'episode',
            ]),
        ]);

        AwardNomination::factory()->for($event)->for($categories[0], 'awardCategory')->winner()->create([
            'title_id' => $movieTitles[0]->id,
        ]);
        AwardNomination::factory()->for($event)->for($categories[1], 'awardCategory')->create([
            'title_id' => $seriesTitles[0]->id,
        ]);
        AwardNomination::factory()->for($event)->for($categories[2], 'awardCategory')->create([
            'title_id' => $seriesTitles[0]->id,
            'episode_id' => $episodes[0]->id,
            'credited_name' => $episodeTitles[0]->name,
        ]);

        Contribution::factory()->for($editor)->for($movieTitles[1], 'contributable')->create([
            'action' => ContributionAction::Update,
            'status' => ContributionStatus::Approved,
        ]);
        Contribution::factory()->for($contributors[0])->for($seriesTitles[1], 'contributable')->create([
            'action' => ContributionAction::Create,
            'status' => ContributionStatus::Submitted,
        ]);

        $watchlist = (new EnsureWatchlistAction)->handle($member);

        ListItem::factory()->for($watchlist, 'userList')->for($movieTitles[0], 'title')->completed()->create([
            'position' => 1,
        ]);

        $member->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'demo.watchlist.seeded',
            'data' => [
                'title' => $movieTitles[0]->name,
                'watchlist_slug' => $watchlist->slug,
            ],
        ]);
    }
}
