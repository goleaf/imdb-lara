<?php

namespace Database\Seeders;

use App\Actions\Lists\EnsureWatchlistAction;
use App\Actions\Titles\RefreshTitleStatisticsAction;
use App\Enums\ContributionAction;
use App\Enums\ContributionStatus;
use App\Enums\ListVisibility;
use App\Enums\MediaKind;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Enums\TitleRelationshipType;
use App\Enums\TitleType;
use App\Enums\WatchState;
use App\Models\Award;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Company;
use App\Models\Contribution;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\ModerationAction;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Rating;
use App\Models\Report;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleRelationship;
use App\Models\TitleTranslation;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Platform Owner',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Admin Curator',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $editor = User::factory()->editor()->create([
            'name' => 'Catalog Editor',
            'username' => 'editor',
            'email' => 'editor@example.com',
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

        $contributors = User::factory()->count(2)->contributor()->create()
            ->prepend(User::factory()->contributor()->create([
                'name' => 'Lead Contributor',
                'username' => 'contributor',
                'email' => 'contributor@example.com',
            ]))
            ->values();

        $genres = collect([
            Genre::factory()->create(['name' => 'Sci-Fi', 'slug' => 'sci-fi']),
            Genre::factory()->create(['name' => 'Drama', 'slug' => 'drama']),
            Genre::factory()->create(['name' => 'Mystery', 'slug' => 'mystery']),
            Genre::factory()->create(['name' => 'Documentary', 'slug' => 'documentary']),
            Genre::factory()->create(['name' => 'Thriller', 'slug' => 'thriller']),
            Genre::factory()->create(['name' => 'Adventure', 'slug' => 'adventure']),
        ]);

        $companies = collect([
            Company::factory()->create(['name' => 'Signal Harbor Studios', 'slug' => 'signal-harbor-studios']),
            Company::factory()->create(['name' => 'Northlight Pictures', 'slug' => 'northlight-pictures']),
            Company::factory()->create(['name' => 'Atlas Documentary Unit', 'slug' => 'atlas-documentary-unit']),
            Company::factory()->create(['name' => 'Bloomline Television', 'slug' => 'bloomline-television']),
        ]);

        $people = collect([
            Person::factory()->create([
                'name' => 'Ava Mercer',
                'slug' => 'ava-mercer',
                'known_for_department' => 'Acting',
            ]),
            Person::factory()->create([
                'name' => 'Jonah Vale',
                'slug' => 'jonah-vale',
                'known_for_department' => 'Acting',
            ]),
            Person::factory()->create([
                'name' => 'Talia Rowe',
                'slug' => 'talia-rowe',
                'known_for_department' => 'Directing',
            ]),
            Person::factory()->create([
                'name' => 'Micah Stone',
                'slug' => 'micah-stone',
                'known_for_department' => 'Writing',
            ]),
            Person::factory()->create([
                'name' => 'Noor Haddad',
                'slug' => 'noor-haddad',
                'known_for_department' => 'Production',
            ]),
            Person::factory()->create([
                'name' => 'Elsie Tran',
                'slug' => 'elsie-tran',
                'known_for_department' => 'Acting',
            ]),
            Person::factory()->create([
                'name' => 'Rafi Quinn',
                'slug' => 'rafi-quinn',
                'known_for_department' => 'Acting',
            ]),
            Person::factory()->create([
                'name' => 'Mina Sato',
                'slug' => 'mina-sato',
                'known_for_department' => 'Directing',
            ]),
        ]);

        $professions = collect([
            'ava_actor' => PersonProfession::factory()->for($people[0], 'person')->primary()->create([
                'department' => 'Cast',
                'profession' => 'Actor',
            ]),
            'jonah_actor' => PersonProfession::factory()->for($people[1], 'person')->primary()->create([
                'department' => 'Cast',
                'profession' => 'Actor',
            ]),
            'talia_director' => PersonProfession::factory()->for($people[2], 'person')->primary()->create([
                'department' => 'Directing',
                'profession' => 'Director',
            ]),
            'micah_writer' => PersonProfession::factory()->for($people[3], 'person')->primary()->create([
                'department' => 'Writing',
                'profession' => 'Writer',
            ]),
            'noor_producer' => PersonProfession::factory()->for($people[4], 'person')->primary()->create([
                'department' => 'Production',
                'profession' => 'Producer',
            ]),
            'elsie_actor' => PersonProfession::factory()->for($people[5], 'person')->primary()->create([
                'department' => 'Cast',
                'profession' => 'Actor',
            ]),
            'rafi_actor' => PersonProfession::factory()->for($people[6], 'person')->primary()->create([
                'department' => 'Cast',
                'profession' => 'Actor',
            ]),
            'mina_director' => PersonProfession::factory()->for($people[7], 'person')->primary()->create([
                'department' => 'Directing',
                'profession' => 'Director',
            ]),
        ]);

        $titles = collect([
            'northern_signal' => Title::factory()->movie()->create([
                'name' => 'Northern Signal',
                'slug' => 'northern-signal',
                'sort_title' => 'Northern Signal',
                'title_type' => TitleType::Movie,
                'popularity_rank' => 1,
                'release_year' => 2024,
                'plot_outline' => 'A stranded research crew intercepts a transmission from beneath Arctic ice.',
                'synopsis' => 'When a glaciology mission goes dark, a linguist and a systems engineer uncover a decades-old station and a signal nobody can explain.',
                'tagline' => 'The ice was listening first.',
            ]),
            'afterlight_protocol' => Title::factory()->movie()->create([
                'name' => 'Afterlight Protocol',
                'slug' => 'afterlight-protocol',
                'sort_title' => 'Afterlight Protocol',
                'title_type' => TitleType::Movie,
                'popularity_rank' => 2,
                'release_year' => 2023,
                'plot_outline' => 'A covert rescue mission spirals into a conspiracy across low-earth orbit.',
            ]),
            'glass_atlas' => Title::factory()->movie()->create([
                'name' => 'The Glass Atlas',
                'slug' => 'the-glass-atlas',
                'sort_title' => 'Glass Atlas, The',
                'title_type' => TitleType::Movie,
                'popularity_rank' => 3,
                'release_year' => 2022,
                'plot_outline' => 'Cartographers race to rebuild a world archive after a climate data breach.',
            ]),
            'harbor_nine' => Title::factory()->miniSeries()->create([
                'name' => 'Harbor Nine',
                'slug' => 'harbor-nine',
                'sort_title' => 'Harbor Nine',
                'title_type' => TitleType::MiniSeries,
                'popularity_rank' => 4,
                'release_year' => 2025,
                'plot_outline' => 'A missing submarine case forces an exhausted port city back into the spotlight.',
            ]),
            'static_bloom' => Title::factory()->series()->create([
                'name' => 'Static Bloom',
                'slug' => 'static-bloom',
                'sort_title' => 'Static Bloom',
                'title_type' => TitleType::Series,
                'popularity_rank' => 5,
                'release_year' => 2021,
                'plot_outline' => 'A near-future telecom crew keeps a failing city connected one district at a time.',
            ]),
            'worlds_beneath_ice' => Title::factory()->documentary()->create([
                'name' => 'Worlds Beneath Ice',
                'slug' => 'worlds-beneath-ice',
                'sort_title' => 'Worlds Beneath Ice',
                'title_type' => TitleType::Documentary,
                'popularity_rank' => 6,
                'release_year' => 2024,
                'plot_outline' => 'Marine biologists map thriving ecosystems sealed beneath Antarctic shelves.',
            ]),
            'signal_to_summit' => Title::factory()->short()->create([
                'name' => 'Signal to Summit',
                'slug' => 'signal-to-summit',
                'sort_title' => 'Signal to Summit',
                'title_type' => TitleType::Short,
                'popularity_rank' => 7,
                'release_year' => 2020,
                'plot_outline' => 'A mountain radio operator relays a final call through a storm.',
            ]),
            'midwinter_broadcast' => Title::factory()->special()->create([
                'name' => 'Midwinter Broadcast',
                'slug' => 'midwinter-broadcast',
                'sort_title' => 'Midwinter Broadcast',
                'title_type' => TitleType::Special,
                'popularity_rank' => 8,
                'release_year' => 2023,
                'plot_outline' => 'A live charity special reunites a legendary ensemble for one uneasy night.',
            ]),
            'aurora_run' => Title::factory()->movie()->create([
                'name' => 'Aurora Run',
                'slug' => 'aurora-run',
                'sort_title' => 'Aurora Run',
                'title_type' => TitleType::Movie,
                'popularity_rank' => 9,
                'release_year' => 2026,
                'release_date' => now()->addDays(45)->toDateString(),
                'plot_outline' => 'A test pilot chases a vanished spacecraft through the aurora corridor.',
                'synopsis' => 'An atmospheric rescue thriller set above the Arctic circle, where a prototype launch disappears after re-entry and the only pilot willing to chase it has unfinished history with the crew on board.',
                'tagline' => 'The rescue window closes at dawn.',
            ]),
        ]);

        $titles['northern_signal']->genres()->attach([
            $genres[0]->id,
            $genres[2]->id,
            $genres[4]->id,
        ]);
        $titles['afterlight_protocol']->genres()->attach([$genres[0]->id, $genres[4]->id]);
        $titles['glass_atlas']->genres()->attach([$genres[1]->id, $genres[2]->id]);
        $titles['harbor_nine']->genres()->attach([$genres[1]->id, $genres[2]->id, $genres[4]->id]);
        $titles['static_bloom']->genres()->attach([$genres[0]->id, $genres[1]->id]);
        $titles['worlds_beneath_ice']->genres()->attach([$genres[3]->id, $genres[5]->id]);
        $titles['signal_to_summit']->genres()->attach([$genres[1]->id, $genres[5]->id]);
        $titles['midwinter_broadcast']->genres()->attach([$genres[1]->id]);
        $titles['aurora_run']->genres()->attach([$genres[0]->id, $genres[5]->id, $genres[4]->id]);

        $titles['northern_signal']->companies()->attach($companies[0], [
            'relationship' => 'production',
            'credited_as' => 'Signal Harbor Studios',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        $titles['afterlight_protocol']->companies()->attach($companies[1], [
            'relationship' => 'production',
            'credited_as' => 'Northlight Pictures',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        $titles['glass_atlas']->companies()->attach($companies[1], [
            'relationship' => 'production',
            'credited_as' => 'Northlight Pictures',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        $titles['harbor_nine']->companies()->attach($companies[0], [
            'relationship' => 'production',
            'credited_as' => 'Signal Harbor Studios',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        $titles['static_bloom']->companies()->attach($companies[3], [
            'relationship' => 'production',
            'credited_as' => 'Bloomline Television',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        $titles['worlds_beneath_ice']->companies()->attach($companies[2], [
            'relationship' => 'production',
            'credited_as' => 'Atlas Documentary Unit',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        $titles['aurora_run']->companies()->attach($companies[1], [
            'relationship' => 'production',
            'credited_as' => 'Northlight Pictures',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        TitleTranslation::factory()->for($titles['northern_signal'])->create([
            'locale' => 'lt',
            'localized_title' => 'Šiaurinis Signalas',
            'localized_slug' => 'siaurinis-signalas',
            'localized_plot_outline' => 'Arkties ledynuose įstrigusi komanda pagauna nepaaiškinamą signalą.',
        ]);
        TitleTranslation::factory()->for($titles['static_bloom'])->create([
            'locale' => 'fr',
            'localized_title' => 'Floraison Statique',
            'localized_slug' => 'floraison-statique',
        ]);

        $titles->values()->each(function (Title $title, int $index) use ($people): void {
            $primaryCast = $people->slice($index % 3, 2);

            foreach ($primaryCast as $castIndex => $person) {
                $title->credits()->create([
                    'person_id' => $person->id,
                    'department' => 'Cast',
                    'job' => 'Actor',
                    'character_name' => fake()->firstName().' '.fake()->lastName(),
                    'billing_order' => $castIndex + 1,
                    'is_principal' => true,
                ]);
            }
        });

        $titles['northern_signal']->credits()->createMany([
            [
                'person_id' => $people[0]->id,
                'department' => 'Cast',
                'job' => 'Actor',
                'character_name' => 'Dr. Mara Elling',
                'billing_order' => 1,
                'is_principal' => true,
                'person_profession_id' => $professions['ava_actor']->id,
            ],
            [
                'person_id' => $people[1]->id,
                'department' => 'Cast',
                'job' => 'Actor',
                'character_name' => 'Elias Vonn',
                'billing_order' => 2,
                'is_principal' => true,
                'person_profession_id' => $professions['jonah_actor']->id,
            ],
            [
                'person_id' => $people[2]->id,
                'department' => 'Directing',
                'job' => 'Director',
                'billing_order' => 3,
                'is_principal' => true,
                'person_profession_id' => $professions['talia_director']->id,
            ],
        ]);

        $titles['static_bloom']->credits()->createMany([
            [
                'person_id' => $people[5]->id,
                'department' => 'Cast',
                'job' => 'Actor',
                'character_name' => 'Tess Mora',
                'billing_order' => 1,
                'is_principal' => true,
                'person_profession_id' => $professions['elsie_actor']->id,
            ],
            [
                'person_id' => $people[6]->id,
                'department' => 'Cast',
                'job' => 'Actor',
                'character_name' => 'Kellan Rey',
                'billing_order' => 2,
                'is_principal' => true,
                'person_profession_id' => $professions['rafi_actor']->id,
            ],
            [
                'person_id' => $people[7]->id,
                'department' => 'Directing',
                'job' => 'Director',
                'billing_order' => 3,
                'is_principal' => true,
                'person_profession_id' => $professions['mina_director']->id,
            ],
        ]);

        $staticBloomSeasonOne = Season::factory()->for($titles['static_bloom'], 'series')->create([
            'name' => 'Season 1',
            'slug' => 'static-bloom-season-1',
            'season_number' => 1,
            'release_year' => 2021,
            'summary' => 'The first winter blackout puts the exchange under impossible strain.',
        ]);

        $staticBloomSeasonTwo = Season::factory()->for($titles['static_bloom'], 'series')->create([
            'name' => 'Season 2',
            'slug' => 'static-bloom-season-2',
            'season_number' => 2,
            'release_year' => 2022,
            'summary' => 'The crew rebuilds the network as rival cities begin poaching key infrastructure.',
        ]);

        $harborNineSeasonOne = Season::factory()->for($titles['harbor_nine'], 'series')->create([
            'name' => 'Season 1',
            'slug' => 'harbor-nine-season-1',
            'season_number' => 1,
            'release_year' => 2025,
            'summary' => 'A port-city disappearance exposes military secrets and civilian corruption.',
        ]);

        $episodeTitles = collect([
            'pilot' => Title::factory()->episode()->create([
                'name' => 'Static Bloom: Pilot',
                'slug' => 'static-bloom-pilot',
                'sort_title' => 'Static Bloom Pilot',
                'title_type' => TitleType::Episode,
                'popularity_rank' => 901,
                'release_year' => 2021,
            ]),
            'switchback' => Title::factory()->episode()->create([
                'name' => 'Static Bloom: Switchback',
                'slug' => 'static-bloom-switchback',
                'sort_title' => 'Static Bloom Switchback',
                'title_type' => TitleType::Episode,
                'popularity_rank' => 902,
                'release_year' => 2021,
            ]),
            'signal_path' => Title::factory()->episode()->create([
                'name' => 'Static Bloom: Signal Path',
                'slug' => 'static-bloom-signal-path',
                'sort_title' => 'Static Bloom Signal Path',
                'title_type' => TitleType::Episode,
                'popularity_rank' => 903,
                'release_year' => 2022,
            ]),
            'deep_end' => Title::factory()->episode()->create([
                'name' => 'Harbor Nine: The Deep End',
                'slug' => 'harbor-nine-the-deep-end',
                'sort_title' => 'Harbor Nine The Deep End',
                'title_type' => TitleType::Episode,
                'popularity_rank' => 904,
                'release_year' => 2025,
            ]),
        ]);

        $episodes = collect([
            'pilot' => Episode::factory()
                ->for($episodeTitles['pilot'], 'title')
                ->for($titles['static_bloom'], 'series')
                ->for($staticBloomSeasonOne, 'season')
                ->create([
                    'season_number' => 1,
                    'episode_number' => 1,
                    'absolute_number' => 1,
                    'production_code' => 'SB101',
                    'aired_at' => now()->subYears(4)->toDateString(),
                ]),
            'switchback' => Episode::factory()
                ->for($episodeTitles['switchback'], 'title')
                ->for($titles['static_bloom'], 'series')
                ->for($staticBloomSeasonOne, 'season')
                ->create([
                    'season_number' => 1,
                    'episode_number' => 2,
                    'absolute_number' => 2,
                    'production_code' => 'SB102',
                    'aired_at' => now()->subYears(4)->addWeek()->toDateString(),
                ]),
            'signal_path' => Episode::factory()
                ->for($episodeTitles['signal_path'], 'title')
                ->for($titles['static_bloom'], 'series')
                ->for($staticBloomSeasonTwo, 'season')
                ->create([
                    'season_number' => 2,
                    'episode_number' => 1,
                    'absolute_number' => 3,
                    'production_code' => 'SB201',
                    'aired_at' => now()->subYears(3)->toDateString(),
                ]),
            'deep_end' => Episode::factory()
                ->for($episodeTitles['deep_end'], 'title')
                ->for($titles['harbor_nine'], 'series')
                ->for($harborNineSeasonOne, 'season')
                ->create([
                    'season_number' => 1,
                    'episode_number' => 1,
                    'absolute_number' => 1,
                    'production_code' => 'HN101',
                    'aired_at' => now()->subMonths(6)->toDateString(),
                ]),
        ]);

        $titles['static_bloom']->credits()->create([
            'person_id' => $people[3]->id,
            'department' => 'Writing',
            'job' => 'Writer',
            'billing_order' => 4,
            'is_principal' => false,
            'person_profession_id' => $professions['micah_writer']->id,
            'episode_id' => $episodes['switchback']->id,
            'credited_as' => 'Episode Writer',
        ]);

        $episodeTitles['pilot']->credits()->create([
            'person_id' => $people[7]->id,
            'department' => 'Directing',
            'job' => 'Director',
            'billing_order' => 1,
            'is_principal' => true,
            'person_profession_id' => $professions['mina_director']->id,
        ]);

        foreach ($titles as $title) {
            MediaAsset::factory()->for($title, 'mediable')->poster()->create();
            MediaAsset::factory()->for($title, 'mediable')->backdrop()->create();
        }

        MediaAsset::factory()->for($titles['northern_signal'], 'mediable')->trailer()->create([
            'provider' => 'youtube',
            'provider_key' => 'northern-signal-trailer',
            'kind' => MediaKind::Trailer,
        ]);
        MediaAsset::factory()->for($titles['static_bloom'], 'mediable')->trailer()->create([
            'provider' => 'youtube',
            'provider_key' => 'static-bloom-trailer',
            'kind' => MediaKind::Trailer,
        ]);

        $people->each(fn (Person $person) => MediaAsset::factory()->for($person, 'mediable')->headshot()->create());

        $watchlist = app(EnsureWatchlistAction::class)->handle($member);
        $watchlist->items()->createMany([
            [
                'title_id' => $titles['northern_signal']->id,
                'notes' => 'Queued from the featured carousel.',
                'position' => 1,
                'watch_state' => WatchState::Planned,
            ],
            [
                'title_id' => $titles['static_bloom']->id,
                'notes' => 'Binge before the finale special.',
                'position' => 2,
                'watch_state' => WatchState::Watching,
                'started_at' => now()->subDays(2),
            ],
            [
                'title_id' => $titles['worlds_beneath_ice']->id,
                'notes' => 'Research inspiration.',
                'position' => 3,
                'watch_state' => WatchState::Completed,
                'started_at' => now()->subWeek(),
                'watched_at' => now()->subDays(1),
                'rewatch_count' => 1,
            ],
        ]);

        UserList::factory()->for($member)->public()->create([
            'name' => 'Weekend Marathon',
            'slug' => 'weekend-marathon',
            'description' => 'A shortlist of high-energy picks.',
            'visibility' => ListVisibility::Public,
        ])->items()->createMany([
            [
                'title_id' => $titles['afterlight_protocol']->id,
                'notes' => 'The pacing makes this a perfect Friday opener.',
                'position' => 1,
            ],
            [
                'title_id' => $titles['glass_atlas']->id,
                'notes' => 'Pairs well with the documentary slate.',
                'position' => 2,
            ],
        ]);

        foreach ($titles as $title) {
            foreach ($contributors as $contributor) {
                Rating::factory()->for($contributor)->for($title)->create();
            }
        }

        $publishedReviews = collect([
            Review::factory()->published()->for($member, 'author')->for($titles['northern_signal'])->create([
                'headline' => 'Elegant and sharp.',
                'body' => 'A confident sci-fi mystery with real momentum.',
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
                'published_at' => now(),
            ]),
            Review::factory()->published()->for($contributors[0], 'author')->for($titles['afterlight_protocol'])->create([
                'headline' => 'Dense, but rewarding.',
                'body' => 'The world-building lands because the character work is precise.',
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
                'published_at' => now(),
            ]),
            Review::factory()->published()->for($contributors[1], 'author')->for($titles['static_bloom'])->create([
                'headline' => 'Great ensemble television.',
                'body' => 'The cast chemistry keeps the long-form story lean.',
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
                'published_at' => now(),
                'status' => ReviewStatus::Published,
            ]),
        ]);

        ReviewVote::factory()->for($publishedReviews[0])->for($moderator)->helpful()->create();

        $report = Report::factory()->for($moderator, 'reporter')->for($publishedReviews[0], 'reportable')->create([
            'status' => ReportStatus::Resolved,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'resolution_notes' => 'Verified spoiler tagging and left the review published.',
        ]);

        ModerationAction::factory()->create([
            'moderator_id' => $admin->id,
            'report_id' => $report->id,
            'actionable_type' => Review::class,
            'actionable_id' => $publishedReviews[0]->id,
            'action' => 'reviewed',
            'notes' => 'Verified spoiler tagging and left review published.',
        ]);

        TitleRelationship::factory()->create([
            'from_title_id' => $titles['northern_signal']->id,
            'to_title_id' => $titles['afterlight_protocol']->id,
            'relationship_type' => TitleRelationshipType::Similar,
            'weight' => 8,
        ]);

        TitleRelationship::factory()->create([
            'from_title_id' => $titles['static_bloom']->id,
            'to_title_id' => $titles['harbor_nine']->id,
            'relationship_type' => TitleRelationshipType::SharedUniverse,
            'weight' => 6,
        ]);

        $award = Award::factory()->create([
            'name' => 'Celestial Screen Awards',
            'slug' => 'celestial-screen-awards',
        ]);

        $awardEvent = AwardEvent::factory()->for($award)->create([
            'name' => '2025 Celestial Screen Awards',
            'slug' => '2025-celestial-screen-awards',
            'year' => 2025,
        ]);

        $bestPicture = AwardCategory::factory()->for($award)->create([
            'name' => 'Best Picture',
            'slug' => 'best-picture',
            'recipient_scope' => 'title',
        ]);

        $bestLeadPerformance = AwardCategory::factory()->for($award)->create([
            'name' => 'Best Lead Performance',
            'slug' => 'best-lead-performance',
            'recipient_scope' => 'person',
        ]);

        $bestEpisode = AwardCategory::factory()->for($award)->create([
            'name' => 'Best Episode',
            'slug' => 'best-episode',
            'recipient_scope' => 'episode',
        ]);

        AwardNomination::factory()->for($awardEvent)->for($bestPicture, 'awardCategory')->winner()->create([
            'title_id' => $titles['northern_signal']->id,
            'sort_order' => 1,
        ]);

        AwardNomination::factory()->for($awardEvent)->for($bestLeadPerformance, 'awardCategory')->winner()->forPerson()->create([
            'person_id' => $people[0]->id,
            'credited_name' => 'Ava Mercer',
            'sort_order' => 2,
        ]);

        AwardNomination::factory()->for($awardEvent)->for($bestEpisode, 'awardCategory')->forEpisode()->create([
            'title_id' => $titles['static_bloom']->id,
            'episode_id' => $episodes['pilot']->id,
            'credited_name' => $episodeTitles['pilot']->name,
            'sort_order' => 3,
        ]);

        Contribution::factory()->for($contributors[0])->for($titles['northern_signal'], 'contributable')->create([
            'action' => ContributionAction::Update,
            'status' => ContributionStatus::Approved,
            'payload' => [
                'field' => 'plot_outline',
                'value' => 'Updated copy for the Arctic transmission mystery.',
            ],
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        Contribution::factory()->for($contributors[1])->for($people[2], 'contributable')->create([
            'action' => ContributionAction::Curate,
            'status' => ContributionStatus::Submitted,
            'payload' => [
                'field' => 'biography',
                'value' => 'Expanded directing credits and festival background.',
            ],
        ]);

        $member->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'catalog.review-approved',
            'data' => [
                'title' => $titles['northern_signal']->name,
                'message' => 'Your review for Northern Signal is now public.',
            ],
        ]);

        $superAdmin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'staff.welcome',
            'data' => [
                'message' => 'Superadmin access provisioned for platform operations.',
            ],
        ]);

        $editor->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'staff.assignment',
            'data' => [
                'message' => 'Editorial access provisioned for title and people curation.',
            ],
        ]);

        Title::query()
            ->select(['id'])
            ->get()
            ->each(fn (Title $title) => app(RefreshTitleStatisticsAction::class)->handle($title));
    }
}
