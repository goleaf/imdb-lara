<?php

namespace App\Actions\Import;

use RuntimeException;

class EnsureLegacyImportPipelineIsEnabledAction
{
    public static function disabledMessage(): string
    {
        return 'Legacy IMDb import pipeline is disabled because this application now runs directly against the existing MySQL catalog.';
    }

    public function handle(): void
    {
        if ((bool) config('screenbase.legacy_import_pipeline_enabled', false)) {
            return;
        }

        throw new RuntimeException(self::disabledMessage());
    }
}
