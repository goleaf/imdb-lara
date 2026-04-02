<?php

namespace Tests;

use App\Actions\Import\DownloadImdbTitlePayloadAction;
use App\Actions\Import\FetchImdbJsonAction;
use App\Actions\Import\ImportImdbNamePayloadAction;
use App\Actions\Import\ImportImdbTitlePayloadAction;
use App\Actions\Import\WriteImdbNameVerificationReportAction;
use App\Actions\Import\WriteImdbTitleVerificationReportAction;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Sleep;
use ReflectionClass;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();

        $property = (new ReflectionClass(FetchImdbJsonAction::class))
            ->getProperty('lastRequestFinishedAtMicroseconds');

        $property->setValue(null, null);
    }

    /**
     * @return array{
     *     downloaded: bool,
     *     imdb_id: string,
     *     payload: array<string, mixed>,
     *     payload_hash: string,
     *     source_url: string,
     *     storage_path: string
     * }
     */
    protected function downloadImdbTitlePayload(string $imdbId, string $directory, bool $force = false): array
    {
        return app(DownloadImdbTitlePayloadAction::class)->handle(
            $imdbId,
            $directory,
            '/titles/{titleId}',
            $force,
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected function importImdbTitlePayloadFromPath(string $path, array $options = [], ?array $payload = null): Title
    {
        $payload ??= $this->decodeJsonFile($path);

        $title = app(ImportImdbTitlePayloadAction::class)->handle($payload, $path, $options);

        app(WriteImdbTitleVerificationReportAction::class)->handle(
            $title,
            $payload,
            $this->artifactDirectoryForPath($path),
        );

        return $title;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function importImdbNamePayload(string $path, array $payload, array $options = []): Person
    {
        $person = app(ImportImdbNamePayloadAction::class)->handle($payload, $path, $options);

        app(WriteImdbNameVerificationReportAction::class)->handle(
            $person,
            $payload,
            $this->artifactDirectoryForPath($path),
        );

        return $person;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonFile(string $path): array
    {
        $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException(sprintf('Expected JSON object payload in [%s].', $path));
        }

        return $decoded;
    }

    protected function artifactDirectoryForPath(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($path, PATHINFO_FILENAME);
    }
}
