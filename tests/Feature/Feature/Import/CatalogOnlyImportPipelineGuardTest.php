<?php

namespace Tests\Feature\Feature\Import;

use App\Actions\Import\EnsureLegacyImportPipelineIsEnabledAction;
use App\Actions\Import\ImportImdbNamePayloadAction;
use App\Actions\Import\ImportImdbTitlePayloadAction;
use RuntimeException;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class CatalogOnlyImportPipelineGuardTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_title_import_action_throws_when_legacy_pipeline_is_disabled(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(EnsureLegacyImportPipelineIsEnabledAction::disabledMessage());

        app(ImportImdbTitlePayloadAction::class)->handle(
            ['id' => 'tt0000001'],
            storage_path('framework/testing/catalog-only-title.json'),
        );
    }

    public function test_name_import_action_throws_when_legacy_pipeline_is_disabled(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(EnsureLegacyImportPipelineIsEnabledAction::disabledMessage());

        app(ImportImdbNamePayloadAction::class)->handle([
            'id' => 'nm0000001',
        ], storage_path('framework/testing/catalog-only-name.json'));
    }
}
