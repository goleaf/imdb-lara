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

class ImdbSourceDatabaseMigrationCommandProgressTest extends TestCase
{
    use RefreshDatabase;
    use UsesCatalogOnlyApplication;

    public function test_command_outputs_process_metrics_and_estimated_table_counts(): void
    {
        Schema::create('legacy_sync_records', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
        });

        $sourceDatabasePath = storage_path('framework/testing/source-imdb-migration-progress.sqlite');
        $credentialsFilePath = storage_path('framework/testing/source-imdb-migration-progress-credentials.txt');

        File::ensureDirectoryExists(dirname($sourceDatabasePath));
        File::delete($sourceDatabasePath);
        File::delete($credentialsFilePath);
        File::put($sourceDatabasePath, '');

        Config::set('database.connections.source_migration_progress_seed', [
            'driver' => 'sqlite',
            'database' => $sourceDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('source_migration_progress_seed');

        Schema::connection('source_migration_progress_seed')->create('legacy_sync_records', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
        });

        DB::connection('source_migration_progress_seed')->table('legacy_sync_records')->insert([
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

        $this->artisan('imdb:migrate-source-database', [
            '--credentials-file' => $credentialsFilePath,
            '--source-driver' => 'sqlite',
            '--table' => ['legacy_sync_records'],
            '--batch-size' => 1,
        ])
            ->expectsOutputToContain('Starting IMDb source database migration...')
            ->expectsOutputToContain('Tables done / estimated')
            ->expectsOutputToContain('Rows copied / estimated')
            ->expectsOutputToContain('Completed table 1/1: legacy_sync_records')
            ->expectsOutputToContain('100.00%')
            ->expectsOutputToContain('legacy_sync_records')
            ->assertSuccessful();
    }
}
