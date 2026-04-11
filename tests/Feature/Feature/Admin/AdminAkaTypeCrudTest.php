<?php

namespace Tests\Feature\Feature\Admin;

use App\Livewire\Pages\Admin\AkaTypeCreatePage;
use App\Livewire\Pages\Admin\AkaTypeEditPage;
use App\Models\AkaType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\TestCase;

class AdminAkaTypeCrudTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_editor_can_create_update_and_delete_aka_types_from_livewire_pages(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        $createPage = Livewire::test(AkaTypeCreatePage::class)
            ->set('name', 'imdbDisplay')
            ->call('saveAkaType');

        $akaType = AkaType::query()
            ->where('name', 'imdbDisplay')
            ->firstOrFail();

        $createPage->assertRedirect(route('admin.aka-types.edit', $akaType));

        $this->get(route('admin.aka-types.index'))
            ->assertOk()
            ->assertSee('Imdb Display')
            ->assertSee('Edit');

        DB::connection('imdb_mysql')->table('movie_aka_types')->insert([
            'movie_aka_id' => 1,
            'aka_type_id' => $akaType->getKey(),
            'position' => 1,
        ]);

        $editPage = Livewire::test(AkaTypeEditPage::class, ['akaType' => $akaType])
            ->set('name', 'original')
            ->call('saveAkaType');

        $akaType->refresh();

        $editPage->assertRedirect(route('admin.aka-types.edit', $akaType));
        $this->assertSame('original', $akaType->name);

        Livewire::test(AkaTypeEditPage::class, ['akaType' => $akaType])
            ->call('deleteAkaType')
            ->assertRedirect(route('admin.aka-types.index'));

        $this->assertDatabaseMissing('aka_types', ['id' => $akaType->getKey()], 'imdb_mysql');
        $this->assertDatabaseMissing('movie_aka_types', ['aka_type_id' => $akaType->getKey()], 'imdb_mysql');
    }

    public function test_editor_cannot_create_duplicate_aka_type_names(): void
    {
        $editor = User::factory()->editor()->create();

        AkaType::query()->create([
            'name' => 'imdbDisplay',
        ]);

        $this->actingAs($editor);

        Livewire::test(AkaTypeCreatePage::class)
            ->set('name', 'imdbDisplay')
            ->call('saveAkaType')
            ->assertHasErrors(['name']);
    }
}
