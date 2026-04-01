<?php

namespace App\Actions\Import;

use App\Models\Credit;
use App\Models\ImdbTitleImport;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class WriteImdbTitleVerificationReportAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Title $title, array $payload, string $artifactDirectory): string
    {
        $rawImport = ImdbTitleImport::query()
            ->where('imdb_id', $title->imdb_id)
            ->first();
        $storedPayload = is_array($rawImport?->payload) ? $rawImport->payload : [];
        $checks = [
            'title' => $this->buildCheck(
                sourceTotalCount: 1,
                downloadedCount: is_array(data_get($payload, 'title')) ? 1 : 0,
                storedPayloadCount: is_array(data_get($storedPayload, 'title')) ? 1 : 0,
                normalizedCount: Title::query()->whereKey($title->getKey())->count(),
                relationIntegrity: true,
            ),
            'credits' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'credits.totalCount', 'credits.credits'),
                downloadedCount: $this->countItems($payload, 'credits.credits'),
                storedPayloadCount: $this->countItems($storedPayload, 'credits.credits'),
                normalizedCount: Credit::query()
                    ->where('title_id', $title->id)
                    ->whereNotNull('imdb_source_group')
                    ->whereNull('episode_id')
                    ->count(),
                relationIntegrity: ! Credit::query()
                    ->where('title_id', $title->id)
                    ->whereNotNull('imdb_source_group')
                    ->where(function ($query): void {
                        $query->whereNull('person_id')
                            ->orWhereDoesntHave('person');
                    })
                    ->exists(),
            ),
            'releaseDates' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'releaseDates.totalCount', 'releaseDates.releaseDates'),
                downloadedCount: $this->countItems($payload, 'releaseDates.releaseDates'),
                storedPayloadCount: $this->countItems($storedPayload, 'releaseDates.releaseDates'),
                normalizedCount: is_array(data_get($storedPayload, 'releaseDates')) ? $this->countItems($storedPayload, 'releaseDates.releaseDates') : 0,
                relationIntegrity: $title->release_date !== null || $this->countItems($payload, 'releaseDates.releaseDates') === 0,
            ),
            'akas' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'akas.totalCount', 'akas.akas'),
                downloadedCount: $this->countItems($payload, 'akas.akas'),
                storedPayloadCount: $this->countItems($storedPayload, 'akas.akas'),
                normalizedCount: DB::table('title_translations')->where('title_id', $title->id)->count(),
                relationIntegrity: ! DB::table('title_translations')
                    ->where('title_id', $title->id)
                    ->whereNull('locale')
                    ->exists(),
            ),
            'seasons' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'seasons.totalCount', 'seasons.seasons'),
                downloadedCount: $this->countItems($payload, 'seasons.seasons'),
                storedPayloadCount: $this->countItems($storedPayload, 'seasons.seasons'),
                normalizedCount: DB::table('seasons')->where('series_id', $title->id)->count(),
                relationIntegrity: ! DB::table('seasons')->where('series_id', $title->id)->whereNull('season_number')->exists(),
            ),
            'episodes' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'episodes.totalCount', 'episodes.episodes'),
                downloadedCount: $this->countItems($payload, 'episodes.episodes'),
                storedPayloadCount: $this->countItems($storedPayload, 'episodes.episodes'),
                normalizedCount: DB::table('episodes')->where('series_id', $title->id)->count(),
                relationIntegrity: ! DB::table('episodes')
                    ->where('series_id', $title->id)
                    ->where(function ($query): void {
                        $query->whereNull('title_id')
                            ->orWhereNull('series_id');
                    })
                    ->exists(),
            ),
            'images' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'images.totalCount', 'images.images'),
                downloadedCount: $this->countItems($payload, 'images.images'),
                storedPayloadCount: $this->countItems($storedPayload, 'images.images'),
                normalizedCount: $this->countMediaAssetsByContext($title, ['title-image']),
                relationIntegrity: ! $this->hasMediaAssetsWithoutUrl($title, ['title-image']),
            ),
            'videos' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'videos.totalCount', 'videos.videos'),
                downloadedCount: $this->countItems($payload, 'videos.videos'),
                storedPayloadCount: $this->countItems($storedPayload, 'videos.videos'),
                normalizedCount: $this->countMediaAssetsByContext($title, ['title-video']),
                relationIntegrity: ! $this->hasMediaAssetsWithoutUrl($title, ['title-video']),
            ),
            'awardNominations' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'awardNominations.totalCount', 'awardNominations.awardNominations'),
                downloadedCount: $this->countItems($payload, 'awardNominations.awardNominations'),
                storedPayloadCount: $this->countItems($storedPayload, 'awardNominations.awardNominations'),
                normalizedCount: DB::table('award_nominations')->where('title_id', $title->id)->count(),
                relationIntegrity: ! DB::table('award_nominations')
                    ->where('title_id', $title->id)
                    ->whereNull('award_event_id')
                    ->exists(),
                normalizedExpectedCount: $this->expectedAwardNominationRows($payload),
            ),
            'parentsGuide' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'parentsGuide.totalCount', 'parentsGuide.advisories'),
                downloadedCount: $this->countItems($payload, 'parentsGuide.advisories'),
                storedPayloadCount: $this->countItems($storedPayload, 'parentsGuide.advisories'),
                normalizedCount: $this->countItems($storedPayload, 'parentsGuide.advisories'),
                relationIntegrity: is_array(data_get($storedPayload, 'parentsGuide')) || $this->countItems($payload, 'parentsGuide.advisories') === 0,
            ),
            'certificates' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'certificates.totalCount', 'certificates.certificates'),
                downloadedCount: $this->countItems($payload, 'certificates.certificates'),
                storedPayloadCount: $this->countItems($storedPayload, 'certificates.certificates'),
                normalizedCount: $this->countItems($storedPayload, 'certificates.certificates'),
                relationIntegrity: $title->age_rating !== null || $this->countItems($payload, 'certificates.certificates') === 0,
            ),
            'companyCredits' => $this->buildCheck(
                sourceTotalCount: $this->sourceTotalCount($payload, 'companyCredits.totalCount', 'companyCredits.companyCredits'),
                downloadedCount: $this->countItems($payload, 'companyCredits.companyCredits'),
                storedPayloadCount: $this->countItems($storedPayload, 'companyCredits.companyCredits'),
                normalizedCount: DB::table('company_title')->where('title_id', $title->id)->count(),
                relationIntegrity: ! DB::table('company_title')->where('title_id', $title->id)->whereNull('company_id')->exists(),
            ),
            'boxOffice' => $this->buildCheck(
                sourceTotalCount: is_array(data_get($payload, 'boxOffice')) ? 1 : 0,
                downloadedCount: is_array(data_get($payload, 'boxOffice')) ? 1 : 0,
                storedPayloadCount: is_array(data_get($storedPayload, 'boxOffice')) ? 1 : 0,
                normalizedCount: is_array(data_get($storedPayload, 'boxOffice')) ? 1 : 0,
                relationIntegrity: is_array(data_get($storedPayload, 'boxOffice')) || ! is_array(data_get($payload, 'boxOffice')),
            ),
        ];
        $status = collect($checks)->every(fn (array $check): bool => (bool) data_get($check, 'ok')) ? 'passed' : 'failed';
        $report = [
            'imdb_id' => $title->imdb_id,
            'verified_at' => now()->toIso8601String(),
            'status' => $status,
            'checks' => $checks,
        ];
        $path = rtrim($artifactDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'verification.json';

        File::ensureDirectoryExists($artifactDirectory);
        File::put($path, json_encode(
            $report,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        return $path;
    }

    private function sourceTotalCount(array $payload, string $totalCountPath, string $itemsPath): int
    {
        $totalCount = data_get($payload, $totalCountPath);

        if (is_numeric($totalCount)) {
            return (int) $totalCount;
        }

        return $this->countItems($payload, $itemsPath);
    }

    private function countItems(array $payload, string $path): int
    {
        $items = data_get($payload, $path);

        return is_array($items) && array_is_list($items) ? count($items) : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCheck(
        int $sourceTotalCount,
        int $downloadedCount,
        int $storedPayloadCount,
        int $normalizedCount,
        bool $relationIntegrity,
        ?int $normalizedExpectedCount = null,
    ): array {
        $normalizedExpectedCount ??= $sourceTotalCount;

        return [
            'source_total_count' => $sourceTotalCount,
            'downloaded_count' => $downloadedCount,
            'stored_payload_count' => $storedPayloadCount,
            'normalized_expected_count' => $normalizedExpectedCount,
            'normalized_count' => $normalizedCount,
            'download_complete' => $sourceTotalCount === $downloadedCount,
            'stored_payload_complete' => $sourceTotalCount === $storedPayloadCount,
            'normalized_complete' => $normalizedExpectedCount === $normalizedCount,
            'relation_integrity_ok' => $relationIntegrity,
            'ok' => $sourceTotalCount === $downloadedCount
                && $sourceTotalCount === $storedPayloadCount
                && $normalizedExpectedCount === $normalizedCount
                && $relationIntegrity,
        ];
    }

    private function expectedAwardNominationRows(array $payload): int
    {
        $rows = collect($this->normalizeObjectList(data_get($payload, 'awardNominations.awardNominations')))
            ->sum(function (array $awardNominationPayload): int {
                $nomineeCount = count($this->normalizeObjectList(data_get($awardNominationPayload, 'nominees')));

                return max(1, $nomineeCount);
            });

        return $rows > 0 ? $rows : $this->countItems($payload, 'awardNominations.awardNominations');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $values): array
    {
        return collect(is_iterable($values) ? $values : [])
            ->filter(fn (mixed $value): bool => is_array($value))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $contexts
     */
    private function countMediaAssetsByContext(Title $title, array $contexts): int
    {
        return MediaAsset::query()
            ->where('mediable_type', $title::class)
            ->where('mediable_id', $title->id)
            ->get()
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array(data_get($mediaAsset->metadata, 'source_context'), $contexts, true))
            ->count();
    }

    /**
     * @param  list<string>  $contexts
     */
    private function hasMediaAssetsWithoutUrl(Title $title, array $contexts): bool
    {
        return MediaAsset::query()
            ->where('mediable_type', $title::class)
            ->where('mediable_id', $title->id)
            ->get()
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array(data_get($mediaAsset->metadata, 'source_context'), $contexts, true))
            ->contains(fn (MediaAsset $mediaAsset): bool => ! is_string($mediaAsset->url) || trim($mediaAsset->url) === '');
    }
}
