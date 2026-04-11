<?php

namespace Tests\Feature\Feature\Admin;

use App\Livewire\Pages\Admin\AkaAttributeCreatePage;
use App\Livewire\Pages\Admin\AkaAttributeEditPage;
use App\Models\AkaAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\BootstrapsImdbMysqlSqlite;
use Tests\TestCase;

class AdminAkaAttributeCrudTest extends TestCase
{
    use BootstrapsImdbMysqlSqlite;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpImdbMysqlSqliteDatabase();
    }

    public function test_editor_can_create_update_and_delete_aka_attributes_from_livewire_pages(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        $createPage = Livewire::test(AkaAttributeCreatePage::class)
            ->set('name', 'Festival title')
            ->call('saveAkaAttribute');

        $akaAttribute = AkaAttribute::query()
            ->where('name', 'Festival title')
            ->firstOrFail();

        $createPage->assertRedirect(route('admin.aka-attributes.edit', $akaAttribute));

        $this->get(route('admin.aka-attributes.index'))
            ->assertOk()
            ->assertSee('Festival title')
            ->assertSee('Edit');

        DB::connection('imdb_mysql')->table('movie_aka_attributes')->insert([
            'movie_aka_id' => 1,
            'aka_attribute_id' => $akaAttribute->getKey(),
            'position' => 1,
        ]);

        $editPage = Livewire::test(AkaAttributeEditPage::class, ['akaAttribute' => $akaAttribute])
            ->set('name', 'Festival cut')
            ->call('saveAkaAttribute');

        $akaAttribute->refresh();

        $editPage->assertRedirect(route('admin.aka-attributes.edit', $akaAttribute));
        $this->assertSame('Festival cut', $akaAttribute->name);

        Livewire::test(AkaAttributeEditPage::class, ['akaAttribute' => $akaAttribute])
            ->call('deleteAkaAttribute')
            ->assertRedirect(route('admin.aka-attributes.index'));

        $this->assertDatabaseMissing('aka_attributes', ['id' => $akaAttribute->getKey()], 'imdb_mysql');
        $this->assertDatabaseMissing('movie_aka_attributes', ['aka_attribute_id' => $akaAttribute->getKey()], 'imdb_mysql');
    }

    public function test_editor_cannot_create_duplicate_aka_attribute_names(): void
    {
        $editor = User::factory()->editor()->create();

        AkaAttribute::query()->create([
            'name' => 'Festival title',
        ]);

        $this->actingAs($editor);

        Livewire::test(AkaAttributeCreatePage::class)
            ->set('name', 'Festival title')
            ->call('saveAkaAttribute')
            ->assertHasErrors(['name']);
    }
}
