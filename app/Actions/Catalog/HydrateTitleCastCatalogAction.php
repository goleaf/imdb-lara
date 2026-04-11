<?php

namespace App\Actions\Catalog;

use App\Actions\Import\DownloadImdbTitlePayloadAction;
use App\Actions\Import\ResolveImdbApiUrlAction;
use App\Models\Credit;
use App\Models\MoviePlot;
use App\Models\MoviePrimaryImage;
use App\Models\NameCreditCharacter;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Profession;
use App\Models\Title;
use App\Models\TitleImage;
use App\Models\TitleStatistic;
use Illuminate\Support\Facades\DB;

class HydrateTitleCastCatalogAction
{
    public function __construct(
        private readonly DownloadImdbTitlePayloadAction $downloadImdbTitlePayloadAction,
        private readonly ResolveImdbApiUrlAction $resolveImdbApiUrlAction,
    ) {}

    public function handle(Title $title): Title
    {
        $imdbId = $this->resolveTitleImdbId($title);

        if ($imdbId === null) {
            return $title;
        }

        $download = $this->downloadImdbTitlePayloadAction->handle(
            $imdbId,
            $this->storageDirectory(),
            $this->titleUrlTemplate(),
            true,
        );

        /** @var array<string, mixed> $payload */
        $payload = $download['payload'];
        /** @var array<string, mixed> $titlePayload */
        $titlePayload = is_array(data_get($payload, 'title')) ? data_get($payload, 'title') : $payload;
        $creditsPayload = $this->normalizeObjectList(data_get($payload, 'credits.credits'));
        $imagesPayload = is_array(data_get($payload, 'images')) ? data_get($payload, 'images') : null;

        DB::connection('imdb_mysql')->transaction(function () use ($title, $titlePayload, $creditsPayload, $imagesPayload): void {
            $this->syncTitle($title, $titlePayload);
            $this->syncTitlePlot($title, $titlePayload);
            $this->syncTitleRating($title, $titlePayload);
            $this->syncPrimaryImage($title, data_get($titlePayload, 'primaryImage'));
            $this->syncTitleImages($title, $this->normalizeObjectList(data_get($imagesPayload, 'images')), $imagesPayload !== null);
            $this->syncCredits($title, $creditsPayload);
        });

        return $title->fresh() ?? $title;
    }

    private function storageDirectory(): string
    {
        return rtrim((string) config('services.imdb.storage_root', storage_path('app/private/imdb-temp')), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'catalog-cast-hydration'
            .DIRECTORY_SEPARATOR.'titles';
    }

    private function titleUrlTemplate(): string
    {
        $template = $this->resolveImdbApiUrlAction->endpoint('title');

        return $template !== '' ? $template : '/titles/{titleId}';
    }

    private function resolveTitleImdbId(Title $title): ?string
    {
        $imdbId = is_string($title->imdb_id) && $title->imdb_id !== ''
            ? $title->imdb_id
            : $title->tconst;

        return is_string($imdbId) && preg_match('/^tt\d+$/', $imdbId) === 1
            ? $imdbId
            : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncTitle(Title $title, array $payload): void
    {
        $runtimeSeconds = $this->nullableInt(data_get($payload, 'runtimeSeconds'));
        $primaryTitle = $this->nullableString(data_get($payload, 'primaryTitle'));
        $originalTitle = $this->nullableString(data_get($payload, 'originalTitle'));
        $existingEndYear = $title->getRawOriginal('endyear');
        $existingRuntimeSeconds = $title->getRawOriginal('runtimeSeconds');
        $existingRuntimeMinutes = $title->getRawOriginal('runtimeminutes');

        $title->fill([
            'tconst' => $this->nullableString(data_get($payload, 'id')) ?? $title->tconst,
            'imdb_id' => $this->nullableString(data_get($payload, 'id')) ?? $title->imdb_id,
            'titletype' => $this->nullableString(data_get($payload, 'type')) ?? $title->titletype,
            'primarytitle' => $primaryTitle ?? $title->primarytitle,
            'originaltitle' => $originalTitle ?? $primaryTitle ?? $title->originaltitle,
            'startyear' => $this->nullableInt(data_get($payload, 'startYear')) ?? $title->startyear,
            'endyear' => $this->nullableInt(data_get($payload, 'endYear')) ?? (is_numeric($existingEndYear) ? (int) $existingEndYear : null),
            'runtimeSeconds' => $runtimeSeconds ?? (is_numeric($existingRuntimeSeconds) ? (int) $existingRuntimeSeconds : null),
            'runtimeminutes' => $runtimeSeconds !== null
                ? (int) floor($runtimeSeconds / 60)
                : (is_numeric($existingRuntimeMinutes) ? (int) $existingRuntimeMinutes : null),
        ]);

        $title->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncTitlePlot(Title $title, array $payload): void
    {
        $plot = $this->nullableString(data_get($payload, 'plot'));

        if ($plot === null) {
            return;
        }

        MoviePlot::query()->updateOrCreate(
            ['movie_id' => $title->getKey()],
            ['plot' => $plot],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncTitleRating(Title $title, array $payload): void
    {
        $aggregateRating = $this->nullableFloat(data_get($payload, 'rating.aggregateRating'));
        $voteCount = $this->nullableInt(data_get($payload, 'rating.voteCount'));

        if ($aggregateRating === null && $voteCount === null) {
            return;
        }

        $statistic = TitleStatistic::query()->firstOrNew([
            'movie_id' => $title->getKey(),
        ]);

        $statistic->fill([
            'aggregate_rating' => $aggregateRating ?? $statistic->aggregate_rating,
            'vote_count' => $voteCount ?? $statistic->vote_count,
            'rating_distribution' => $statistic->rating_distribution ?? TitleStatistic::normalizeRatingDistribution(),
        ]);
        $statistic->save();
    }

    private function syncPrimaryImage(Title $title, mixed $primaryImagePayload): void
    {
        if (! is_array($primaryImagePayload)) {
            return;
        }

        $url = $this->nullableString(data_get($primaryImagePayload, 'url'));

        if ($url === null) {
            return;
        }

        MoviePrimaryImage::query()->updateOrCreate(
            ['movie_id' => $title->getKey()],
            [
                'url' => $url,
                'width' => $this->nullableInt(data_get($primaryImagePayload, 'width')),
                'height' => $this->nullableInt(data_get($primaryImagePayload, 'height')),
                'type' => $this->nullableString(data_get($primaryImagePayload, 'type')) ?? 'primary',
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $images
     */
    private function syncTitleImages(Title $title, array $images, bool $hasFullPayload): void
    {
        $persistedUrls = [];

        foreach ($images as $index => $imagePayload) {
            $url = $this->nullableString(data_get($imagePayload, 'url'));

            if ($url === null) {
                continue;
            }

            $image = TitleImage::query()->firstOrNew([
                'movie_id' => $title->getKey(),
                'url' => $url,
            ]);

            $image->fill([
                'position' => $index + 1,
                'width' => $this->nullableInt(data_get($imagePayload, 'width')),
                'height' => $this->nullableInt(data_get($imagePayload, 'height')),
                'type' => $this->nullableString(data_get($imagePayload, 'type')),
            ]);
            $image->save();

            $persistedUrls[] = $url;
        }

        if (! $hasFullPayload) {
            return;
        }

        $staleImages = TitleImage::query()->where('movie_id', $title->getKey());

        if ($persistedUrls !== []) {
            $staleImages->whereNotIn('url', $persistedUrls);
        }

        $staleImages->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $credits
     */
    private function syncCredits(Title $title, array $credits): void
    {
        $persistedCreditIds = [];
        $creditRows = [];
        $creditCharacters = [];

        foreach ($this->collapseCredits($credits) as $creditPayload) {
            $namePayload = data_get($creditPayload, 'name');

            if (! is_array($namePayload)) {
                continue;
            }

            $category = $this->nullableString(data_get($creditPayload, 'category'));
            $person = $this->upsertPerson($namePayload, $category);
            $creditKey = $this->nameCreditKey($person->getKey(), $title->getKey(), $category);

            $creditRows[$creditKey] = [
                'movie_id' => $title->getKey(),
                'name_basic_id' => $person->getKey(),
                'category' => $category,
                'episode_count' => $this->nullableInt(data_get($creditPayload, 'episodeCount')),
                'position' => $this->nullableInt(data_get($creditPayload, 'position')),
            ];
            $creditCharacters[$creditKey] = $this->normalizeStringList(data_get($creditPayload, 'characters'));
        }

        if ($creditRows !== []) {
            Credit::query()->upsert(
                array_values($creditRows),
                ['name_basic_id', 'movie_id', 'category'],
                ['episode_count', 'position'],
            );
        }

        $persistedCredits = Credit::query()
            ->where('movie_id', $title->getKey())
            ->get()
            ->keyBy(fn (Credit $credit): string => $this->nameCreditKey(
                (int) $credit->name_basic_id,
                (int) $credit->movie_id,
                $credit->category,
            ));

        foreach ($creditCharacters as $creditKey => $characters) {
            $credit = $persistedCredits->get($creditKey);

            if (! $credit instanceof Credit) {
                continue;
            }

            $persistedCreditIds[] = (int) $credit->getKey();
            $this->syncCreditCharacters($credit, $characters);
        }

        $staleCredits = Credit::query()->where('movie_id', $title->getKey());

        if ($persistedCreditIds !== []) {
            $staleCredits->whereNotIn('id', $persistedCreditIds);
        }

        $staleCreditIds = $staleCredits->pluck('id');

        if ($staleCreditIds->isNotEmpty()) {
            NameCreditCharacter::query()->whereIn('name_credit_id', $staleCreditIds)->delete();
            Credit::query()->whereIn('id', $staleCreditIds)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertPerson(array $payload, ?string $fallbackProfession): Person
    {
        $imdbId = $this->nullableString(data_get($payload, 'id'));

        if ($imdbId === null) {
            throw new \RuntimeException('IMDb credit name payload is missing an id.');
        }

        $person = Person::query()
            ->where('nconst', $imdbId)
            ->orWhere('imdb_id', $imdbId)
            ->first() ?? new Person;

        $displayName = $this->nullableString(data_get($payload, 'displayName'))
            ?? $this->nullableString(data_get($payload, 'name'))
            ?? $person->displayName
            ?? $person->primaryname
            ?? $imdbId;
        $primaryProfessions = $this->normalizeStringList(data_get($payload, 'primaryProfessions'));

        if ($primaryProfessions === [] && $fallbackProfession !== null) {
            $primaryProfessions = [$fallbackProfession];
        }

        $person->fill([
            'nconst' => $imdbId,
            'imdb_id' => $imdbId,
            'displayName' => $displayName,
            'primaryname' => $this->nullableString(data_get($payload, 'primaryName')) ?? $displayName,
            'alternativeNames' => $this->normalizeStringList(data_get($payload, 'alternativeNames')) !== []
                ? json_encode($this->normalizeStringList(data_get($payload, 'alternativeNames')), JSON_THROW_ON_ERROR)
                : $person->getRawOriginal('alternativeNames'),
            'biography' => $this->nullableString(data_get($payload, 'biography')) ?? $person->biography,
            'primaryprofession' => $primaryProfessions !== []
                ? implode(',', $primaryProfessions)
                : $person->primaryprofession,
            'primaryProfessions' => $primaryProfessions !== []
                ? json_encode($primaryProfessions, JSON_THROW_ON_ERROR)
                : $person->getRawOriginal('primaryProfessions'),
            'birthLocation' => $this->nullableString(data_get($payload, 'birthLocation')) ?? $person->birthLocation,
            'deathLocation' => $this->nullableString(data_get($payload, 'deathLocation')) ?? $person->deathLocation,
            'primaryImage_url' => $this->nullableString(data_get($payload, 'primaryImage.url')) ?? $person->primaryImage_url,
            'primaryImage_width' => $this->nullableInt(data_get($payload, 'primaryImage.width')) ?? $person->primaryImage_width,
            'primaryImage_height' => $this->nullableInt(data_get($payload, 'primaryImage.height')) ?? $person->primaryImage_height,
        ]);
        $person->save();

        $this->syncPersonProfessions($person, $primaryProfessions);

        return $person;
    }

    /**
     * @param  list<string>  $professionNames
     */
    private function syncPersonProfessions(Person $person, array $professionNames): void
    {
        foreach ($professionNames as $index => $professionName) {
            $profession = Profession::query()->firstOrCreate([
                'name' => $professionName,
            ]);

            $personProfession = PersonProfession::query()->firstOrNew([
                'name_basic_id' => $person->getKey(),
                'profession_id' => $profession->getKey(),
            ]);

            $personProfession->fill([
                'position' => $index + 1,
            ]);
            $personProfession->save();
        }
    }

    /**
     * @param  list<string>  $characters
     */
    private function syncCreditCharacters(Credit $credit, array $characters): void
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
     * @param  list<array<string, mixed>>  $credits
     * @return list<array<string, mixed>>
     */
    private function collapseCredits(array $credits): array
    {
        $collapsedCredits = [];

        foreach ($credits as $index => $creditPayload) {
            $namePayload = data_get($creditPayload, 'name');

            if (! is_array($namePayload)) {
                continue;
            }

            $imdbId = $this->nullableString(data_get($namePayload, 'id'));

            if ($imdbId === null) {
                continue;
            }

            $category = $this->nullableString(data_get($creditPayload, 'category'));
            $creditKey = $this->nameCreditKey($imdbId, 'title', $category);
            $episodeCount = $this->nullableInt(data_get($creditPayload, 'episodeCount'));
            $position = $index + 1;
            $characters = $this->normalizeStringList(data_get($creditPayload, 'characters'));

            if (! array_key_exists($creditKey, $collapsedCredits)) {
                $collapsedCredits[$creditKey] = [
                    'name' => $namePayload,
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
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $value): array
    {
        if (! is_iterable($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_iterable($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): string => trim($item))
            ->unique()
            ->values()
            ->all();
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
}
