<?php

namespace App\Actions\Import;

use Illuminate\Support\Arr;

class BuildCompactImdbPayloadAction
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function forTitle(array $payload): array
    {
        if ((int) data_get($payload, 'storageVersion', 0) >= 1) {
            return $payload;
        }

        $isBundle = is_array(data_get($payload, 'title'));
        $titlePayload = $isBundle ? data_get($payload, 'title') : $payload;

        if (! is_array($titlePayload)) {
            return [];
        }

        $compactPayload = array_filter([
            'storageVersion' => 1,
            'import' => $this->titleImportMeta($payload, $titlePayload),
            'title' => $this->titleDetails($titlePayload),
            'interests' => $this->titleInterests($payload, $titlePayload),
            'akas' => $this->titleAkas($payload),
            'seasons' => $this->titleSeasons($payload),
            'episodes' => $this->titleEpisodes($payload),
            'images' => $this->titleImages($payload),
            'videos' => $this->titleVideos($payload),
            'awardNominations' => $this->titleAwardNominations($payload),
            'releaseDates' => $this->titleReleaseDates($payload),
            'parentsGuide' => $this->titleParentsGuide($payload),
            'certificates' => $this->titleCertificates($payload),
            'companyCredits' => $this->titleCompanyCredits($payload),
            'boxOffice' => $this->titleBoxOffice($payload),
            'artifacts' => $this->artifacts($payload),
        ], fn (mixed $value): bool => ! $this->isEmptyPayloadValue($value));

        return $compactPayload;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    public function forPerson(?array $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if ((int) data_get($payload, 'storageVersion', 0) >= 1) {
            return $payload;
        }

        $detailsPayload = is_array(data_get($payload, 'details')) ? data_get($payload, 'details') : $payload;
        $compactPayload = array_filter([
            'storageVersion' => 1,
            'details' => $this->personDetails(is_array($detailsPayload) ? $detailsPayload : []),
            'images' => $this->personImages($payload),
            'relationships' => $this->personRelationships($payload),
            'trivia' => $this->personTrivia($payload),
            'filmography' => $this->personFilmography($payload),
        ], fn (mixed $value): bool => ! $this->isEmptyPayloadValue($value));

        return $compactPayload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $titlePayload
     * @return array<string, mixed>|null
     */
    public function titleImportMeta(array $payload, array $titlePayload): ?array
    {
        $meta = array_filter([
            'schemaVersion' => $this->nullableInt(data_get($payload, 'schemaVersion')),
            'imdbId' => $this->nullableString(data_get($payload, 'imdbId'))
                ?? $this->nullableString(data_get($titlePayload, 'id')),
            'sourceUrl' => $this->nullableString(data_get($payload, 'sourceUrl')),
            'downloadedAt' => $this->nullableString(data_get($payload, 'downloadedAt')),
        ], fn (mixed $value): bool => $value !== null);

        return $meta === [] ? null : $meta;
    }

    /**
     * @param  array<string, mixed>  $titlePayload
     * @return array<string, mixed>|null
     */
    public function titleDetails(array $titlePayload): ?array
    {
        return $this->compactTitleDetails($titlePayload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $titlePayload
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    public function titleInterests(array $payload, array $titlePayload): ?array
    {
        return $this->compactInterests($payload, $titlePayload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleAkas(array $payload): ?array
    {
        return is_array(data_get($payload, 'akas')) ? data_get($payload, 'akas') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleSeasons(array $payload): ?array
    {
        return is_array(data_get($payload, 'seasons')) ? data_get($payload, 'seasons') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleEpisodes(array $payload): ?array
    {
        return is_array(data_get($payload, 'episodes')) ? data_get($payload, 'episodes') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleImages(array $payload): ?array
    {
        return is_array(data_get($payload, 'images')) ? data_get($payload, 'images') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleVideos(array $payload): ?array
    {
        return is_array(data_get($payload, 'videos')) ? data_get($payload, 'videos') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleAwardNominations(array $payload): ?array
    {
        return is_array(data_get($payload, 'awardNominations')) ? data_get($payload, 'awardNominations') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleReleaseDates(array $payload): ?array
    {
        return is_array(data_get($payload, 'releaseDates')) ? data_get($payload, 'releaseDates') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleParentsGuide(array $payload): ?array
    {
        return is_array(data_get($payload, 'parentsGuide')) ? data_get($payload, 'parentsGuide') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleCertificates(array $payload): ?array
    {
        return is_array(data_get($payload, 'certificates')) ? data_get($payload, 'certificates') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleCompanyCredits(array $payload): ?array
    {
        return is_array(data_get($payload, 'companyCredits')) ? data_get($payload, 'companyCredits') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function titleBoxOffice(array $payload): ?array
    {
        return is_array(data_get($payload, 'boxOffice')) ? data_get($payload, 'boxOffice') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function artifacts(array $payload): ?array
    {
        return is_array(data_get($payload, 'artifacts')) ? data_get($payload, 'artifacts') : null;
    }

    /**
     * @param  array<string, mixed>  $detailsPayload
     * @return array<string, mixed>|null
     */
    public function personDetails(array $detailsPayload): ?array
    {
        return $this->compactPersonDetails($detailsPayload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function personRelationships(array $payload): ?array
    {
        return is_array(data_get($payload, 'relationships')) ? data_get($payload, 'relationships') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function personImages(array $payload): ?array
    {
        return is_array(data_get($payload, 'images')) ? data_get($payload, 'images') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function personTrivia(array $payload): ?array
    {
        return is_array(data_get($payload, 'trivia')) ? data_get($payload, 'trivia') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function personFilmography(array $payload): ?array
    {
        return is_array(data_get($payload, 'filmography')) ? data_get($payload, 'filmography') : null;
    }

    /**
     * @param  array<string, mixed>  $titlePayload
     * @return array<string, mixed>|null
     */
    private function compactTitleDetails(array $titlePayload): ?array
    {
        $supplemental = Arr::except($titlePayload, [
            'id',
            'type',
            'primaryTitle',
            'originalTitle',
            'primaryImage',
            'startYear',
            'endYear',
            'runtimeSeconds',
            'genres',
            'interests',
            'rating',
            'metacritic',
            'plot',
            'synopsis',
            'tagline',
            'originCountries',
            'spokenLanguages',
            'directors',
            'writers',
            'stars',
        ]);

        return $supplemental === [] ? null : $supplemental;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $titlePayload
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private function compactInterests(array $payload, array $titlePayload): ?array
    {
        $detailedInterests = data_get($payload, 'interests');

        if (is_array($detailedInterests) && $detailedInterests !== []) {
            return $detailedInterests;
        }

        $fallbackInterests = collect(data_get($titlePayload, 'interests', []))
            ->filter(fn (mixed $interest): bool => is_array($interest) && filled(data_get($interest, 'name')))
            ->map(function (array $interest): array {
                return array_filter([
                    'id' => $this->nullableString(data_get($interest, 'id')),
                    'name' => $this->nullableString(data_get($interest, 'name')),
                    'isSubgenre' => (bool) data_get($interest, 'isSubgenre', false),
                ], fn (mixed $value): bool => $value !== null);
            })
            ->values()
            ->all();

        return $fallbackInterests === [] ? null : $fallbackInterests;
    }

    /**
     * @param  array<string, mixed>  $detailsPayload
     * @return array<string, mixed>|null
     */
    private function compactPersonDetails(array $detailsPayload): ?array
    {
        $supplemental = Arr::except($detailsPayload, [
            'id',
            'displayName',
            'alternativeNames',
            'primaryProfessions',
            'primaryImage',
            'biography',
            'birthDate',
            'deathDate',
            'birthLocation',
            'deathLocation',
            'meterRanking',
        ]);
        $meterRanking = $this->personMeterRankingSupplemental(data_get($detailsPayload, 'meterRanking'));

        if ($meterRanking !== null) {
            $supplemental['meterRanking'] = $meterRanking;
        }

        return $supplemental === [] ? null : $supplemental;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function personMeterRankingSupplemental(mixed $meterRanking): ?array
    {
        if (! is_array($meterRanking)) {
            return null;
        }

        $supplemental = Arr::except($meterRanking, [
            'currentRank',
            'rank',
        ]);

        return $supplemental === [] ? null : $supplemental;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function isEmptyPayloadValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_array($value) && $value === [];
    }
}
