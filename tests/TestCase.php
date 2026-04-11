<?php

namespace Tests;

use App\Actions\Import\DownloadImdbTitlePayloadAction;
use App\Actions\Import\FetchImdbGraphqlAction;
use App\Actions\Import\FetchImdbJsonAction;
use App\Actions\Import\ImportImdbNamePayloadAction;
use App\Actions\Import\ImportImdbTitlePayloadAction;
use App\Actions\Import\WriteImdbNameVerificationReportAction;
use App\Actions\Import\WriteImdbTitleVerificationReportAction;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Sleep;
use ReflectionClass;
use RuntimeException;
use Tests\Concerns\UsesCatalogOnlyApplication;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            $this->isCatalogOnlySurface()
            && ! $this->supportsCatalogOnlyApplicationTestContract()
        ) {
            $this->markTestSkipped('Legacy local-schema or write-side test is not applicable in the current catalog-only MySQL application mode.');
        }

        Sleep::fake();
        Cache::flush();

        config()->set('services.imdb.graphql.enabled', false);
        config()->set('services.imdb.http_cache.enabled', false);
        FetchImdbGraphqlAction::flushMemoryCache();

        $property = (new ReflectionClass(FetchImdbJsonAction::class))
            ->getProperty('lastRequestFinishedAtMicroseconds');

        $property->setValue(null, null);
    }

    protected function isCatalogOnlySurface(): bool
    {
        return Route::has('public.home')
            && ! Route::has('account.watchlist')
            && ! Route::has('admin.dashboard');
    }

    protected function supportsCatalogOnlyApplicationTestContract(): bool
    {
        return in_array(
            UsesCatalogOnlyApplication::class,
            class_uses_recursive(static::class),
            true,
        );
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
