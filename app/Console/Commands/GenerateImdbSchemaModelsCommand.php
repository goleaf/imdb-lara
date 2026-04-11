<?php

namespace App\Console\Commands;

use App\Actions\Database\GenerateSchemaModelsAction;
use Illuminate\Console\Command;
use Throwable;

class GenerateImdbSchemaModelsCommand extends Command
{
    protected $signature = 'imdb:generate-schema-models
        {--connection=imdb_mysql : Database connection to inspect}
        {--namespace=App\\Models : Namespace for the generated models}
        {--output-path=app/Models : Relative or absolute path for generated model files}
        {--table=* : Restrict generation to one or more specific tables}
        {--force : Overwrite existing generated files}';

    protected $description = 'Generate Eloquent models from an existing database schema.';

    public function __construct(
        private readonly GenerateSchemaModelsAction $generateSchemaModelsAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $result = $this->generateSchemaModelsAction->handle(
                connection: (string) $this->option('connection'),
                outputPath: (string) $this->option('output-path'),
                namespace: (string) $this->option('namespace'),
                tables: $this->option('table'),
                force: (bool) $this->option('force'),
            );

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Generated tables', (string) count($result['generated'])],
                    ['Skipped tables', (string) count($result['skipped'])],
                    ['Output path', $result['output_path']],
                ],
            );

            if ($result['generated'] !== []) {
                $this->line('Generated: '.implode(', ', $result['generated']));
            }

            if ($result['skipped'] !== []) {
                $this->warn('Skipped existing files: '.implode(', ', $result['skipped']));
            }

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            report($throwable);
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }
    }
}
