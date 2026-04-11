<?php

namespace App\Actions\Import;

use App\Enums\MediaKind;
use App\Models\Credit;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportImdbNamePayloadAction
{
    private bool $fillMissingOnly = false;

    public function __construct(
        private readonly BuildCompactImdbPayloadAction $buildCompactImdbPayloadAction,
        private readonly EnsureLegacyImportPipelineIsEnabledAction $ensureLegacyImportPipelineIsEnabledAction,
        private readonly WriteImdbEndpointImportReportAction $writeImdbEndpointImportReportAction,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, ?string $storagePath = null, array $options = []): Person
    {
        $this->ensureLegacyImportPipelineIsEnabledAction->handle();

        $previousFillMissingOnly = $this->fillMissingOnly;
        $this->fillMissingOnly = (bool) ($options['fill_missing_only'] ?? false);

        try {
            $detailsPayload = is_array(data_get($payload, 'details')) ? data_get($payload, 'details') : $payload;
            $imdbId = $this->requiredImdbPersonId($detailsPayload);
            $artifactDirectory = $this->resolveArtifactDirectory($storagePath, $imdbId);

            return DB::transaction(function () use ($payload, $detailsPayload, $imdbId, $artifactDirectory): Person {
                $detailsBefore = $this->snapshotPersonEndpointState($imdbId, [
                    'name' => 'Name',
                    'biography' => 'Biography',
                    'short_biography' => 'Short biography',
                    'known_for_department' => 'Known for department',
                    'birth_date' => 'Birth date',
                    'death_date' => 'Death date',
                    'birth_place' => 'Birth place',
                    'death_place' => 'Death place',
                    'nationality' => 'Nationality',
                    'popularity_rank' => 'Popularity rank',
                    'search_keywords' => 'Search keywords',
                ], [
                    'alternate_names' => fn (Person $person): array => collect($person->resolvedAlternateNames())
                        ->mapWithKeys(fn (string $value): array => [Str::lower($value) => $value])
                        ->all(),
                    'professions' => fn (Person $person): array => $person->professions()
                        ->get()
                        ->mapWithKeys(fn (PersonProfession $profession): array => [
                            $profession->profession => $profession->profession.' · '.$profession->department,
                        ])
                        ->all(),
                    'payload_sections' => fn (Person $person): array => collect(is_array($person->imdb_payload) ? $person->imdb_payload : [])
                        ->except(['storageVersion', 'relationships', 'trivia', 'filmography'])
                        ->mapWithKeys(fn (mixed $value, string $key): array => [$key => Str::headline($key)])
                        ->all(),
                ]);
                $person = $this->upsertPerson($payload, $detailsPayload, $imdbId);
                $detailsAfter = $this->snapshotPersonEndpointState($imdbId, [
                    'name' => 'Name',
                    'biography' => 'Biography',
                    'short_biography' => 'Short biography',
                    'known_for_department' => 'Known for department',
                    'birth_date' => 'Birth date',
                    'death_date' => 'Death date',
                    'birth_place' => 'Birth place',
                    'death_place' => 'Death place',
                    'nationality' => 'Nationality',
                    'popularity_rank' => 'Popularity rank',
                    'search_keywords' => 'Search keywords',
                ], [
                    'alternate_names' => fn (Person $person): array => collect($person->resolvedAlternateNames())
                        ->mapWithKeys(fn (string $value): array => [Str::lower($value) => $value])
                        ->all(),
                    'professions' => fn (Person $person): array => $person->professions()
                        ->get()
                        ->mapWithKeys(fn (PersonProfession $profession): array => [
                            $profession->profession => $profession->profession.' · '.$profession->department,
                        ])
                        ->all(),
                    'payload_sections' => fn (Person $person): array => collect(is_array($person->imdb_payload) ? $person->imdb_payload : [])
                        ->except(['storageVersion', 'relationships', 'trivia', 'filmography'])
                        ->mapWithKeys(fn (mixed $value, string $key): array => [$key => Str::headline($key)])
                        ->all(),
                ]);
                $this->writeEndpointReport(
                    $artifactDirectory,
                    'details',
                    true,
                    $detailsBefore,
                    $detailsAfter,
                    [
                        'artifact_path' => 'details.json',
                        'imdb_id' => $imdbId,
                    ],
                );

                $imagesBefore = $this->snapshotPersonEndpointState($imdbId, [], [
                    'media_assets' => fn (Person $person): array => $this->personMediaAssetSnapshot($person),
                    'payload_sections' => fn (Person $person): array => $this->personPayloadSectionSnapshot($person, ['images']),
                ]);
                $this->syncPersonMediaAssets($person, $detailsPayload, $payload);
                $imagesAfter = $this->snapshotPersonEndpointState($imdbId, [], [
                    'media_assets' => fn (Person $person): array => $this->personMediaAssetSnapshot($person),
                    'payload_sections' => fn (Person $person): array => $this->personPayloadSectionSnapshot($person, ['images']),
                ]);
                $this->writeEndpointReport(
                    $artifactDirectory,
                    'images',
                    is_array(data_get($payload, 'images')),
                    $imagesBefore,
                    $imagesAfter,
                    [
                        'artifact_path' => 'images.json',
                        'imdb_id' => $imdbId,
                    ],
                );

                $filmographyBefore = $this->snapshotPersonEndpointState($imdbId, [], [
                    'credits' => fn (Person $person): array => $this->personCreditSnapshot($person, 'imdb:filmography'),
                    'payload_sections' => fn (Person $person): array => $this->personPayloadSectionSnapshot($person, ['filmography']),
                ]);
                $this->syncFilmography($person, $payload);
                $filmographyAfter = $this->snapshotPersonEndpointState($imdbId, [], [
                    'credits' => fn (Person $person): array => $this->personCreditSnapshot($person, 'imdb:filmography'),
                    'payload_sections' => fn (Person $person): array => $this->personPayloadSectionSnapshot($person, ['filmography']),
                ]);
                $this->writeEndpointReport(
                    $artifactDirectory,
                    'filmography',
                    is_array(data_get($payload, 'filmography')),
                    $filmographyBefore,
                    $filmographyAfter,
                    [
                        'artifact_path' => 'filmography.json',
                        'imdb_id' => $imdbId,
                    ],
                );

                $relationshipsBefore = $this->snapshotPersonEndpointState($imdbId, [], [
                    'payload_sections' => fn (Person $person): array => $this->personPayloadSectionSnapshot($person, ['relationships']),
                ]);
                $this->syncPayloadSection($person, 'relationships', $this->buildCompactImdbPayloadAction->personRelationships($payload));
                $relationshipsAfter = $this->snapshotPersonEndpointState($imdbId, [], [
                    'payload_sections' => fn (Person $person): array => $this->personPayloadSectionSnapshot($person, ['relationships']),
                ]);
                $this->writeEndpointReport(
                    $artifactDirectory,
                    'relationships',
                    is_array(data_get($payload, 'relationships')),
                    $relationshipsBefore,
                    $relationshipsAfter,
                    [
                        'artifact_path' => 'relationships.json',
                        'imdb_id' => $imdbId,
                    ],
                );

                $triviaBefore = $this->snapshotPersonEndpointState($imdbId, [], [
                    'payload_sections' => fn (Person $person): array => $this->personPayloadSectionSnapshot($person, ['trivia']),
                ]);
                $this->syncPayloadSection($person, 'trivia', $this->buildCompactImdbPayloadAction->personTrivia($payload));
                $triviaAfter = $this->snapshotPersonEndpointState($imdbId, [], [
                    'payload_sections' => fn (Person $person): array => $this->personPayloadSectionSnapshot($person, ['trivia']),
                ]);
                $this->writeEndpointReport(
                    $artifactDirectory,
                    'trivia',
                    is_array(data_get($payload, 'trivia')),
                    $triviaBefore,
                    $triviaAfter,
                    [
                        'artifact_path' => 'trivia.json',
                        'imdb_id' => $imdbId,
                    ],
                );

                return $person->fresh([
                    'mediaAssets',
                    'professions',
                ]);
            });
        } finally {
            $this->fillMissingOnly = $previousFillMissingOnly;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $detailsPayload
     */
    private function upsertPerson(array $payload, array $detailsPayload, string $imdbId): Person
    {
        $displayName = $this->requiredNonEmptyString($detailsPayload, 'displayName');
        $alternativeNames = $this->uniqueStrings([
            ...$this->normalizeStringList(data_get($detailsPayload, 'alternativeNames')),
            ...array_filter([$this->nullableString(data_get($detailsPayload, 'birthName'))]),
        ]);
        $primaryProfessions = $this->uniqueStrings($this->normalizeStringList(data_get($detailsPayload, 'primaryProfessions')));
        $person = Person::query()->withTrashed()->firstOrNew([
            'imdb_id' => $imdbId,
        ]);
        $biography = $this->nullableString(data_get($detailsPayload, 'biography'));
        $editorialAlternateNames = $this->removeImportedAlternateNames(
            $person->alternate_names,
            $alternativeNames,
        );

        $person->fill([
            'imdb_id' => $imdbId,
            'name' => $this->preferStringValue($person->name, $displayName),
            'alternate_names' => $this->preferStringValue(
                $person->alternate_names,
                $editorialAlternateNames === [] ? null : implode(' | ', $editorialAlternateNames),
            ),
            'imdb_alternative_names' => $alternativeNames === []
                ? ($person->imdb_alternative_names ?? [])
                : $this->preferArrayValue($person->imdb_alternative_names, $alternativeNames),
            'imdb_primary_professions' => $primaryProfessions === []
                ? ($person->imdb_primary_professions ?? [])
                : $this->preferArrayValue($person->imdb_primary_professions, $primaryProfessions),
            'imdb_payload' => $this->mergeCompactPayload(
                $person->imdb_payload,
                array_filter([
                    'storageVersion' => 1,
                    'details' => $this->buildCompactImdbPayloadAction->personDetails($detailsPayload),
                ], fn (mixed $value): bool => $value !== null && $value !== []),
            ),
            'biography' => $this->preferStringValue($person->biography, $biography),
            'short_biography' => $this->preferStringValue($person->short_biography, $this->extractShortBiography($biography)),
            'known_for_department' => $this->preferStringValue($person->known_for_department, $this->knownForDepartment($primaryProfessions)),
            'birth_date' => $this->preferNullableValue(
                optional($person->birth_date)?->toDateString(),
                $this->precisionDate(data_get($detailsPayload, 'birthDate')),
            ),
            'death_date' => $this->preferNullableValue(
                optional($person->death_date)?->toDateString(),
                $this->precisionDate(data_get($detailsPayload, 'deathDate')),
            ),
            'birth_place' => $this->preferStringValue($person->birth_place, $this->nullableString(data_get($detailsPayload, 'birthLocation'))),
            'death_place' => $this->preferStringValue($person->death_place, $this->nullableString(data_get($detailsPayload, 'deathLocation'))),
            'nationality' => $this->preferStringValue($person->nationality, $this->nullableString(data_get($detailsPayload, 'nationality'))),
            'popularity_rank' => $this->preferNumericValue(
                $person->popularity_rank,
                $this->nullableInt(data_get($detailsPayload, 'meterRanking.currentRank'))
                    ?? $this->nullableInt(data_get($detailsPayload, 'meterRanking.rank')),
            ),
            'search_keywords' => $this->preferStringValue($person->search_keywords, $this->personSearchKeywords($alternativeNames, $primaryProfessions)),
            'is_published' => true,
        ]);
        $person->save();

        if ($person->trashed()) {
            $person->restore();
        }

        foreach ($primaryProfessions as $sortOrder => $professionLabel) {
            [$profession, $department] = $this->mapProfession($professionLabel);
            $this->upsertProfession($person, $profession, $department, $sortOrder === 0, $sortOrder);
        }

        return $person;
    }

    private function upsertProfession(
        Person $person,
        string $profession,
        string $department,
        bool $isPrimary,
        int $sortOrder,
    ): void {
        $personProfession = PersonProfession::query()->firstOrNew([
            'person_id' => $person->id,
            'profession' => $profession,
        ]);

        $personProfession->fill([
            'department' => $this->preferStringValue($personProfession->department, $department),
            'is_primary' => $personProfession->exists && $this->fillMissingOnly ? $personProfession->is_primary : $isPrimary,
            'sort_order' => $this->preferNumericValue($personProfession->sort_order, $sortOrder),
        ]);
        $personProfession->save();
    }

    /**
     * @param  array<string, mixed>  $detailsPayload
     * @param  array<string, mixed>  $payload
     */
    private function syncPersonMediaAssets(Person $person, array $detailsPayload, array $payload): void
    {
        $importedProviderKeys = [];
        $imagesPayload = data_get($payload, 'images');
        $hasFullImages = is_array($imagesPayload);

        if ($hasFullImages) {
            $this->syncPayloadSection($person, 'images', $this->buildCompactImdbPayloadAction->personImages($payload));

            foreach ($this->normalizeObjectList(data_get($imagesPayload, 'images')) as $index => $imagePayload) {
                $url = $this->nullableString(data_get($imagePayload, 'url'));

                if ($url === null) {
                    continue;
                }

                $importedProviderKeys[] = $this->upsertMediaAsset(
                    $this->mapPersonImageKind($imagePayload),
                    $person,
                    hash('sha1', 'person-image:'.$person->imdb_id.':'.$url),
                    $url,
                    $person->name,
                    $this->nullableString(data_get($imagePayload, 'type')),
                    $this->nullableInt(data_get($imagePayload, 'width')),
                    $this->nullableInt(data_get($imagePayload, 'height')),
                    false,
                    $index + 1,
                    [
                        'source_context' => 'person-image',
                        'image' => $imagePayload,
                    ],
                );
            }
        }

        $primaryImage = data_get($detailsPayload, 'primaryImage');

        if (is_array($primaryImage)) {
            $url = $this->nullableString(data_get($primaryImage, 'url'));

            if ($url !== null) {
                $importedProviderKeys[] = $this->upsertMediaAsset(
                    MediaKind::Headshot,
                    $person,
                    hash('sha1', 'person-image:'.$person->imdb_id.':'.$url),
                    $url,
                    $person->name,
                    'primary',
                    $this->nullableInt(data_get($primaryImage, 'width')),
                    $this->nullableInt(data_get($primaryImage, 'height')),
                    true,
                    0,
                    [
                        'source_context' => 'person-primary-image',
                        'image' => $primaryImage,
                    ],
                );
            }
        }

        if (! $this->fillMissingOnly && $hasFullImages) {
            $this->removeStaleImdbMediaAssets($person, $importedProviderKeys);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncFilmography(Person $person, array $payload): void
    {
        $filmographyPayload = $this->buildCompactImdbPayloadAction->personFilmography($payload);
        $this->syncPayloadSection($person, 'filmography', $filmographyPayload);

        foreach ($this->normalizeObjectList(data_get($payload, 'filmography.credits')) as $index => $creditPayload) {
            $titleImdbId = $this->nullableString(data_get($creditPayload, 'title.id'));

            if ($titleImdbId === null) {
                continue;
            }

            $title = Title::query()->withTrashed()->where('imdb_id', $titleImdbId)->first();

            if (! $title instanceof Title) {
                continue;
            }

            $category = $this->nullableString(data_get($creditPayload, 'category')) ?? 'crew';
            [$professionName, $department] = $this->mapProfession($category);
            $profession = PersonProfession::query()->firstOrNew([
                'person_id' => $person->id,
                'profession' => $professionName,
            ]);

            $profession->fill([
                'department' => $this->preferStringValue($profession->department, $department),
                'is_primary' => $profession->exists && $this->fillMissingOnly ? $profession->is_primary : $index === 0,
                'sort_order' => $this->preferNumericValue($profession->sort_order, $index),
            ]);
            $profession->save();

            $credit = Credit::query()->withTrashed()->firstOrNew([
                'title_id' => $title->id,
                'person_id' => $person->id,
                'department' => $department,
                'job' => $professionName,
                'episode_id' => null,
                'imdb_source_group' => 'imdb:filmography',
            ]);

            $characters = $this->normalizeStringList(data_get($creditPayload, 'characters'));

            $credit->fill([
                'character_name' => $this->preferStringValue($credit->character_name, $characters === [] ? null : implode(' | ', $characters)),
                'billing_order' => $this->preferNumericValue($credit->billing_order, $index + 1),
                'is_principal' => $credit->exists && $this->fillMissingOnly ? $credit->is_principal : false,
                'person_profession_id' => $this->preferNumericValue($credit->person_profession_id, $profession->id),
                'credited_as' => $this->preferStringValue($credit->credited_as, null),
                'imdb_source_group' => 'imdb:filmography',
            ]);
            $credit->save();

            if ($credit->trashed()) {
                $credit->restore();
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $sectionPayload
     */
    private function syncPayloadSection(Person $person, string $key, ?array $sectionPayload): void
    {
        if (! is_array($sectionPayload) || $sectionPayload === []) {
            return;
        }

        $person->forceFill([
            'imdb_payload' => $this->mergeCompactPayload($person->imdb_payload, [
                'storageVersion' => 1,
                $key => $sectionPayload,
            ]),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function upsertMediaAsset(
        MediaKind $kind,
        Person $person,
        string $providerKey,
        string $url,
        ?string $altText,
        ?string $caption,
        ?int $width,
        ?int $height,
        bool $isPrimary,
        int $position,
        array $metadata,
    ): string {
        $asset = MediaAsset::query()->withTrashed()->firstOrNew([
            'mediable_type' => $person::class,
            'mediable_id' => $person->getKey(),
            'provider' => 'imdb',
            'provider_key' => $providerKey,
        ]);

        $asset->fill([
            'kind' => $this->preferStringValue($asset->kind?->value ?? $asset->getRawOriginal('kind'), $kind->value),
            'url' => $this->preferStringValue($asset->url, $url),
            'alt_text' => $this->preferStringValue($asset->alt_text, $altText),
            'caption' => $this->preferStringValue($asset->caption, $caption),
            'width' => $this->preferNumericValue($asset->width, $width),
            'height' => $this->preferNumericValue($asset->height, $height),
            'provider' => 'imdb',
            'provider_key' => $providerKey,
            'metadata' => $this->mergeCompactPayload($asset->metadata, $metadata),
            'is_primary' => $asset->exists && $this->fillMissingOnly ? $asset->is_primary : $isPrimary,
            'position' => $this->preferNumericValue($asset->position, $position),
        ]);
        $asset->save();

        if ($asset->trashed()) {
            $asset->restore();
        }

        return $providerKey;
    }

    private function removeStaleImdbMediaAssets(Person $person, array $importedProviderKeys): void
    {
        $staleAssets = MediaAsset::query()
            ->withTrashed()
            ->where('mediable_type', $person::class)
            ->where('mediable_id', $person->getKey())
            ->where('provider', 'imdb');

        if ($importedProviderKeys !== []) {
            $staleAssets->whereNotIn('provider_key', array_values(array_unique($importedProviderKeys)));
        }

        $staleAssets->get()->each->delete();
    }

    private function mapPersonImageKind(array $imagePayload): MediaKind
    {
        $width = $this->nullableInt(data_get($imagePayload, 'width')) ?? 0;
        $height = $this->nullableInt(data_get($imagePayload, 'height')) ?? 0;

        return $height >= $width ? MediaKind::Headshot : MediaKind::Gallery;
    }

    /**
     * @param  list<string>  $alternativeNames
     * @param  list<string>  $primaryProfessions
     */
    private function personSearchKeywords(array $alternativeNames, array $primaryProfessions): ?string
    {
        $keywords = $this->uniqueStrings([
            ...$alternativeNames,
            ...$primaryProfessions,
        ]);

        return $keywords === [] ? null : implode(', ', $keywords);
    }

    /**
     * @param  list<string>  $primaryProfessions
     */
    private function knownForDepartment(array $primaryProfessions): ?string
    {
        if ($primaryProfessions === []) {
            return null;
        }

        return $this->mapProfession($primaryProfessions[0])[1];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function mapProfession(?string $rawProfession): array
    {
        $normalized = Str::of((string) $rawProfession)
            ->replace(['_', '-'], ' ')
            ->trim()
            ->lower()
            ->value();

        return match ($normalized) {
            'actor' => ['Actor', 'Cast'],
            'actress' => ['Actress', 'Cast'],
            'self' => ['Self', 'Cast'],
            'archive footage', 'archive sound' => ['Archive', 'Cast'],
            'director' => ['Director', 'Directing'],
            'writer', 'screenplay', 'story', 'creator' => ['Writer', 'Writing'],
            'producer', 'executive producer', 'associate producer', 'co producer' => ['Producer', 'Production'],
            'editor', 'editorial department' => ['Editor', 'Editing'],
            'composer', 'music department', 'soundtrack' => ['Composer', 'Music'],
            'cinematographer', 'camera department' => ['Cinematographer', 'Camera'],
            'art department', 'art director', 'production designer' => ['Production Designer', 'Art'],
            'costume designer', 'costume department' => ['Costume Designer', 'Costume'],
            'make up department' => ['Make-Up', 'Make-Up'],
            'visual effects' => ['Visual Effects', 'Visual Effects'],
            'animation department' => ['Animation', 'Animation'],
            'stunts' => ['Stunts', 'Stunts'],
            'script department' => ['Script Department', 'Writing'],
            'miscellaneous' => ['Miscellaneous', 'Crew'],
            default => [Str::title($normalized), 'Crew'],
        };
    }

    private function extractShortBiography(?string $biography): ?string
    {
        if ($biography === null) {
            return null;
        }

        $paragraphs = preg_split('/\R{2,}/', trim($biography)) ?: [];
        $summary = collect($paragraphs)
            ->map(fn (string $paragraph): string => trim(preg_replace('/\s+/', ' ', $paragraph) ?? $paragraph))
            ->first(fn (string $paragraph): bool => $paragraph !== '');

        return $summary === null ? null : Str::limit($summary, 280);
    }

    /**
     * @param  list<string>  $importedAlternateNames
     * @return list<string>
     */
    private function removeImportedAlternateNames(?string $storedAlternateNames, array $importedAlternateNames): array
    {
        $storedNames = collect(preg_split('/\s*\|\s*/', $storedAlternateNames ?? '') ?: [])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values()
            ->all();

        if ($storedNames === []) {
            return [];
        }

        return collect($storedNames)
            ->reject(fn (string $storedName): bool => in_array($storedName, $importedAlternateNames, true))
            ->unique()
            ->values()
            ->all();
    }

    private function preferStringValue(mixed $existing, ?string $incoming): ?string
    {
        $existingValue = $this->nullableString($existing);
        $incomingValue = $this->nullableString($incoming);

        if ($this->fillMissingOnly) {
            return $existingValue ?? $incomingValue;
        }

        return $incomingValue ?? $existingValue;
    }

    private function preferNumericValue(mixed $existing, mixed $incoming): int|float|null
    {
        $existingValue = $this->normalizeNumericValue($existing);
        $incomingValue = $this->normalizeNumericValue($incoming);

        if ($this->fillMissingOnly) {
            return $existingValue ?? $incomingValue;
        }

        return $incomingValue ?? $existingValue;
    }

    private function preferNullableValue(mixed $existing, mixed $incoming): mixed
    {
        if ($this->fillMissingOnly) {
            return $this->hasMeaningfulValue($existing) ? $existing : $incoming;
        }

        return $this->hasMeaningfulValue($incoming) ? $incoming : $existing;
    }

    /**
     * @param  array<int|string, mixed>|null  $existing
     * @param  array<int|string, mixed>  $incoming
     * @return array<int|string, mixed>
     */
    private function preferArrayValue(?array $existing, array $incoming): array
    {
        $existingValue = is_array($existing) ? $existing : [];

        if ($existingValue === []) {
            return $incoming;
        }

        if ($incoming === []) {
            return $existingValue;
        }

        if (! $this->fillMissingOnly) {
            return $incoming;
        }

        return $this->mergeListPayload($existingValue, $incoming);
    }

    /**
     * @param  array<int|string, mixed>|null  $existing
     * @param  array<int|string, mixed>|null  $incoming
     * @return array<int|string, mixed>
     */
    private function mergeCompactPayload(?array $existing, ?array $incoming): array
    {
        if (! is_array($existing) || $existing === []) {
            return is_array($incoming) ? $incoming : [];
        }

        if (! is_array($incoming) || $incoming === []) {
            return $existing;
        }

        $merged = $this->mergePayloadValues($existing, $incoming);

        return is_array($merged) ? $merged : $existing;
    }

    private function hasMeaningfulValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }

    private function normalizeNumericValue(mixed $value): int|float|null
    {
        if (! is_numeric($value)) {
            return null;
        }

        $stringValue = (string) $value;

        return str_contains($stringValue, '.') ? (float) $value : (int) $value;
    }

    private function mergePayloadValues(mixed $existing, mixed $incoming): mixed
    {
        if (! is_array($existing) || ! is_array($incoming)) {
            return $this->preferNullableValue($existing, $incoming);
        }

        if (array_is_list($existing) && array_is_list($incoming)) {
            return $this->mergeListPayload($existing, $incoming);
        }

        $merged = $existing;

        foreach ($incoming as $key => $value) {
            if (! array_key_exists($key, $merged)) {
                $merged[$key] = $value;

                continue;
            }

            $merged[$key] = $this->mergePayloadValues($merged[$key], $value);
        }

        return $merged;
    }

    /**
     * @param  list<mixed>  $existing
     * @param  list<mixed>  $incoming
     * @return list<mixed>
     */
    private function mergeListPayload(array $existing, array $incoming): array
    {
        $merged = array_values($existing);
        $indexByKey = [];

        foreach ($merged as $index => $item) {
            $indexByKey[$this->payloadListKey($item)] = $index;
        }

        foreach ($incoming as $item) {
            $itemKey = $this->payloadListKey($item);

            if (! array_key_exists($itemKey, $indexByKey)) {
                $merged[] = $item;
                $indexByKey[$itemKey] = array_key_last($merged);

                continue;
            }

            $existingIndex = $indexByKey[$itemKey];
            $merged[$existingIndex] = $this->mergePayloadValues($merged[$existingIndex], $item);
        }

        return array_values($merged);
    }

    private function payloadListKey(mixed $value): string
    {
        if (is_array($value)) {
            foreach (['id', 'code', 'slug', 'locale', 'name', 'url'] as $key) {
                $candidate = data_get($value, $key);

                if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                    return $key.':'.Str::lower(trim((string) $candidate));
                }
            }

            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return 'json:'.($encoded === false ? serialize($value) : $encoded);
        }

        if (is_bool($value)) {
            return 'bool:'.($value ? '1' : '0');
        }

        if (is_scalar($value) || $value === null) {
            return get_debug_type($value).':'.(string) $value;
        }

        return 'serialized:'.serialize($value);
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $values): array
    {
        return collect(is_iterable($values) ? $values : [])
            ->map(fn (mixed $value): ?string => $this->nullableString($value))
            ->filter()
            ->values()
            ->all();
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
     * @param  list<string>  $values
     * @return list<string>
     */
    private function uniqueStrings(array $values): array
    {
        return collect($values)
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $fieldLabels
     * @param  array<string, callable(Person): array<string, string>>  $relationResolvers
     * @return array{
     *     exists: bool,
     *     fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }
     */
    private function snapshotPersonEndpointState(string $imdbId, array $fieldLabels, array $relationResolvers = []): array
    {
        $person = Person::query()->withTrashed()->where('imdb_id', $imdbId)->first();

        if (! $person instanceof Person) {
            return [
                'exists' => false,
                'fields' => [],
                'relations' => [],
            ];
        }

        return [
            'exists' => true,
            'fields' => collect($fieldLabels)
                ->filter(function (string $label, string $field) use ($person): bool {
                    $value = data_get($person, $field);

                    if ($value === null) {
                        return false;
                    }

                    return ! is_string($value) || trim($value) !== '';
                })
                ->all(),
            'relations' => collect($relationResolvers)
                ->map(fn (callable $resolver): array => $resolver($person))
                ->all(),
        ];
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    private function personPayloadSectionSnapshot(Person $person, array $keys): array
    {
        return collect($keys)
            ->filter(fn (string $key): bool => is_array(data_get($person->imdb_payload, $key)))
            ->mapWithKeys(fn (string $key): array => [$key => Str::headline($key)])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function personMediaAssetSnapshot(Person $person): array
    {
        return $person->mediaAssets()
            ->get()
            ->mapWithKeys(function (MediaAsset $mediaAsset): array {
                $kind = $mediaAsset->kind?->value ?? $mediaAsset->getRawOriginal('kind');
                $label = collect([$kind, $mediaAsset->caption, $mediaAsset->url])->filter()->implode(' · ');

                return [$mediaAsset->provider_key => $label];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function personCreditSnapshot(Person $person, string $sourceGroup): array
    {
        return $person->credits()
            ->with('title:id,name,imdb_id')
            ->where('imdb_source_group', $sourceGroup)
            ->get()
            ->mapWithKeys(function (Credit $credit): array {
                $character = filled($credit->character_name) ? ' · '.$credit->character_name : '';

                return [
                    implode('|', [$credit->title?->imdb_id, $credit->job, $credit->id]) => trim(($credit->title?->name ?? 'Unknown title').' · '.$credit->job.$character),
                ];
            })
            ->all();
    }

    /**
     * @param  array{
     *     exists: bool,
     *     fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }  $before
     * @param  array{
     *     exists: bool,
     *     fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }  $after
     * @param  array<string, mixed>  $meta
     */
    private function writeEndpointReport(string $artifactDirectory, string $endpoint, bool $hasPayload, array $before, array $after, array $meta = []): void
    {
        $addedRelations = [];
        $existingRelations = [];

        foreach ($after['relations'] as $relation => $values) {
            $beforeValues = $before['relations'][$relation] ?? [];
            $existing = array_values($beforeValues);
            $added = array_values(array_diff_key($values, $beforeValues));

            if ($existing !== []) {
                $existingRelations[$relation] = $existing;
            }

            if ($added !== []) {
                $addedRelations[$relation] = $added;
            }
        }

        $this->writeImdbEndpointImportReportAction->handle($artifactDirectory, $endpoint, array_merge([
            'endpoint' => $endpoint,
            'processed_at' => now()->toIso8601String(),
            'has_payload' => $hasPayload,
            'new_record' => ! $before['exists'] && $after['exists'],
            'existing_field_map' => $before['fields'],
            'added_field_map' => array_diff_key($after['fields'], $before['fields']),
            'existing_fields' => array_values($before['fields']),
            'added_fields' => array_values(array_diff_key($after['fields'], $before['fields'])),
            'existing_relations' => $existingRelations,
            'added_relations' => $addedRelations,
        ], $meta));
    }

    private function resolveArtifactDirectory(?string $storagePath, string $imdbId): string
    {
        if (! is_string($storagePath) || trim($storagePath) === '') {
            $path = storage_path('app/private/imdb-temp/names/'.$imdbId.'/details.json');

            return dirname($path);
        }

        if (str_ends_with(str_replace('\\', '/', $storagePath), '.json')) {
            $directory = dirname($storagePath);

            if (is_string($directory) && $directory !== '' && ! str_ends_with(str_replace('\\', '/', $directory), '/'.$imdbId)) {
                return $directory.DIRECTORY_SEPARATOR.$imdbId;
            }

            return $directory;
        }

        return rtrim($storagePath, DIRECTORY_SEPARATOR);
    }

    private function requiredImdbPersonId(array $payload): string
    {
        $imdbId = data_get($payload, 'id');

        if (! is_string($imdbId) || preg_match('/^nm\d+$/', $imdbId) !== 1) {
            throw new RuntimeException('Payload is missing a valid IMDb person identifier.');
        }

        return $imdbId;
    }

    private function requiredNonEmptyString(array $payload, string $key): string
    {
        $value = data_get($payload, $key);

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf('Payload is missing [%s].', $key));
        }

        return trim($value);
    }

    private function precisionDate(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        $year = $this->nullableInt(data_get($value, 'year'));
        $month = $this->nullableInt(data_get($value, 'month'));
        $day = $this->nullableInt(data_get($value, 'day'));

        if ($year === null || $month === null || $day === null) {
            return null;
        }

        try {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
