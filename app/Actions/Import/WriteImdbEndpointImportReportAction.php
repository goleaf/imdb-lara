<?php

namespace App\Actions\Import;

use Illuminate\Support\Facades\File;

class WriteImdbEndpointImportReportAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $artifactDirectory, string $endpoint, array $payload): string
    {
        $directory = rtrim($artifactDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'imports';
        $path = $directory.DIRECTORY_SEPARATOR.$endpoint.'.json';

        File::ensureDirectoryExists($directory);
        File::put($path, json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        return $path;
    }
}
