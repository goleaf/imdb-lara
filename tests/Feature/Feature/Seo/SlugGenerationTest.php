<?php

namespace Tests\Feature\Feature\Seo;

use App\Models\Person;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_titles_and_people_normalize_provided_slugs_before_persisting(): void
    {
        $title = Title::factory()->create([
            'name' => 'Northern Signal',
            'slug' => ' Northern Signal 2026 ',
        ]);

        $person = Person::factory()->create([
            'name' => 'Ava Mercer',
            'slug' => ' Ava Mercer ',
        ]);

        $this->assertSame('northern-signal-2026', $title->fresh()->slug);
        $this->assertSame('ava-mercer', $person->fresh()->slug);
    }

    public function test_list_slugs_stay_clean_and_unique_within_each_profile_scope(): void
    {
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();

        $firstList = UserList::factory()->for($firstOwner)->create([
            'name' => 'Weekend Marathon',
            'slug' => ' Weekend Marathon ',
        ]);
        $secondList = UserList::query()->create([
            'user_id' => $firstOwner->id,
            'name' => 'Weekend Marathon',
            'visibility' => 'public',
            'is_watchlist' => false,
        ]);
        $thirdList = UserList::query()->create([
            'user_id' => $secondOwner->id,
            'name' => 'Weekend Marathon',
            'visibility' => 'public',
            'is_watchlist' => false,
        ]);

        $this->assertSame('weekend-marathon', $firstList->fresh()->slug);
        $this->assertSame('weekend-marathon-2', $secondList->fresh()->slug);
        $this->assertSame('weekend-marathon', $thirdList->fresh()->slug);
    }
}
