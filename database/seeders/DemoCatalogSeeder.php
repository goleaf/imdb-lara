<?php

namespace Database\Seeders;

use App\Actions\Lists\EnsureWatchlistAction;
use App\Actions\Titles\RefreshTitleStatisticsAction;
use App\ListVisibility;
use App\Models\Company;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\ModerationAction;
use App\Models\Person;
use App\Models\Rating;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use App\ReportStatus;
use App\TitleType;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Curator',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $moderator = User::factory()->moderator()->create([
            'name' => 'Review Moderator',
            'username' => 'moderator',
            'email' => 'moderator@example.com',
        ]);

        $member = User::factory()->create([
            'name' => 'Member Viewer',
            'username' => 'member',
            'email' => 'member@example.com',
        ]);

        $critics = User::factory()->count(3)->create();
        $people = Person::factory()->count(8)->create();
        $genres = Genre::factory()->count(6)->create();
        $companies = Company::factory()->count(4)->create();

        $titles = collect([
            Title::factory()->movie()->create(['name' => 'Northern Signal', 'title_type' => TitleType::Movie]),
            Title::factory()->movie()->create(['name' => 'Afterlight Protocol', 'title_type' => TitleType::Movie]),
            Title::factory()->movie()->create(['name' => 'The Glass Atlas', 'title_type' => TitleType::Movie]),
            Title::factory()->series()->create(['name' => 'Harbor Nine', 'title_type' => TitleType::Series]),
            Title::factory()->series()->create(['name' => 'Static Bloom', 'title_type' => TitleType::Series]),
            Title::factory()->documentary()->create(['name' => 'Worlds Beneath Ice', 'title_type' => TitleType::Documentary]),
            Title::factory()->documentary()->create(['name' => 'Signal to Summit', 'title_type' => TitleType::Documentary]),
            Title::factory()->movie()->create(['name' => 'Mercury Vale', 'title_type' => TitleType::Movie]),
        ]);

        $titles->each(function (Title $title, int $index) use ($genres, $companies, $people): void {
            $title->genres()->attach($genres->random(2)->pluck('id'));
            $title->companies()->attach($companies->random(2)->pluck('id')->mapWithKeys(
                fn (int $companyId): array => [$companyId => ['relationship' => 'production']],
            ));

            $cast = $people->slice($index, 3);

            foreach ($cast as $castIndex => $person) {
                $title->credits()->create([
                    'person_id' => $person->id,
                    'department' => 'Cast',
                    'job' => 'Actor',
                    'character_name' => fake()->firstName().' '.fake()->lastName(),
                    'billing_order' => $castIndex + 1,
                    'is_principal' => $castIndex < 2,
                ]);
            }

            MediaAsset::factory()->for($title, 'mediable')->poster()->create();
        });

        $watchlist = app(EnsureWatchlistAction::class)->handle($member);
        $watchlist->items()->createMany(
            $titles->take(3)->values()->map(fn (Title $title, int $index): array => [
                'title_id' => $title->id,
                'notes' => 'Queued from the featured carousel.',
                'position' => $index + 1,
            ])->all(),
        );

        UserList::factory()->for($member)->public()->create([
            'name' => 'Weekend Marathon',
            'slug' => 'weekend-marathon',
            'description' => 'A shortlist of high-energy picks.',
            'visibility' => ListVisibility::Public,
        ])->items()->createMany(
            $titles->slice(3, 2)->values()->map(fn (Title $title, int $index): array => [
                'title_id' => $title->id,
                'notes' => 'Best watched after midnight.',
                'position' => $index + 1,
            ])->all(),
        );

        foreach ($titles as $title) {
            foreach ($critics as $critic) {
                Rating::factory()->for($critic)->for($title)->create();
            }
        }

        $publishedReviews = collect([
            Review::factory()->published()->for($member, 'author')->for($titles[0])->create([
                'headline' => 'Elegant and sharp.',
                'body' => 'A confident sci-fi mystery with real momentum.',
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
                'published_at' => now(),
            ]),
            Review::factory()->published()->for($critics[0], 'author')->for($titles[1])->create([
                'headline' => 'Dense, but rewarding.',
                'body' => 'The world-building lands because the character work is precise.',
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
                'published_at' => now(),
            ]),
            Review::factory()->published()->for($critics[1], 'author')->for($titles[3])->create([
                'headline' => 'Great ensemble television.',
                'body' => 'The cast chemistry keeps the long-form story lean.',
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
                'published_at' => now(),
            ]),
        ]);

        ReviewVote::factory()->for($publishedReviews[0])->for($moderator)->helpful()->create();

        $report = Report::factory()->for($moderator, 'reporter')->for($publishedReviews[0], 'reportable')->create([
            'status' => ReportStatus::Resolved,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        ModerationAction::factory()->create([
            'moderator_id' => $admin->id,
            'report_id' => $report->id,
            'actionable_type' => Review::class,
            'actionable_id' => $publishedReviews[0]->id,
            'action' => 'reviewed',
            'notes' => 'Verified spoiler tagging and left review published.',
        ]);

        $titles->each(fn (Title $title) => app(RefreshTitleStatisticsAction::class)->handle($title));
    }
}
