<?php

namespace App\Actions\Import;

use App\Actions\Import\Concerns\ManagesImdbImportConcurrency;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\MovieGenre;
use App\Models\MovieMetacritic;
use App\Models\MovieRating;
use App\Models\NameBasic;
use App\Models\NameBasicAlternativeName;
use App\Models\NameBasicKnownForTitle;
use App\Models\NameBasicMeterRanking;
use App\Models\NameBasicPrimaryImage;
use App\Models\NameBasicProfession;
use App\Models\NameCredit;
use App\Models\NameCreditCharacter;
use App\Models\NameCreditSummary;
use App\Models\NameImage;
use App\Models\NameImageSummary;
use App\Models\NameRelationship;
use App\Models\NameRelationshipAttribute;
use App\Models\NameRelationType;
use App\Models\NameTriviaEntry;
use App\Models\NameTriviaSummary;
use App\Models\Profession;
use App\Models\RelationAttribute;
use App\Models\TitleType;
use RuntimeException;

class ImportImdbCatalogNamePayloadAction
{
    use ManagesImdbImportConcurrency;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): NameBasic
    {
        $detailsPayload = is_array(data_get($payload, 'details')) ? data_get($payload, 'details') : $payload;

        if (! is_array($detailsPayload)) {
            throw new RuntimeException('The IMDb name payload is missing the details object.');
        }

        $imdbId = $this->requiredImdbId($detailsPayload);

        /** @var NameBasic $person */
        $person = $this->runLockedImport('name', $imdbId, function () use ($payload, $detailsPayload): NameBasic {
            $person = $this->upsertPerson($detailsPayload);

            $this->syncAlternativeNames($person, $this->alternativeNames($detailsPayload));
            $this->syncProfessions($person, $this->normalizeStringList(data_get($detailsPayload, 'primaryProfessions')));
            $this->syncPrimaryImage($person, is_array(data_get($detailsPayload, 'primaryImage')) ? data_get($detailsPayload, 'primaryImage') : []);
            $this->syncImages($person, is_array(data_get($payload, 'images')) ? data_get($payload, 'images') : []);
            $this->syncFilmography($person, is_array(data_get($payload, 'filmography')) ? data_get($payload, 'filmography') : []);
            $this->syncKnownForTitles(
                $person,
                $this->normalizeObjectList(data_get($detailsPayload, 'knownForTitles')),
                $this->normalizeObjectList(data_get($payload, 'filmography.credits')),
            );
            $this->syncRelationships($person, is_array(data_get($payload, 'relationships')) ? data_get($payload, 'relationships') : []);
            $this->syncTrivia($person, is_array(data_get($payload, 'trivia')) ? data_get($payload, 'trivia') : []);

            return $person->fresh() ?? $person;
        });

        return $person;
    }

    /**
     * @param  array<string, mixed>  $detailsPayload
     */
    private function upsertPerson(array $detailsPayload): NameBasic
    {
        $imdbId = $this->requiredImdbId($detailsPayload);

        $person = NameBasic::query()
            ->where('nconst', $imdbId)
            ->orWhere('imdb_id', $imdbId)
            ->first() ?? new NameBasic;

        $displayName = $this->nullableString(data_get($detailsPayload, 'displayName')) ?? $imdbId;
        $primaryProfessions = $this->normalizeStringList(data_get($detailsPayload, 'primaryProfessions'));
        $alternativeNames = $this->alternativeNames($detailsPayload);

        $person->fill([
            'nconst' => $imdbId,
            'imdb_id' => $imdbId,
            'displayName' => $displayName,
            'primaryname' => $this->nullableString(data_get($detailsPayload, 'primaryName')) ?? $displayName,
            'birthyear' => $this->nullableInt(data_get($detailsPayload, 'birthDate.year')) ?? $person->birthyear,
            'deathyear' => $this->nullableInt(data_get($detailsPayload, 'deathDate.year')) ?? $person->deathyear,
            'primaryprofession' => $primaryProfessions === [] ? $person->primaryprofession : implode(',', $primaryProfessions),
            'knownfortitles' => $person->knownfortitles,
            'alternativeNames' => $alternativeNames === [] ? $person->alternativeNames : $this->jsonEncode($alternativeNames),
            'biography' => $this->nullableString(data_get($detailsPayload, 'biography')) ?? $person->biography,
            'birthDate_day' => $this->nullableInt(data_get($detailsPayload, 'birthDate.day')) ?? $person->birthDate_day,
            'birthDate_month' => $this->nullableInt(data_get($detailsPayload, 'birthDate.month')) ?? $person->birthDate_month,
            'birthDate_year' => $this->nullableInt(data_get($detailsPayload, 'birthDate.year')) ?? $person->birthDate_year,
            'birthLocation' => $this->nullableString(data_get($detailsPayload, 'birthLocation')) ?? $person->birthLocation,
            'birthName' => $this->nullableString(data_get($detailsPayload, 'birthName')) ?? $person->birthName,
            'deathDate_day' => $this->nullableInt(data_get($detailsPayload, 'deathDate.day')) ?? $person->deathDate_day,
            'deathDate_month' => $this->nullableInt(data_get($detailsPayload, 'deathDate.month')) ?? $person->deathDate_month,
            'deathDate_year' => $this->nullableInt(data_get($detailsPayload, 'deathDate.year')) ?? $person->deathDate_year,
            'deathLocation' => $this->nullableString(data_get($detailsPayload, 'deathLocation')) ?? $person->deathLocation,
            'deathReason' => $this->nullableString(data_get($detailsPayload, 'deathReason')) ?? $person->deathReason,
            'heightCm' => $this->nullableInt(data_get($detailsPayload, 'heightCm')) ?? $person->heightCm,
            'primaryImage_url' => $this->nullableString(data_get($detailsPayload, 'primaryImage.url')) ?? $person->primaryImage_url,
            'primaryImage_width' => $this->nullableInt(data_get($detailsPayload, 'primaryImage.width')) ?? $person->primaryImage_width,
            'primaryImage_height' => $this->nullableInt(data_get($detailsPayload, 'primaryImage.height')) ?? $person->primaryImage_height,
            'primaryProfessions' => $primaryProfessions === [] ? $person->primaryProfessions : $this->jsonEncode($primaryProfessions),
        ]);
        $person->save();

        if (is_array(data_get($detailsPayload, 'meterRanking'))) {
            NameBasicMeterRanking::query()->updateOrCreate(
                ['name_basic_id' => $person->getKey()],
                [
                    'current_rank' => $this->nullableInt(data_get($detailsPayload, 'meterRanking.currentRank'))
                        ?? $this->nullableInt(data_get($detailsPayload, 'meterRanking.rank')),
                    'change_direction' => $this->nullableString(data_get($detailsPayload, 'meterRanking.changeDirection')),
                    'difference' => $this->nullableInt(data_get($detailsPayload, 'meterRanking.difference')),
                ],
            );
        }

        return $person;
    }

    /**
     * @param  array<string, mixed>  $detailsPayload
     */
    private function requiredImdbId(array $detailsPayload): string
    {
        $imdbId = $this->nullableString(data_get($detailsPayload, 'id'));

        if ($imdbId === null) {
            throw new RuntimeException('The IMDb name details payload is missing an id.');
        }

        return $imdbId;
    }

    /**
     * @param  list<string>  $alternativeNames
     */
    private function syncAlternativeNames(NameBasic $person, array $alternativeNames): void
    {
        NameBasicAlternativeName::query()->where('name_basic_id', $person->getKey())->delete();

        foreach ($alternativeNames as $index => $alternativeName) {
            NameBasicAlternativeName::query()->create([
                'name_basic_id' => $person->getKey(),
                'alternative_name' => $alternativeName,
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * @param  list<string>  $professionNames
     */
    private function syncProfessions(NameBasic $person, array $professionNames): void
    {
        NameBasicProfession::query()->where('name_basic_id', $person->getKey())->delete();

        foreach ($professionNames as $index => $professionName) {
            $profession = Profession::query()->firstOrCreate(['name' => $professionName]);

            NameBasicProfession::query()->create([
                'name_basic_id' => $person->getKey(),
                'profession_id' => $profession->getKey(),
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $primaryImagePayload
     */
    private function syncPrimaryImage(NameBasic $person, array $primaryImagePayload): void
    {
        $url = $this->nullableString(data_get($primaryImagePayload, 'url'));

        if ($url === null) {
            return;
        }

        NameBasicPrimaryImage::query()->updateOrCreate(
            ['name_basic_id' => $person->getKey()],
            [
                'url' => $url,
                'width' => $this->nullableInt(data_get($primaryImagePayload, 'width')),
                'height' => $this->nullableInt(data_get($primaryImagePayload, 'height')),
                'type' => $this->nullableString(data_get($primaryImagePayload, 'type')) ?? 'primary',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $imagesPayload
     */
    private function syncImages(NameBasic $person, array $imagesPayload): void
    {
        NameImage::query()->where('name_basic_id', $person->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($imagesPayload, 'images')) as $index => $imagePayload) {
            $url = $this->nullableString(data_get($imagePayload, 'url'));

            if ($url === null) {
                continue;
            }

            NameImage::query()->create([
                'name_basic_id' => $person->getKey(),
                'position' => $index + 1,
                'url' => $url,
                'width' => $this->nullableInt(data_get($imagePayload, 'width')),
                'height' => $this->nullableInt(data_get($imagePayload, 'height')),
                'type' => $this->nullableString(data_get($imagePayload, 'type')),
            ]);
        }

        if ($this->nullableInt(data_get($imagesPayload, 'totalCount')) !== null) {
            NameImageSummary::query()->updateOrCreate(
                ['name_basic_id' => $person->getKey()],
                [
                    'total_count' => $this->nullableInt(data_get($imagesPayload, 'totalCount')),
                    'next_page_token' => $this->nullableString(data_get($imagesPayload, 'nextPageToken')),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $filmographyPayload
     */
    private function syncFilmography(NameBasic $person, array $filmographyPayload): void
    {
        $creditRows = [];
        $creditCharacters = [];
        $movieIds = [];

        foreach ($this->collapseFilmographyCredits(
            $this->normalizeObjectList(data_get($filmographyPayload, 'credits')),
        ) as $creditPayload) {
            $movie = $this->upsertMovieStub(data_get($creditPayload, 'title'));

            if (! $movie instanceof Movie) {
                continue;
            }

            $category = $this->nullableString(data_get($creditPayload, 'category'));
            $creditKey = $this->nameCreditKey($person->getKey(), $movie->getKey(), $category);

            $creditRows[$creditKey] = [
                'name_basic_id' => $person->getKey(),
                'movie_id' => $movie->getKey(),
                'category' => $category,
                'episode_count' => $this->nullableInt(data_get($creditPayload, 'episodeCount')),
                'position' => $this->nullableInt(data_get($creditPayload, 'position')),
            ];
            $creditCharacters[$creditKey] = $this->normalizeStringList(data_get($creditPayload, 'characters'));
            $movieIds[] = (int) $movie->getKey();
        }

        if ($creditRows !== []) {
            NameCredit::query()->upsert(
                array_values($creditRows),
                ['name_basic_id', 'movie_id', 'category'],
                ['episode_count', 'position'],
            );

            $persistedCredits = NameCredit::query()
                ->where('name_basic_id', $person->getKey())
                ->whereIn('movie_id', array_values(array_unique($movieIds)))
                ->get()
                ->keyBy(fn (NameCredit $credit): string => $this->nameCreditKey(
                    (int) $credit->name_basic_id,
                    (int) $credit->movie_id,
                    $credit->category,
                ));

            foreach ($creditCharacters as $creditKey => $characters) {
                $credit = $persistedCredits->get($creditKey);

                if (! $credit instanceof NameCredit) {
                    continue;
                }

                $this->syncNameCreditCharacters($credit, $characters);
            }
        }

        if ($this->nullableInt(data_get($filmographyPayload, 'totalCount')) !== null) {
            NameCreditSummary::query()->updateOrCreate(
                ['name_basic_id' => $person->getKey()],
                [
                    'total_count' => $this->nullableInt(data_get($filmographyPayload, 'totalCount')),
                    'next_page_token' => $this->nullableString(data_get($filmographyPayload, 'nextPageToken')),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $relationshipsPayload
     */
    private function syncRelationships(NameBasic $person, array $relationshipsPayload): void
    {
        $existingIds = NameRelationship::query()->where('name_basic_id', $person->getKey())->pluck('id');

        if ($existingIds->isNotEmpty()) {
            NameRelationshipAttribute::query()->whereIn('name_relationship_id', $existingIds)->delete();
        }

        NameRelationship::query()->where('name_basic_id', $person->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($relationshipsPayload, 'relationships')) as $index => $relationshipPayload) {
            $related = $this->upsertPerson([
                'id' => $this->nullableString(data_get($relationshipPayload, 'name.id')),
                'displayName' => $this->nullableString(data_get($relationshipPayload, 'name.displayName')),
            ]);
            $relationTypeName = $this->nullableString(data_get($relationshipPayload, 'relationType'));

            if ($relationTypeName === null) {
                continue;
            }

            $relationType = NameRelationType::query()->firstOrCreate(['name' => $relationTypeName]);
            $relationship = NameRelationship::query()->create([
                'name_basic_id' => $person->getKey(),
                'related_name_basic_id' => $related->getKey(),
                'name_relation_type_id' => $relationType->getKey(),
                'position' => $index + 1,
            ]);

            foreach ($this->normalizeStringList(data_get($relationshipPayload, 'attributes')) as $attributeIndex => $attributeName) {
                $attribute = RelationAttribute::query()->firstOrCreate(['name' => $attributeName]);

                NameRelationshipAttribute::query()->create([
                    'name_relationship_id' => $relationship->getKey(),
                    'relation_attribute_id' => $attribute->getKey(),
                    'position' => $attributeIndex + 1,
                ]);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $knownForTitles
     * @param  list<array<string, mixed>>  $filmographyCredits
     */
    private function syncKnownForTitles(NameBasic $person, array $knownForTitles, array $filmographyCredits): void
    {
        NameBasicKnownForTitle::query()->where('name_basic_id', $person->getKey())->delete();

        $position = 1;
        $seenMovieIds = [];
        $candidatePayloads = $knownForTitles !== []
            ? $knownForTitles
            : array_values(array_filter(array_map(
                fn (array $creditPayload): mixed => data_get($creditPayload, 'title'),
                $filmographyCredits,
            ), fn (mixed $titlePayload): bool => is_array($titlePayload)));

        foreach ($candidatePayloads as $candidatePayload) {
            $movie = $this->upsertMovieStub($candidatePayload);

            if (! $movie instanceof Movie) {
                continue;
            }

            $movieId = (int) $movie->getKey();

            if (isset($seenMovieIds[$movieId])) {
                continue;
            }

            $seenMovieIds[$movieId] = true;

            NameBasicKnownForTitle::query()->create([
                'name_basic_id' => $person->getKey(),
                'title_basic_id' => $movieId,
                'position' => $position,
            ]);

            $position++;

            if ($position > 4) {
                break;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $triviaPayload
     */
    private function syncTrivia(NameBasic $person, array $triviaPayload): void
    {
        NameTriviaEntry::query()->where('name_basic_id', $person->getKey())->delete();

        foreach ($this->normalizeObjectList(data_get($triviaPayload, 'triviaEntries')) as $index => $entryPayload) {
            $entryId = $this->nullableString(data_get($entryPayload, 'id'));

            if ($entryId === null) {
                continue;
            }

            NameTriviaEntry::query()->create([
                'imdb_id' => $entryId,
                'name_basic_id' => $person->getKey(),
                'text' => $this->nullableString(data_get($entryPayload, 'text')),
                'interest_count' => $this->nullableInt(data_get($entryPayload, 'interestCount')),
                'vote_count' => $this->nullableInt(data_get($entryPayload, 'voteCount')),
                'position' => $index + 1,
            ]);
        }

        if ($this->nullableInt(data_get($triviaPayload, 'totalCount')) !== null) {
            NameTriviaSummary::query()->updateOrCreate(
                ['name_basic_id' => $person->getKey()],
                [
                    'total_count' => $this->nullableInt(data_get($triviaPayload, 'totalCount')),
                    'next_page_token' => $this->nullableString(data_get($triviaPayload, 'nextPageToken')),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>|mixed  $payload
     */
    private function upsertMovieStub(mixed $payload): ?Movie
    {
        if (! is_array($payload)) {
            return null;
        }

        $imdbId = $this->nullableString(data_get($payload, 'id'));

        if ($imdbId === null) {
            return null;
        }

        $movie = Movie::query()
            ->where('tconst', $imdbId)
            ->orWhere('imdb_id', $imdbId)
            ->first() ?? new Movie;

        $typeName = $this->nullableString(data_get($payload, 'type'));
        $titleType = $typeName !== null
            ? TitleType::query()->firstOrCreate(['name' => $typeName])
            : null;
        $genres = $this->normalizeStringList(data_get($payload, 'genres'));

        $movie->fill([
            'tconst' => $imdbId,
            'imdb_id' => $imdbId,
            'titletype' => $typeName,
            'primarytitle' => $this->nullableString(data_get($payload, 'primaryTitle')) ?? $movie->primarytitle,
            'originaltitle' => $this->nullableString(data_get($payload, 'originalTitle'))
                ?? $this->nullableString(data_get($payload, 'primaryTitle'))
                ?? $movie->originaltitle,
            'startyear' => $this->nullableInt(data_get($payload, 'startYear')) ?? $movie->startyear,
            'runtimeSeconds' => $this->nullableInt(data_get($payload, 'runtimeSeconds')) ?? $movie->runtimeSeconds,
            'runtimeminutes' => $this->runtimeMinutes($this->nullableInt(data_get($payload, 'runtimeSeconds'))) ?? $movie->runtimeminutes,
            'genres' => $genres === [] ? $movie->genres : implode(',', $genres),
            'title_type_id' => $titleType?->getKey() ?? $movie->title_type_id,
        ]);
        $movie->save();

        if ($genres !== []) {
            MovieGenre::query()->where('movie_id', $movie->getKey())->delete();

            foreach ($genres as $index => $genreName) {
                $genre = Genre::query()->firstOrCreate(['name' => $genreName]);

                MovieGenre::query()->create([
                    'movie_id' => $movie->getKey(),
                    'genre_id' => $genre->getKey(),
                    'position' => $index + 1,
                ]);
            }
        }

        $this->syncMovieRating($movie, is_array(data_get($payload, 'rating')) ? data_get($payload, 'rating') : []);
        $this->syncMovieMetacritic($movie, is_array(data_get($payload, 'metacritic')) ? data_get($payload, 'metacritic') : []);

        return $movie;
    }

    /**
     * @param  array<string, mixed>  $ratingPayload
     */
    private function syncMovieRating(Movie $movie, array $ratingPayload): void
    {
        if ($ratingPayload === []) {
            return;
        }

        MovieRating::query()->updateOrCreate(
            ['movie_id' => $movie->getKey()],
            array_filter([
                'aggregate_rating' => $this->nullableFloat(data_get($ratingPayload, 'aggregateRating')),
                'vote_count' => $this->nullableInt(data_get($ratingPayload, 'voteCount')),
            ], fn (mixed $value): bool => $value !== null),
        );
    }

    /**
     * @param  array<string, mixed>  $metacriticPayload
     */
    private function syncMovieMetacritic(Movie $movie, array $metacriticPayload): void
    {
        if ($metacriticPayload === []) {
            return;
        }

        $attributes = array_filter([
            'url' => $this->nullableString(data_get($metacriticPayload, 'url')),
            'score' => $this->nullableInt(data_get($metacriticPayload, 'score')),
            'review_count' => $this->nullableInt(data_get($metacriticPayload, 'reviewCount')),
        ], fn (mixed $value): bool => $value !== null);

        if ($attributes === []) {
            return;
        }

        MovieMetacritic::query()->updateOrCreate(
            ['movie_id' => $movie->getKey()],
            $attributes,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $credits
     * @return list<array<string, mixed>>
     */
    private function collapseFilmographyCredits(array $credits): array
    {
        $collapsedCredits = [];

        foreach ($credits as $index => $creditPayload) {
            $titlePayload = data_get($creditPayload, 'title');

            if (! is_array($titlePayload)) {
                continue;
            }

            $titleId = $this->nullableString(data_get($titlePayload, 'id'));

            if ($titleId === null) {
                continue;
            }

            $category = $this->nullableString(data_get($creditPayload, 'category'));
            $creditKey = $this->nameCreditKey('person', $titleId, $category);
            $episodeCount = $this->nullableInt(data_get($creditPayload, 'episodeCount'));
            $position = $index + 1;
            $characters = $this->normalizeStringList(data_get($creditPayload, 'characters'));

            if (! array_key_exists($creditKey, $collapsedCredits)) {
                $collapsedCredits[$creditKey] = [
                    'title' => $titlePayload,
                    'category' => $category,
                    'episodeCount' => $episodeCount,
                    'position' => $position,
                    'characters' => $characters,
                ];

                continue;
            }

            $collapsedCredits[$creditKey]['episodeCount'] = $this->preferLargerInt(
                $collapsedCredits[$creditKey]['episodeCount'],
                $episodeCount,
            );
            $collapsedCredits[$creditKey]['position'] = min(
                $collapsedCredits[$creditKey]['position'],
                $position,
            );
            $collapsedCredits[$creditKey]['characters'] = $this->mergeUniqueStrings(
                $collapsedCredits[$creditKey]['characters'],
                $characters,
            );
        }

        return array_values($collapsedCredits);
    }

    /**
     * @param  list<string>  $characters
     */
    private function syncNameCreditCharacters(NameCredit $credit, array $characters): void
    {
        $persistedPositions = [];

        foreach ($characters as $index => $characterName) {
            $position = $index + 1;

            $character = NameCreditCharacter::query()->firstOrNew([
                'name_credit_id' => $credit->getKey(),
                'position' => $position,
            ]);

            $character->fill([
                'character_name' => $characterName,
            ]);
            $character->save();

            $persistedPositions[] = $position;
        }

        $staleCharacters = NameCreditCharacter::query()->where('name_credit_id', $credit->getKey());

        if ($persistedPositions !== []) {
            $staleCharacters->whereNotIn('position', $persistedPositions);
        }

        $staleCharacters->delete();
    }

    /**
     * @param  list<string>  $existingValues
     * @param  list<string>  $incomingValues
     * @return list<string>
     */
    private function mergeUniqueStrings(array $existingValues, array $incomingValues): array
    {
        return $this->normalizeStringList([
            ...$existingValues,
            ...$incomingValues,
        ]);
    }

    private function preferLargerInt(?int $existingValue, ?int $incomingValue): ?int
    {
        if ($existingValue === null) {
            return $incomingValue;
        }

        if ($incomingValue === null) {
            return $existingValue;
        }

        return max($existingValue, $incomingValue);
    }

    private function nameCreditKey(int|string $nameBasicId, int|string $movieId, ?string $category): string
    {
        return sprintf(
            '%s:%s:%s',
            (string) $nameBasicId,
            (string) $movieId,
            $category ?? '__null__',
        );
    }

    /**
     * @param  array<string, mixed>  $detailsPayload
     * @return list<string>
     */
    private function alternativeNames(array $detailsPayload): array
    {
        return $this->normalizeStringList([
            ...$this->normalizeStringList(data_get($detailsPayload, 'alternativeNames')),
            ...array_filter([$this->nullableString(data_get($detailsPayload, 'birthName'))]),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $value): array
    {
        if (! is_iterable($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return array_values($items);
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_iterable($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }

            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $items[] = $item;
        }

        return array_values(array_unique($items));
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
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function runtimeMinutes(?int $runtimeSeconds): ?int
    {
        if ($runtimeSeconds === null) {
            return null;
        }

        return (int) floor($runtimeSeconds / 60);
    }

    /**
     * @param  list<string>  $values
     */
    private function jsonEncode(array $values): string
    {
        return json_encode(array_values($values), JSON_THROW_ON_ERROR);
    }
}
