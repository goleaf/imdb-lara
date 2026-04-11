<?php

namespace Tests\Feature\Feature\Admin;

use App\Livewire\Pages\Admin\AwardCategoryCreatePage;
use App\Livewire\Pages\Admin\AwardCategoryEditPage;
use App\Models\AwardCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class AdminAwardCategoryCrudTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use RefreshDatabase;
    use UsesCatalogOnlyApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_editor_can_create_update_and_delete_award_categories_from_livewire_pages(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        $createPage = Livewire::test(AwardCategoryCreatePage::class)
            ->set('name', 'Best Picture')
            ->call('saveAwardCategory');

        $awardCategory = AwardCategory::query()
            ->where('name', 'Best Picture')
            ->firstOrFail();

        $createPage->assertRedirect(route('admin.award-categories.edit', $awardCategory));

        $this->get(route('admin.award-categories.index'))
            ->assertOk()
            ->assertSee('Best Picture')
            ->assertSee('Edit');

        DB::connection('imdb_mysql')->table('movie_award_nominations')->insert([
            'movie_id' => 1,
            'event_imdb_id' => null,
            'award_category_id' => $awardCategory->getKey(),
            'award_year' => 2024,
            'text' => 'Imported nomination',
            'is_winner' => 0,
            'winner_rank' => null,
            'position' => 1,
        ]);

        $editPage = Livewire::test(AwardCategoryEditPage::class, ['awardCategory' => $awardCategory])
            ->set('name', 'Best Director')
            ->call('saveAwardCategory');

        $awardCategory->refresh();

        $editPage->assertRedirect(route('admin.award-categories.edit', $awardCategory));
        $this->assertSame('Best Director', $awardCategory->name);

        Livewire::test(AwardCategoryEditPage::class, ['awardCategory' => $awardCategory])
            ->call('deleteAwardCategory')
            ->assertRedirect(route('admin.award-categories.index'));

        $this->assertDatabaseMissing('award_categories', ['id' => $awardCategory->getKey()], 'imdb_mysql');
        $this->assertDatabaseHas('movie_award_nominations', [
            'movie_id' => 1,
            'award_year' => 2024,
            'award_category_id' => null,
        ], 'imdb_mysql');
    }

    public function test_editor_cannot_create_duplicate_award_category_names(): void
    {
        $editor = User::factory()->editor()->create();

        AwardCategory::query()->create([
            'name' => 'Best Picture',
        ]);

        $this->actingAs($editor);

        Livewire::test(AwardCategoryCreatePage::class)
            ->set('name', 'Best Picture')
            ->call('saveAwardCategory')
            ->assertHasErrors(['name']);
    }
}
