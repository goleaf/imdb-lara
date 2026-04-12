<?php

namespace Tests\Feature\Feature\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class ImdbSourceDatabaseMigrationCommandTest extends TestCase
{
    use RefreshDatabase;
    use UsesCatalogOnlyApplication;

    public function test_command_resumes_table_copy_from_saved_cursor_state(): void
    {
        Schema::create('legacy_sync_records', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
        });

        $sourceDatabasePath = storage_path('framework/testing/source-imdb-migration.sqlite');
        $credentialsFilePath = storage_path('framework/testing/source-imdb-migration-credentials.txt');

        File::ensureDirectoryExists(dirname($sourceDatabasePath));
        File::delete($sourceDatabasePath);
        File::delete($credentialsFilePath);
        File::put($sourceDatabasePath, '');

        Config::set('database.connections.source_migration_seed', [
            'driver' => 'sqlite',
            'database' => $sourceDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('source_migration_seed');

        Schema::connection('source_migration_seed')->create('legacy_sync_records', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
        });

        DB::connection('source_migration_seed')->table('legacy_sync_records')->insert([
            ['id' => 1, 'name' => 'Action'],
            ['id' => 2, 'name' => 'Drama'],
            ['id' => 3, 'name' => 'Comedy'],
        ]);

        File::put($credentialsFilePath, implode(PHP_EOL, [
            'Hostname:',
            'local',
            '',
            'Database:',
            $sourceDatabasePath,
            '',
            'Username:',
            'unused',
            '',
            'Password:',
            'unused',
            '',
        ]));

        DB::table('legacy_sync_records')->insert([
            'id' => 1,
            'name' => 'Action',
        ]);

        DB::table('database_migration_states')->insert([
            'source_driver' => 'sqlite',
            'source_host' => 'local',
            'source_database' => $sourceDatabasePath,
            'table_name' => 'legacy_sync_records',
            'cursor_columns' => json_encode(['id'], JSON_THROW_ON_ERROR),
            'last_cursor' => json_encode(['id' => 1], JSON_THROW_ON_ERROR),
            'rows_copied' => 1,
            'status' => 'running',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
            'started_at' => now()->subMinute(),
        ]);

        $this->artisan('imdb:migrate-source-database', [
            '--credentials-file' => $credentialsFilePath,
            '--source-driver' => 'sqlite',
            '--table' => ['legacy_sync_records'],
            '--batch-size' => 1,
        ])->assertSuccessful();

        $this->assertSame(
            [
                ['id' => 1, 'name' => 'Action'],
                ['id' => 2, 'name' => 'Drama'],
                ['id' => 3, 'name' => 'Comedy'],
            ],
            DB::table('legacy_sync_records')
                ->select(['id', 'name'])
                ->orderBy('id')
                ->get()
                ->map(fn (object $row): array => ['id' => (int) $row->id, 'name' => (string) $row->name])
                ->all(),
        );

        $this->assertDatabaseHas('database_migration_states', [
            'source_driver' => 'sqlite',
            'source_host' => 'local',
            'source_database' => $sourceDatabasePath,
            'table_name' => 'legacy_sync_records',
            'rows_copied' => 3,
            'status' => 'completed',
        ]);
    }
}
