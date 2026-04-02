<?php

namespace App\Actions\Import;

use App\Enums\CompanyKind;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Models\Award;
use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Company;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\ImdbTitleImport;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\Models\TitleTranslation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

class ImportImdbTitlePayloadAction
{
    private bool $fillMissingOnly = false;

    public function __construct(
        private readonly BuildCompactImdbPayloadAction $buildCompactImdbPayloadAction,
        private readonly WriteImdbEndpointImportReportAction $writeImdbEndpointImportReportAction,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $storagePath, array $options = []): Title
    {
        $previousFillMissingOnly = $this->fillMissingOnly;
        $this->fillMissingOnly = (bool) ($options['fill_missing_only'] ?? false);

        try {
            $bundle = $this->normalizeBundle($payload);
            $imdbId = $this->requiredImdbId($bundle['title']);
            $artifactDirectory = $this->resolveArtifactDirectory($storagePath, $imdbId);

            return DB::transaction(function () use ($artifactDirectory, $bundle, $imdbId, $payload, $storagePath): Title {
                $beforeSnapshot = $this->snapshotTitleImportState($imdbId);
                $title = $this->upsertTitle($imdbId, $bundle, $payload);

                $this->syncGenres($title, $this->normalizeStringList(data_get($bundle['title'], 'genres')));
                $this->syncTitleTranslations($title, $bundle['akas']);
                $episodesCount = $this->syncSeriesStructure($title, $bundle);
                $this->upsertTitleStatistic($title, $bundle, $episodesCount);
                $this->syncTitleMediaAssets($title, $bundle);
                $this->syncCredits($title, $bundle);
                $this->syncCompanies($title, $bundle['companyCredits']);
                $this->syncAwards($title, $bundle['awardNominations'], $bundle['names']);
                $this->upsertImportRecord($imdbId, $payload, $storagePath, $bundle['source_url']);
                $afterSnapshot = $this->snapshotTitleImportState($imdbId);
                $this->writeEndpointReports($artifactDirectory, $imdbId, $payload, $bundle, $beforeSnapshot, $afterSnapshot);

                return $title->fresh([
                    'awardNominations.awardEvent',
                    'companies',
                    'credits.person',
                    'genres',
                    'mediaAssets',
                    'seasons.episodes.title',
                    'statistic',
                    'translations',
                ]);
            });
        } finally {
            $this->fillMissingOnly = $previousFillMissingOnly;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     akas: list<array<string, mixed>>,
     *     awardNominations: list<array<string, mixed>>,
     *     awardStats: array<string, mixed>|null,
     *     boxOffice: array<string, mixed>|null,
     *     certificates: list<array<string, mixed>>,
     *     companyCredits: list<array<string, mixed>>,
     *     credits: list<array<string, mixed>>,
     *     episodes: list<array<string, mixed>>,
     *     has_full_credits: bool,
     *     has_full_episodes: bool,
     *     has_full_images: bool,
     *     has_full_seasons: bool,
     *     has_full_videos: bool,
     *     images: list<array<string, mixed>>,
     *     is_bundle: bool,
     *     names: array<string, array<string, mixed>>,
     *     parentsGuide: array<string, mixed>|null,
     *     releaseDates: list<array<string, mixed>>,
     *     seasons: list<array<string, mixed>>,
     *     source_url: string,
     *     title: array<string, mixed>,
     *     videos: list<array<string, mixed>>
     * }
     */
    private function normalizeBundle(array $payload): array
    {
        $isBundle = $this->hasBundlePayload($payload);
        $titlePayload = $isBundle ? data_get($payload, 'title') : $payload;

        if (! is_array($titlePayload)) {
            throw new RuntimeException('The import payload does not contain a valid title object.');
        }

        $imdbId = $this->requiredImdbId($titlePayload);

        return [
            'is_bundle' => $isBundle,
            'source_url' => $this->nullableString(data_get($payload, 'sourceUrl')) ?? sprintf('https://api.imdbapi.dev/titles/%s', $imdbId),
            'title' => $titlePayload,
            'credits' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'credits.credits')) : [],
            'has_full_credits' => $isBundle && is_array(data_get($payload, 'credits')),
            'releaseDates' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'releaseDates.releaseDates')) : [],
            'akas' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'akas.akas')) : [],
            'seasons' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'seasons.seasons')) : [],
            'has_full_seasons' => $isBundle && is_array(data_get($payload, 'seasons')),
            'episodes' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'episodes.episodes')) : [],
            'has_full_episodes' => $isBundle && is_array(data_get($payload, 'episodes')),
            'images' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'images.images')) : [],
            'has_full_images' => $isBundle && is_array(data_get($payload, 'images')),
            'videos' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'videos.videos')) : [],
            'has_full_videos' => $isBundle && is_array(data_get($payload, 'videos')),
            'awardNominations' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'awardNominations.awardNominations')) : [],
            'awardStats' => is_array(data_get($payload, 'awardNominations.stats')) ? data_get($payload, 'awardNominations.stats') : null,
            'parentsGuide' => is_array(data_get($payload, 'parentsGuide')) ? data_get($payload, 'parentsGuide') : null,
            'certificates' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'certificates.certificates')) : [],
            'companyCredits' => $isBundle ? $this->normalizeObjectList(data_get($payload, 'companyCredits.companyCredits')) : [],
            'boxOffice' => is_array(data_get($payload, 'boxOffice')) ? data_get($payload, 'boxOffice') : null,
            'names' => $isBundle ? $this->normalizeNameBundles(data_get($payload, 'names')) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @param  array<string, mixed>  $rawPayload
     */
    private function upsertTitle(string $imdbId, array $bundle, array $rawPayload): Title
    {
        $titlePayload = $bundle['title'];
        $titleName = $this->requiredNonEmptyString($titlePayload, 'primaryTitle');
        $originalName = $this->nullableString(data_get($titlePayload, 'originalTitle')) ?? $titleName;
        $genres = $this->normalizeStringList(data_get($titlePayload, 'genres'));
        $interests = $this->normalizeInterests(data_get($titlePayload, 'interests'));
        $originCountries = $this->normalizeNamedCodes(data_get($titlePayload, 'originCountries'));
        $spokenLanguages = $this->normalizeNamedCodes(data_get($titlePayload, 'spokenLanguages'));
        $searchKeywords = $this->buildSearchKeywords(
            $genres,
            $interests,
            $originCountries,
            $spokenLanguages,
            $this->resolvePrimaryAkas($bundle['akas']),
        );

        $title = Title::query()->withTrashed()->firstOrNew([
            'imdb_id' => $imdbId,
        ]);

        $releaseYear = $this->nullableInt(data_get($titlePayload, 'startYear'))
            ?? $this->nullableInt(data_get($titlePayload, 'releaseDate.year'));
        $runtimeSeconds = $this->nullableInt(data_get($titlePayload, 'runtimeSeconds'));
        $runtimeMinutes = $this->runtimeMinutes($runtimeSeconds);
        $popularityRank = $this->nullableInt(data_get($titlePayload, 'meterRanking.currentRank'))
            ?? $this->nullableInt(data_get($titlePayload, 'meterRanking.rank'));
        $compactPayload = $this->buildCompactImdbPayloadAction->forTitle($rawPayload);

        $title->fill([
            'imdb_id' => $imdbId,
            'name' => $this->preferStringValue($title->name, $titleName),
            'original_name' => $this->preferStringValue($title->original_name, $originalName),
            'sort_title' => $this->preferStringValue($title->sort_title, $originalName),
            'title_type' => $this->preferStringValue($title->title_type?->value ?? $title->getRawOriginal('title_type'), $this->mapTitleType((string) data_get($titlePayload, 'type'))->value),
            'imdb_type' => $this->preferStringValue($title->imdb_type, $this->nullableString(data_get($titlePayload, 'type'))),
            'release_year' => $this->preferNumericValue($title->release_year, $releaseYear),
            'end_year' => $this->preferNumericValue($title->end_year, $this->nullableInt(data_get($titlePayload, 'endYear'))),
            'release_date' => $this->preferNullableValue(
                optional($title->release_date)?->toDateString(),
                $this->resolveTitleReleaseDate($bundle),
            ),
            'runtime_minutes' => $this->preferNumericValue($title->runtime_minutes, $runtimeMinutes),
            'runtime_seconds' => $this->preferNumericValue($title->runtime_seconds, $runtimeSeconds),
            'age_rating' => $this->preferStringValue($title->age_rating, $this->resolveAgeRating($bundle['certificates'])),
            'plot_outline' => $this->preferStringValue($title->plot_outline, $this->nullableString(data_get($titlePayload, 'plot'))),
            'synopsis' => $this->preferStringValue($title->synopsis, $this->nullableString(data_get($titlePayload, 'synopsis'))),
            'tagline' => $this->preferStringValue($title->tagline, $this->nullableString(data_get($titlePayload, 'tagline'))),
            'origin_country' => $this->preferStringValue($title->origin_country, data_get($originCountries, '0.code')),
            'original_language' => $this->preferStringValue($title->original_language, data_get($spokenLanguages, '0.code')),
            'popularity_rank' => $this->preferNumericValue($title->popularity_rank, $popularityRank),
            'search_keywords' => $this->preferStringValue($title->search_keywords, $searchKeywords),
            'imdb_genres' => is_iterable(data_get($titlePayload, 'genres'))
                ? $this->preferArrayValue($title->imdb_genres, $genres)
                : ($title->imdb_genres ?? []),
            'imdb_interests' => is_iterable(data_get($titlePayload, 'interests'))
                ? $this->preferArrayValue($title->imdb_interests, $interests)
                : ($title->imdb_interests ?? []),
            'imdb_origin_countries' => is_iterable(data_get($titlePayload, 'originCountries'))
                ? $this->preferArrayValue($title->imdb_origin_countries, $originCountries)
                : ($title->imdb_origin_countries ?? []),
            'imdb_spoken_languages' => is_iterable(data_get($titlePayload, 'spokenLanguages'))
                ? $this->preferArrayValue($title->imdb_spoken_languages, $spokenLanguages)
                : ($title->imdb_spoken_languages ?? []),
            'imdb_payload' => $this->mergeCompactPayload($title->imdb_payload, $compactPayload),
            'is_published' => true,
        ]);
        $title->save();

        if ($title->trashed()) {
            $title->restore();
        }

        return $title;
    }

    /**
     * @param  list<string>  $genres
     */
    private function syncGenres(Title $title, array $genres): void
    {
        $genreIds = collect($genres)
            ->map(fn (string $genreName): int => $this->firstOrCreateGenre($genreName)->id)
            ->all();

        if ($this->fillMissingOnly) {
            $title->genres()->syncWithoutDetaching($genreIds);

            return;
        }

        $title->genres()->sync($genreIds);
    }

    /**
     * @param  list<array<string, mixed>>  $akas
     */
    private function syncTitleTranslations(Title $title, array $akas): void
    {
        collect($akas)
            ->map(function (array $aka): ?array {
                $locale = $this->buildAkaLocale($aka);
                $localizedTitle = $this->nullableString(data_get($aka, 'text'));

                if ($locale === null || $localizedTitle === null) {
                    return null;
                }

                return [
                    'locale' => $locale,
                    'localized_title' => $localizedTitle,
                    'meta_description' => $this->translationMetaDescription($aka),
                ];
            })
            ->filter()
            ->unique(fn (array $translation): string => $translation['locale'])
            ->values()
            ->each(function (array $translation) use ($title): void {
                $titleTranslation = TitleTranslation::query()->firstOrNew([
                    'title_id' => $title->id,
                    'locale' => $translation['locale'],
                ]);

                $titleTranslation->fill([
                    'localized_title' => $this->preferStringValue($titleTranslation->localized_title, $translation['localized_title']),
                    'localized_slug' => null,
                    'localized_plot_outline' => null,
                    'localized_synopsis' => null,
                    'localized_tagline' => null,
                    'meta_title' => $this->preferStringValue($titleTranslation->meta_title, $translation['localized_title']),
                    'meta_description' => $this->preferStringValue($titleTranslation->meta_description, $translation['meta_description']),
                ]);
                $titleTranslation->save();
            });
    }

    /**
     * @param  array<string, mixed>  $bundle
     */
    private function upsertTitleStatistic(Title $title, array $bundle, int $episodesCount): void
    {
        $rating = data_get($bundle['title'], 'rating');
        $metacritic = data_get($bundle['title'], 'metacritic');
        $titleStatistic = TitleStatistic::query()->firstOrNew([
            'title_id' => $title->id,
        ]);

        if ($titleStatistic->rating_distribution === null) {
            $titleStatistic->rating_distribution = TitleStatistic::normalizeRatingDistribution();
        }

        $titleStatistic->fill([
            'rating_count' => $this->preferNumericValue($titleStatistic->rating_count, $this->nullableInt(data_get($rating, 'voteCount'))) ?? 0,
            'average_rating' => $this->preferDecimalValue($titleStatistic->average_rating, $this->nullableFloat(data_get($rating, 'aggregateRating'))) ?? 0,
            'metacritic_score' => $this->preferNumericValue($titleStatistic->metacritic_score, $this->nullableInt(data_get($metacritic, 'score'))),
            'metacritic_review_count' => $this->preferNumericValue($titleStatistic->metacritic_review_count, $this->nullableInt(data_get($metacritic, 'reviewCount'))),
            'episodes_count' => $this->preferNumericValue($titleStatistic->episodes_count, $episodesCount > 0 ? $episodesCount : null) ?? 0,
            'awards_nominated_count' => $this->preferNumericValue($titleStatistic->awards_nominated_count, $this->nullableInt(data_get($bundle['awardStats'], 'nominationCount'))) ?? 0,
            'awards_won_count' => $this->preferNumericValue($titleStatistic->awards_won_count, $this->nullableInt(data_get($bundle['awardStats'], 'winCount'))) ?? 0,
        ]);
        $titleStatistic->save();
    }

    /**
     * @param  array<string, mixed>  $bundle
     */
    private function syncTitleMediaAssets(Title $title, array $bundle): void
    {
        $importedProviderKeys = [];
        $primaryImage = data_get($bundle['title'], 'primaryImage');

        if ($bundle['has_full_images']) {
            foreach ($bundle['images'] as $index => $imagePayload) {
                $url = $this->nullableString(data_get($imagePayload, 'url'));

                if ($url === null) {
                    continue;
                }

                $importedProviderKeys[] = $this->upsertMediaAsset(
                    $this->mapTitleImageKind($imagePayload),
                    $title,
                    hash('sha1', 'title-image:'.$title->imdb_id.':'.$url),
                    $url,
                    $title->name,
                    $this->nullableString(data_get($imagePayload, 'type')),
                    $this->nullableInt(data_get($imagePayload, 'width')),
                    $this->nullableInt(data_get($imagePayload, 'height')),
                    false,
                    $index + 1,
                    metadata: [
                        'source_context' => 'title-image',
                        'image' => $imagePayload,
                    ],
                );
            }
        }

        if (is_array($primaryImage)) {
            $url = $this->nullableString(data_get($primaryImage, 'url'));

            if ($url !== null) {
                $importedProviderKeys[] = $this->upsertMediaAsset(
                    MediaKind::Poster,
                    $title,
                    hash('sha1', 'title-image:'.$title->imdb_id.':'.$url),
                    $url,
                    $title->name.' poster',
                    'primary',
                    $this->nullableInt(data_get($primaryImage, 'width')),
                    $this->nullableInt(data_get($primaryImage, 'height')),
                    true,
                    0,
                    metadata: [
                        'source_context' => 'title-primary-image',
                        'image' => $primaryImage,
                    ],
                );
            }
        }

        if ($bundle['has_full_videos']) {
            foreach ($bundle['videos'] as $index => $videoPayload) {
                $videoId = $this->nullableString(data_get($videoPayload, 'id'));

                if ($videoId === null) {
                    continue;
                }

                $importedProviderKeys[] = $this->upsertMediaAsset(
                    $this->mapVideoKind($this->nullableString(data_get($videoPayload, 'type'))),
                    $title,
                    hash('sha1', 'title-video:'.$title->imdb_id.':'.$videoId),
                    $this->imdbVideoUrl($videoId),
                    $this->nullableString(data_get($videoPayload, 'name')) ?? $title->name,
                    $this->nullableString(data_get($videoPayload, 'description')),
                    $this->nullableInt(data_get($videoPayload, 'width')),
                    $this->nullableInt(data_get($videoPayload, 'height')),
                    false,
                    $index + 1,
                    durationSeconds: $this->nullableInt(data_get($videoPayload, 'runtimeSeconds')),
                    metadata: [
                        'source_context' => 'title-video',
                        'video' => $videoPayload,
                    ],
                );
            }
        }

        if (! $this->fillMissingOnly && ($bundle['has_full_images'] || $bundle['has_full_videos'])) {
            $this->removeStaleImdbMediaAssets($title, $importedProviderKeys);
        }
    }

    /**
     * @param  array<string, mixed>  $bundle
     */
    private function syncSeriesStructure(Title $title, array $bundle): int
    {
        $seasonRecords = [];

        foreach ($bundle['seasons'] as $seasonPayload) {
            $seasonNumber = $this->nullableInt(data_get($seasonPayload, 'season'));

            if ($seasonNumber === null) {
                continue;
            }

            $season = Season::query()->withTrashed()->firstOrNew([
                'series_id' => $title->id,
                'season_number' => $seasonNumber,
            ]);

            $season->fill([
                'name' => $this->preferStringValue($season->name, sprintf('%s Season %d', $title->name, $seasonNumber)),
                'summary' => $season->summary,
                'release_year' => $season->release_year,
            ]);
            $season->save();

            if ($season->trashed()) {
                $season->restore();
            }

            $seasonRecords[$seasonNumber] = $season;
        }

        foreach ($bundle['episodes'] as $episodePayload) {
            $seasonNumber = $this->nullableInt(data_get($episodePayload, 'season'));

            if ($seasonNumber !== null && ! array_key_exists($seasonNumber, $seasonRecords)) {
                $season = Season::query()->withTrashed()->firstOrNew([
                    'series_id' => $title->id,
                    'season_number' => $seasonNumber,
                ]);

                $season->fill([
                    'name' => $this->preferStringValue($season->name, sprintf('%s Season %d', $title->name, $seasonNumber)),
                    'summary' => $season->summary,
                    'release_year' => $this->preferNumericValue(
                        $season->release_year,
                        $this->nullableInt(data_get($episodePayload, 'releaseDate.year')) ?? $title->release_year,
                    ),
                ]);
                $season->save();

                if ($season->trashed()) {
                    $season->restore();
                }

                $seasonRecords[$seasonNumber] = $season;
            }

            $episodeTitle = $this->upsertEpisodeTitle($title, $episodePayload);
            $episode = Episode::query()->withTrashed()->firstOrNew([
                'title_id' => $episodeTitle->id,
            ]);

            $episode->fill([
                'series_id' => $title->id,
                'season_id' => $this->preferNumericValue(
                    $episode->season_id,
                    $seasonNumber !== null ? data_get($seasonRecords, $seasonNumber)?->id : null,
                ),
                'season_number' => $this->preferNumericValue($episode->season_number, $seasonNumber),
                'episode_number' => $this->preferNumericValue(
                    $episode->episode_number,
                    $this->nullableInt(data_get($episodePayload, 'episodeNumber')),
                ),
                'absolute_number' => null,
                'production_code' => null,
                'aired_at' => $this->preferNullableValue(
                    optional($episode->aired_at)?->toDateString(),
                    $this->precisionDate(data_get($episodePayload, 'releaseDate')),
                ),
            ]);
            $episode->save();

            if ($episode->trashed()) {
                $episode->restore();
            }

            $this->upsertEpisodeStatistic($episodeTitle, $episodePayload);
            $this->syncEpisodeImage($episodeTitle, data_get($episodePayload, 'primaryImage'));
        }

        return $this->resolveEpisodeCount($bundle['episodes'], $bundle['seasons']);
    }

    /**
     * @param  array<string, mixed>  $episodePayload
     */
    private function upsertEpisodeTitle(Title $series, array $episodePayload): Title
    {
        $episodeImdbId = $this->requiredImdbId($episodePayload);
        $episodeName = $this->requiredNonEmptyString($episodePayload, 'title');
        $episodeTitle = Title::query()->withTrashed()->firstOrNew([
            'imdb_id' => $episodeImdbId,
        ]);

        $episodeTitle->fill([
            'imdb_id' => $episodeImdbId,
            'name' => $this->preferStringValue($episodeTitle->name, $episodeName),
            'original_name' => $this->preferStringValue($episodeTitle->original_name, $episodeName),
            'sort_title' => $this->preferStringValue($episodeTitle->sort_title, $episodeName),
            'title_type' => $this->preferStringValue(
                $episodeTitle->title_type?->value ?? $episodeTitle->getRawOriginal('title_type'),
                TitleType::Episode->value,
            ),
            'imdb_type' => $this->preferStringValue($episodeTitle->imdb_type, 'episode'),
            'release_year' => $this->preferNumericValue(
                $episodeTitle->release_year,
                $this->nullableInt(data_get($episodePayload, 'releaseDate.year')) ?? $series->release_year,
            ),
            'release_date' => $this->preferNullableValue(
                optional($episodeTitle->release_date)?->toDateString(),
                $this->precisionDate(data_get($episodePayload, 'releaseDate')),
            ),
            'runtime_minutes' => $this->preferNumericValue(
                $episodeTitle->runtime_minutes,
                $this->runtimeMinutes(data_get($episodePayload, 'runtimeSeconds')),
            ),
            'runtime_seconds' => $this->preferNumericValue(
                $episodeTitle->runtime_seconds,
                $this->nullableInt(data_get($episodePayload, 'runtimeSeconds')),
            ),
            'plot_outline' => $this->preferStringValue(
                $episodeTitle->plot_outline,
                $this->nullableString(data_get($episodePayload, 'plot')),
            ),
            'origin_country' => $this->preferStringValue($episodeTitle->origin_country, $series->origin_country),
            'original_language' => $this->preferStringValue($episodeTitle->original_language, $series->original_language),
            'search_keywords' => $this->preferStringValue($episodeTitle->search_keywords, $series->search_keywords),
            'imdb_payload' => $this->mergeCompactPayload($episodeTitle->imdb_payload, [
                'episode' => $episodePayload,
            ]),
            'is_published' => true,
        ]);
        $episodeTitle->save();

        if ($episodeTitle->trashed()) {
            $episodeTitle->restore();
        }

        return $episodeTitle;
    }

    /**
     * @param  array<string, mixed>  $episodePayload
     */
    private function upsertEpisodeStatistic(Title $episodeTitle, array $episodePayload): void
    {
        $rating = data_get($episodePayload, 'rating');
        $titleStatistic = TitleStatistic::query()->firstOrNew([
            'title_id' => $episodeTitle->id,
        ]);

        if ($titleStatistic->rating_distribution === null) {
            $titleStatistic->rating_distribution = TitleStatistic::normalizeRatingDistribution();
        }

        $titleStatistic->fill([
            'rating_count' => $this->preferNumericValue(
                $titleStatistic->rating_count,
                $this->nullableInt(data_get($rating, 'voteCount')),
            ) ?? 0,
            'average_rating' => $this->preferDecimalValue(
                $titleStatistic->average_rating,
                $this->nullableFloat(data_get($rating, 'aggregateRating')),
            ) ?? 0,
        ]);
        $titleStatistic->save();
    }

    private function syncEpisodeImage(Title $episodeTitle, mixed $primaryImage): void
    {
        if (! is_array($primaryImage)) {
            return;
        }

        $url = $this->nullableString(data_get($primaryImage, 'url'));

        if ($url === null) {
            return;
        }

        $this->upsertMediaAsset(
            MediaKind::Still,
            $episodeTitle,
            hash('sha1', 'episode-image:'.$episodeTitle->imdb_id.':'.$url),
            $url,
            $episodeTitle->name,
            'episode-primary-image',
            $this->nullableInt(data_get($primaryImage, 'width')),
            $this->nullableInt(data_get($primaryImage, 'height')),
            true,
            0,
            metadata: [
                'source_context' => 'episode-primary-image',
                'image' => $primaryImage,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $bundle
     */
    private function syncCredits(Title $title, array $bundle): void
    {
        if ($bundle['has_full_credits']) {
            $importedCreditIds = [];

            foreach ($bundle['credits'] as $index => $creditPayload) {
                $namePayload = data_get($creditPayload, 'name');

                if (! is_array($namePayload)) {
                    continue;
                }

                $imdbPersonId = $this->requiredImdbPersonId($namePayload);
                $category = $this->nullableString(data_get($creditPayload, 'category')) ?? 'crew';
                $person = $this->upsertPerson($namePayload, data_get($bundle['names'], $imdbPersonId, []), $category);
                [$professionName, $department] = $this->mapProfession($category);
                $profession = $this->upsertProfession($person, $professionName, $department, true, 0);

                $credit = Credit::query()
                    ->withTrashed()
                    ->firstOrNew([
                        'title_id' => $title->id,
                        'person_id' => $person->id,
                        'department' => $department,
                        'job' => $professionName,
                        'episode_id' => null,
                        'imdb_source_group' => 'imdb:'.$category,
                    ]);

                $characters = $this->normalizeStringList(data_get($creditPayload, 'characters'));

                $credit->fill([
                    'character_name' => $this->preferStringValue($credit->character_name, $characters === [] ? null : implode(' | ', $characters)),
                    'billing_order' => $this->preferNumericValue($credit->billing_order, $index + 1),
                    'is_principal' => $credit->exists && $this->fillMissingOnly ? $credit->is_principal : true,
                    'person_profession_id' => $this->preferNumericValue($credit->person_profession_id, $profession->id),
                    'credited_as' => $this->preferStringValue($credit->credited_as, null),
                    'imdb_source_group' => 'imdb:'.$category,
                ]);
                $credit->save();

                if ($credit->trashed()) {
                    $credit->restore();
                }

                $importedCreditIds[] = $credit->id;
            }

            if (! $this->fillMissingOnly) {
                $staleCredits = Credit::query()
                    ->where('title_id', $title->id)
                    ->whereNotNull('imdb_source_group');

                if ($importedCreditIds !== []) {
                    $staleCredits->whereNotIn('id', $importedCreditIds);
                }

                $staleCredits->get()->each->delete();
            }

            return;
        }

        $this->syncLegacyCreditsGroup($title, data_get($bundle['title'], 'directors'), 'directors', 'Directing', 'Director');
        $this->syncLegacyCreditsGroup($title, data_get($bundle['title'], 'writers'), 'writers', 'Writing', 'Writer');
        $this->syncLegacyCreditsGroup($title, data_get($bundle['title'], 'stars'), 'stars', 'Cast', 'Actor');
    }

    private function syncLegacyCreditsGroup(
        Title $title,
        mixed $peoplePayload,
        string $sourceGroup,
        string $department,
        string $job,
    ): void {
        $importedCreditIds = [];

        foreach ($this->normalizeObjectList($peoplePayload) as $index => $personPayload) {
            $person = $this->upsertPerson($personPayload, [], $sourceGroup);
            $profession = $this->upsertProfession($person, $job, $department, true, 0);

            $credit = Credit::query()
                ->withTrashed()
                ->firstOrNew([
                    'title_id' => $title->id,
                    'person_id' => $person->id,
                    'department' => $department,
                    'job' => $job,
                    'imdb_source_group' => $sourceGroup,
                ]);

            $credit->fill([
                'billing_order' => $this->preferNumericValue($credit->billing_order, $index + 1),
                'is_principal' => $credit->exists && $this->fillMissingOnly ? $credit->is_principal : true,
                'person_profession_id' => $this->preferNumericValue($credit->person_profession_id, $profession->id),
                'character_name' => $this->preferStringValue($credit->character_name, null),
                'credited_as' => $this->preferStringValue($credit->credited_as, null),
                'imdb_source_group' => $sourceGroup,
            ]);
            $credit->save();

            if ($credit->trashed()) {
                $credit->restore();
            }

            $importedCreditIds[] = $credit->id;
        }

        if (! $this->fillMissingOnly) {
            $staleCredits = Credit::query()
                ->where('title_id', $title->id)
                ->where('imdb_source_group', $sourceGroup);

            if ($importedCreditIds !== []) {
                $staleCredits->whereNotIn('id', $importedCreditIds);
            }

            $staleCredits->get()->each->delete();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $companyCredits
     */
    private function syncCompanies(Title $title, array $companyCredits): void
    {
        foreach ($companyCredits as $index => $companyCreditPayload) {
            $companyPayload = data_get($companyCreditPayload, 'company');

            if (! is_array($companyPayload)) {
                continue;
            }

            $company = $this->upsertCompany(
                $companyPayload,
                $this->nullableString(data_get($companyCreditPayload, 'category')),
                $this->normalizeNamedCodes(data_get($companyCreditPayload, 'countries')),
            );

            $relationship = $this->nullableString(data_get($companyCreditPayload, 'category')) ?? 'production';
            $existingPivot = DB::table('company_title')
                ->where('company_id', $company->id)
                ->where('title_id', $title->id)
                ->where('relationship', $relationship)
                ->first();

            DB::table('company_title')->updateOrInsert(
                [
                    'company_id' => $company->id,
                    'title_id' => $title->id,
                    'relationship' => $relationship,
                ],
                [
                    'credited_as' => $this->preferStringValue(
                        is_object($existingPivot) ? $existingPivot->credited_as : null,
                        Str::limit($this->translationMetaDescription($companyCreditPayload), 255, ''),
                    ),
                    'is_primary' => is_object($existingPivot) && $this->fillMissingOnly
                        ? (bool) $existingPivot->is_primary
                        : $index === 0,
                    'sort_order' => $this->preferNumericValue(
                        is_object($existingPivot) ? (int) $existingPivot->sort_order : null,
                        $index + 1,
                    ),
                    'updated_at' => now(),
                    'created_at' => is_object($existingPivot) ? $existingPivot->created_at : now(),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $companyPayload
     * @param  list<array{code: string, name?: string}>  $countries
     */
    private function upsertCompany(array $companyPayload, ?string $category, array $countries): Company
    {
        $name = $this->requiredNonEmptyString($companyPayload, 'name');
        $companyId = $this->nullableString(data_get($companyPayload, 'id'));
        $slug = Str::slug(trim(($companyId !== null ? $companyId.' ' : '').$name));
        $company = Company::query()->withTrashed()->firstOrNew([
            'slug' => $slug,
        ]);

        $company->fill([
            'name' => $this->preferStringValue($company->name, $name),
            'slug' => $slug,
            'kind' => $this->preferStringValue($company->kind?->value ?? $company->getRawOriginal('kind'), $this->mapCompanyKind($category)->value),
            'country_code' => $this->preferStringValue($company->country_code, data_get($countries, '0.code')),
            'description' => $company->description,
            'is_published' => true,
        ]);
        $company->save();

        if ($company->trashed()) {
            $company->restore();
        }

        return $company;
    }

    /**
     * @param  list<array<string, mixed>>  $awardNominations
     * @param  array<string, array<string, mixed>>  $names
     */
    private function syncAwards(Title $title, array $awardNominations, array $names): void
    {
        foreach ($awardNominations as $index => $awardNominationPayload) {
            $eventPayload = data_get($awardNominationPayload, 'event');

            if (! is_array($eventPayload)) {
                continue;
            }

            $year = $this->nullableInt(data_get($awardNominationPayload, 'year')) ?? $title->release_year ?? (int) now()->year;
            $award = $this->upsertAward($eventPayload);
            $awardEvent = $this->upsertAwardEvent($award, $eventPayload, $year);
            $nomineePayloads = $this->normalizeObjectList(data_get($awardNominationPayload, 'nominees'));
            $categoryName = $this->nullableString(data_get($awardNominationPayload, 'category'))
                ?? $this->nullableString(data_get($awardNominationPayload, 'text'))
                ?? 'General';
            $awardCategory = $this->upsertAwardCategory(
                $award,
                $categoryName,
                $nomineePayloads === [] ? 'title' : 'person',
                $this->nullableString(data_get($awardNominationPayload, 'text')),
            );

            if ($nomineePayloads === []) {
                $this->upsertAwardNomination($awardEvent, $awardCategory, $title, null, $awardNominationPayload, $index + 1);

                continue;
            }

            foreach ($nomineePayloads as $nomineeIndex => $nomineePayload) {
                $nomineeId = $this->nullableString(data_get($nomineePayload, 'id'));
                $person = $nomineeId !== null
                    ? $this->upsertPerson($nomineePayload, data_get($names, $nomineeId, []), $categoryName)
                    : null;

                $this->upsertAwardNomination(
                    $awardEvent,
                    $awardCategory,
                    $title,
                    $person,
                    $awardNominationPayload,
                    (($index + 1) * 100) + $nomineeIndex,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    private function upsertAward(array $eventPayload): Award
    {
        $name = $this->requiredNonEmptyString($eventPayload, 'name');
        $eventId = $this->nullableString(data_get($eventPayload, 'id'));
        $slug = Str::slug(trim(($eventId !== null ? $eventId.' ' : '').$name));
        $award = Award::query()->withTrashed()->firstOrNew([
            'slug' => $slug,
        ]);

        $award->fill([
            'name' => $this->preferStringValue($award->name, $name),
            'slug' => $slug,
            'description' => $award->description,
            'country_code' => $award->country_code,
            'is_published' => true,
        ]);
        $award->save();

        if ($award->trashed()) {
            $award->restore();
        }

        return $award;
    }

    /**
     * @param  array<string, mixed>  $eventPayload
     */
    private function upsertAwardEvent(Award $award, array $eventPayload, int $year): AwardEvent
    {
        $name = $this->requiredNonEmptyString($eventPayload, 'name');
        $eventId = $this->nullableString(data_get($eventPayload, 'id'));
        $slug = Str::slug(trim(($eventId !== null ? $eventId.' ' : '').$name.' '.$year));
        $awardEvent = AwardEvent::query()->firstOrNew([
            'slug' => $slug,
        ]);

        $awardEvent->fill([
            'award_id' => $award->id,
            'name' => $this->preferStringValue($awardEvent->name, $name),
            'slug' => $slug,
            'year' => $this->preferNumericValue($awardEvent->year, $year),
            'edition' => $awardEvent->edition,
            'event_date' => optional($awardEvent->event_date)?->toDateString(),
            'location' => $awardEvent->location,
            'details' => $eventId === null
                ? $awardEvent->details
                : $this->preferStringValue($awardEvent->details, json_encode(['source' => 'imdb', 'event_id' => $eventId], JSON_THROW_ON_ERROR)),
        ]);
        $awardEvent->save();

        return $awardEvent;
    }

    private function upsertAwardCategory(
        Award $award,
        string $name,
        string $recipientScope,
        ?string $description,
    ): AwardCategory {
        $slug = Str::slug($name);
        $awardCategory = AwardCategory::query()->firstOrNew([
            'award_id' => $award->id,
            'slug' => $slug,
        ]);

        $awardCategory->fill([
            'name' => $this->preferStringValue($awardCategory->name, $name),
            'slug' => $slug,
            'recipient_scope' => $this->preferStringValue($awardCategory->recipient_scope, $recipientScope),
            'description' => $this->preferStringValue($awardCategory->description, $description),
        ]);
        $awardCategory->save();

        return $awardCategory;
    }

    /**
     * @param  array<string, mixed>  $awardNominationPayload
     */
    private function upsertAwardNomination(
        AwardEvent $awardEvent,
        AwardCategory $awardCategory,
        Title $title,
        ?Person $person,
        array $awardNominationPayload,
        int $sortOrder,
    ): void {
        $creditedName = $person?->name ?? $title->name;
        $awardNomination = AwardNomination::query()->firstOrNew([
            'award_event_id' => $awardEvent->id,
            'award_category_id' => $awardCategory->id,
            'title_id' => $title->id,
            'person_id' => $person?->id,
            'company_id' => null,
            'episode_id' => null,
            'credited_name' => $creditedName,
        ]);

        $awardNomination->fill([
            'details' => $this->preferStringValue($awardNomination->details, json_encode([
                'source' => 'imdb',
                'text' => $this->nullableString(data_get($awardNominationPayload, 'text')),
                'winner_rank' => $this->nullableInt(data_get($awardNominationPayload, 'winnerRank')),
            ], JSON_THROW_ON_ERROR)),
            'is_winner' => $awardNomination->exists && $this->fillMissingOnly
                ? $awardNomination->is_winner
                : (bool) data_get($awardNominationPayload, 'isWinner', false),
            'sort_order' => $this->preferNumericValue($awardNomination->sort_order, $sortOrder),
        ]);
        $awardNomination->save();
    }

    /**
     * @param  array<string, mixed>  $personPayload
     * @param  array<string, mixed>  $nameBundle
     */
    private function upsertPerson(array $personPayload, array $nameBundle, ?string $sourceHint): Person
    {
        $imdbId = $this->requiredImdbPersonId($personPayload);
        $detailsPayload = is_array(data_get($nameBundle, 'details')) ? data_get($nameBundle, 'details') : $personPayload;
        $displayName = $this->requiredNonEmptyString($detailsPayload, 'displayName');
        $alternativeNames = $this->uniqueStrings([
            ...$this->normalizeStringList(data_get($personPayload, 'alternativeNames')),
            ...$this->normalizeStringList(data_get($detailsPayload, 'alternativeNames')),
            ...array_filter([$this->nullableString(data_get($detailsPayload, 'birthName'))]),
        ]);
        $primaryProfessions = $this->uniqueStrings([
            ...$this->normalizeStringList(data_get($personPayload, 'primaryProfessions')),
            ...$this->normalizeStringList(data_get($detailsPayload, 'primaryProfessions')),
        ]);

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
                ? $person->imdb_alternative_names
                : $this->preferArrayValue($person->imdb_alternative_names, $alternativeNames),
            'imdb_primary_professions' => $primaryProfessions === []
                ? $person->imdb_primary_professions
                : $this->preferArrayValue($person->imdb_primary_professions, $primaryProfessions),
            'imdb_payload' => $this->mergeCompactPayload($person->imdb_payload, $this->buildCompactImdbPayloadAction->forPerson(
                $this->buildPersonPayload($person->imdb_payload, $personPayload, $nameBundle),
            )),
            'biography' => $this->preferStringValue($person->biography, $biography),
            'short_biography' => $this->preferStringValue($person->short_biography, $this->extractShortBiography($biography)),
            'known_for_department' => $this->preferStringValue($person->known_for_department, $this->knownForDepartment($sourceHint ?? '', $primaryProfessions)),
            'birth_date' => $this->preferNullableValue(optional($person->birth_date)?->toDateString(), $this->precisionDate(data_get($detailsPayload, 'birthDate'))),
            'death_date' => $this->preferNullableValue(optional($person->death_date)?->toDateString(), $this->precisionDate(data_get($detailsPayload, 'deathDate'))),
            'birth_place' => $this->preferStringValue($person->birth_place, $this->nullableString(data_get($detailsPayload, 'birthLocation'))),
            'death_place' => $this->preferStringValue($person->death_place, $this->nullableString(data_get($detailsPayload, 'deathLocation'))),
            'nationality' => $person->nationality,
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

        if ($primaryProfessions === [] && $sourceHint !== null && trim($sourceHint) !== '') {
            [$profession, $department] = $this->mapProfession($sourceHint);
            $this->upsertProfession($person, $profession, $department, true, 0);
        }

        foreach ($primaryProfessions as $sortOrder => $professionLabel) {
            [$profession, $department] = $this->mapProfession($professionLabel);
            $this->upsertProfession($person, $profession, $department, $sortOrder === 0, $sortOrder);
        }

        $this->syncPersonMediaAssets($person, $detailsPayload, $nameBundle, $personPayload);

        return $person;
    }

    /**
     * @param  array<string, mixed>  $personPayload
     * @param  array<string, mixed>  $nameBundle
     * @return array<string, mixed>
     */
    private function buildPersonPayload(mixed $existingPayload, array $personPayload, array $nameBundle): array
    {
        if ($nameBundle !== []) {
            return array_filter([
                'details' => is_array(data_get($nameBundle, 'details')) ? data_get($nameBundle, 'details') : $personPayload,
                'images' => is_array(data_get($nameBundle, 'images')) ? data_get($nameBundle, 'images') : null,
                'relationships' => is_array(data_get($nameBundle, 'relationships')) ? data_get($nameBundle, 'relationships') : null,
                'trivia' => is_array(data_get($nameBundle, 'trivia')) ? data_get($nameBundle, 'trivia') : null,
            ], fn (mixed $value): bool => $value !== null);
        }

        if (is_array($existingPayload) && array_key_exists('details', $existingPayload)) {
            return $existingPayload;
        }

        return $personPayload;
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

    private function upsertProfession(
        Person $person,
        string $profession,
        string $department,
        bool $isPrimary,
        int $sortOrder,
    ): PersonProfession {
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

        return $personProfession;
    }

    /**
     * @param  array<string, mixed>  $detailsPayload
     * @param  array<string, mixed>  $nameBundle
     * @param  array<string, mixed>  $fallbackPayload
     */
    private function syncPersonMediaAssets(Person $person, array $detailsPayload, array $nameBundle, array $fallbackPayload): void
    {
        $importedProviderKeys = [];
        $hasFullImages = is_array(data_get($nameBundle, 'images'));

        if ($hasFullImages) {
            foreach ($this->normalizeObjectList(data_get($nameBundle, 'images.images')) as $index => $imagePayload) {
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
                    metadata: [
                        'source_context' => 'person-image',
                        'image' => $imagePayload,
                    ],
                );
            }
        }

        $primaryImage = data_get($detailsPayload, 'primaryImage');

        if (! is_array($primaryImage)) {
            $primaryImage = data_get($fallbackPayload, 'primaryImage');
        }

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
                    metadata: [
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

    private function removeStaleImdbMediaAssets(Model $mediable, array $importedProviderKeys): void
    {
        $staleAssets = MediaAsset::query()
            ->withTrashed()
            ->where('mediable_type', $mediable::class)
            ->where('mediable_id', $mediable->getKey())
            ->where('provider', 'imdb');

        if ($importedProviderKeys !== []) {
            $staleAssets->whereNotIn('provider_key', array_values(array_unique($importedProviderKeys)));
        }

        $staleAssets->get()->each->delete();
    }

    private function upsertMediaAsset(
        MediaKind $kind,
        Model $mediable,
        string $providerKey,
        string $url,
        ?string $altText,
        ?string $caption,
        ?int $width,
        ?int $height,
        bool $isPrimary,
        int $position,
        ?string $language = null,
        ?int $durationSeconds = null,
        ?array $metadata = null,
    ): string {
        $asset = MediaAsset::query()->withTrashed()->firstOrNew([
            'mediable_type' => $mediable::class,
            'mediable_id' => $mediable->getKey(),
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
            'language' => $this->preferStringValue($asset->language, $language),
            'duration_seconds' => $this->preferNumericValue($asset->duration_seconds, $durationSeconds),
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

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{
     *     akas: list<array<string, mixed>>,
     *     awardNominations: list<array<string, mixed>>,
     *     awardStats: array<string, mixed>|null,
     *     boxOffice: array<string, mixed>|null,
     *     certificates: list<array<string, mixed>>,
     *     companyCredits: list<array<string, mixed>>,
     *     credits: list<array<string, mixed>>,
     *     episodes: list<array<string, mixed>>,
     *     has_full_credits: bool,
     *     has_full_episodes: bool,
     *     has_full_images: bool,
     *     has_full_seasons: bool,
     *     has_full_videos: bool,
     *     images: list<array<string, mixed>>,
     *     is_bundle: bool,
     *     names: array<string, array<string, mixed>>,
     *     parentsGuide: array<string, mixed>|null,
     *     releaseDates: list<array<string, mixed>>,
     *     seasons: list<array<string, mixed>>,
     *     source_url: string,
     *     title: array<string, mixed>,
     *     videos: list<array<string, mixed>>
     * }  $bundle
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
     */
    private function writeEndpointReports(
        string $artifactDirectory,
        string $imdbId,
        array $payload,
        array $bundle,
        array $before,
        array $after,
    ): void {
        foreach ($this->endpointArtifactPaths() as $endpoint => $artifactPath) {
            $this->writeEndpointReport(
                $artifactDirectory,
                $endpoint,
                $this->endpointHasPayload($endpoint, $payload, $bundle),
                $this->snapshotEndpointSlice($before, $endpoint),
                $this->snapshotEndpointSlice($after, $endpoint),
                [
                    'artifact_path' => $artifactPath,
                    'imdb_id' => $imdbId,
                ],
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function endpointArtifactPaths(): array
    {
        return [
            'title' => 'title.json',
            'credits' => 'credits.json',
            'releaseDates' => 'release-dates.json',
            'akas' => 'aka-titles.json',
            'seasons' => 'seasons.json',
            'episodes' => 'episodes.json',
            'images' => 'images.json',
            'videos' => 'videos.json',
            'awardNominations' => 'award-nominations.json',
            'parentsGuide' => 'parents-guide.json',
            'certificates' => 'certificates.json',
            'companyCredits' => 'company-credits.json',
            'boxOffice' => 'box-office.json',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $bundle
     */
    private function endpointHasPayload(string $endpoint, array $payload, array $bundle): bool
    {
        return match ($endpoint) {
            'title' => is_array(data_get($payload, 'title')) || is_string(data_get($payload, 'id')),
            'credits' => is_array(data_get($payload, 'credits'))
                || $this->normalizeObjectList(data_get($payload, 'directors')) !== []
                || $this->normalizeObjectList(data_get($payload, 'writers')) !== []
                || $this->normalizeObjectList(data_get($payload, 'stars')) !== [],
            'releaseDates' => is_array(data_get($payload, 'releaseDates')),
            'akas' => is_array(data_get($payload, 'akas')),
            'seasons' => is_array(data_get($payload, 'seasons')),
            'episodes' => is_array(data_get($payload, 'episodes')),
            'images' => is_array(data_get($payload, 'images'))
                || is_array(data_get($payload, 'primaryImage'))
                || is_array(data_get($payload, 'title.primaryImage')),
            'videos' => is_array(data_get($payload, 'videos')),
            'awardNominations' => is_array(data_get($payload, 'awardNominations')),
            'parentsGuide' => is_array(data_get($payload, 'parentsGuide')),
            'certificates' => is_array(data_get($payload, 'certificates')),
            'companyCredits' => is_array(data_get($payload, 'companyCredits')),
            'boxOffice' => is_array(data_get($payload, 'boxOffice')),
            default => false,
        };
    }

    /**
     * @return array{
     *     exists: bool,
     *     fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }
     */
    private function snapshotTitleImportState(string $imdbId): array
    {
        $title = Title::query()
            ->withTrashed()
            ->where('imdb_id', $imdbId)
            ->with([
                'awardNominations.awardCategory:id,name',
                'awardNominations.awardEvent.award:id,name',
                'awardNominations.awardEvent:id,award_id,name,year',
                'awardNominations.person:id,imdb_id,name',
                'companies:id,name,slug',
                'credits.person:id,imdb_id,name',
                'genres:id,name,slug',
                'statistic:id,title_id,rating_count,average_rating,metacritic_score,metacritic_review_count,episodes_count,awards_nominated_count,awards_won_count',
                'mediaAssets:id,mediable_type,mediable_id,kind,url,caption,provider_key,metadata',
                'seasons:id,series_id,season_number,name',
                'seriesEpisodes.title:id,imdb_id,name',
                'seriesEpisodes:id,series_id,title_id,season_number,episode_number',
                'translations:id,title_id,locale,localized_title',
            ])
            ->first();

        if (! $title instanceof Title) {
            return [
                'exists' => false,
                'fields' => [],
                'relations' => [],
            ];
        }

        $fields = $this->filledFieldLabels($title, [
            'name' => 'Name',
            'original_name' => 'Original name',
            'title_type' => 'Title type',
            'imdb_type' => 'IMDb type',
            'release_year' => 'Release year',
            'end_year' => 'End year',
            'release_date' => 'Release date',
            'runtime_minutes' => 'Runtime minutes',
            'runtime_seconds' => 'Runtime seconds',
            'age_rating' => 'Age rating',
            'plot_outline' => 'Plot outline',
            'synopsis' => 'Synopsis',
            'tagline' => 'Tagline',
            'origin_country' => 'Origin country',
            'original_language' => 'Original language',
            'popularity_rank' => 'Popularity rank',
            'search_keywords' => 'Search keywords',
        ]);

        if ($title->statistic instanceof TitleStatistic) {
            $fields = array_merge($fields, $this->filledFieldLabels($title->statistic, [
                'rating_count' => 'Rating count',
                'average_rating' => 'Average rating',
                'metacritic_score' => 'Metacritic score',
                'metacritic_review_count' => 'Metacritic review count',
                'episodes_count' => 'Episodes count',
                'awards_nominated_count' => 'Awards nominated count',
                'awards_won_count' => 'Awards won count',
            ], 'statistic.'));
        }

        return [
            'exists' => true,
            'fields' => $fields,
            'relations' => [
                'genres' => $title->genres
                    ->mapWithKeys(fn (Genre $genre): array => [$genre->slug => $genre->name])
                    ->all(),
                'translations' => $title->translations
                    ->mapWithKeys(fn (TitleTranslation $translation): array => [$translation->locale => $translation->locale.' · '.$translation->localized_title])
                    ->all(),
                'seasons' => $title->seasons
                    ->mapWithKeys(fn (Season $season): array => ['season:'.$season->season_number => 'Season '.$season->season_number.' · '.$season->name])
                    ->all(),
                'episodes' => $title->seriesEpisodes
                    ->mapWithKeys(function (Episode $episode): array {
                        $label = collect([
                            $episode->season_number !== null ? 'S'.$episode->season_number : null,
                            $episode->episode_number !== null ? 'E'.$episode->episode_number : null,
                            $episode->title?->name,
                        ])->filter()->implode(' ');

                        return [($episode->title?->imdb_id ?? 'episode-'.$episode->id) => $label];
                    })
                    ->all(),
                'credits' => $title->credits
                    ->mapWithKeys(function (Credit $credit): array {
                        $character = filled($credit->character_name) ? ' · '.$credit->character_name : '';

                        return [
                            implode('|', array_filter([
                                $credit->person?->imdb_id,
                                $credit->department,
                                $credit->job,
                                $credit->episode_id,
                                $credit->imdb_source_group,
                            ], fn (mixed $value): bool => $value !== null && $value !== '')) => trim(($credit->person?->name ?? $credit->credited_as ?? 'Unknown person').' · '.$credit->job.$character),
                        ];
                    })
                    ->all(),
                'companies' => $title->companies
                    ->mapWithKeys(function (Company $company): array {
                        $relationship = (string) ($company->pivot?->relationship ?? 'company');

                        return [$company->slug.'|'.$relationship => $relationship.' · '.$company->name];
                    })
                    ->all(),
                'awards' => $title->awardNominations
                    ->mapWithKeys(function (AwardNomination $awardNomination): array {
                        $label = collect([
                            $awardNomination->awardEvent?->award?->name ?? $awardNomination->awardEvent?->name,
                            $awardNomination->awardCategory?->name,
                            $awardNomination->person?->name ?? $awardNomination->credited_name,
                        ])->filter()->implode(' · ');

                        return [
                            implode('|', array_filter([
                                $awardNomination->award_event_id,
                                $awardNomination->award_category_id,
                                $awardNomination->person_id,
                                $awardNomination->credited_name,
                            ], fn (mixed $value): bool => $value !== null && $value !== '')) => $label,
                        ];
                    })
                    ->all(),
                'image_assets' => $title->mediaAssets
                    ->filter(fn (MediaAsset $mediaAsset): bool => ! in_array($mediaAsset->kind?->value ?? $mediaAsset->getRawOriginal('kind'), [
                        MediaKind::Trailer->value,
                        MediaKind::Clip->value,
                        MediaKind::Featurette->value,
                    ], true))
                    ->mapWithKeys(fn (MediaAsset $mediaAsset): array => [$mediaAsset->provider_key => $this->mediaAssetLabel($mediaAsset)])
                    ->all(),
                'video_assets' => $title->mediaAssets
                    ->filter(fn (MediaAsset $mediaAsset): bool => in_array($mediaAsset->kind?->value ?? $mediaAsset->getRawOriginal('kind'), [
                        MediaKind::Trailer->value,
                        MediaKind::Clip->value,
                        MediaKind::Featurette->value,
                    ], true))
                    ->mapWithKeys(fn (MediaAsset $mediaAsset): array => [$mediaAsset->provider_key => $this->mediaAssetLabel($mediaAsset)])
                    ->all(),
                'payload_sections' => collect(is_array($title->imdb_payload) ? $title->imdb_payload : [])
                    ->except(['storageVersion'])
                    ->mapWithKeys(fn (mixed $value, string $key): array => [$key => Str::headline($key)])
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array{
     *     exists: bool,
     *     fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }  $snapshot
     * @return array{
     *     exists: bool,
     *     fields: array<string, string>,
     *     relations: array<string, array<string, string>>
     * }
     */
    private function snapshotEndpointSlice(array $snapshot, string $endpoint): array
    {
        $fieldKeys = match ($endpoint) {
            'title' => [
                'name',
                'original_name',
                'title_type',
                'imdb_type',
                'release_year',
                'end_year',
                'runtime_minutes',
                'runtime_seconds',
                'plot_outline',
                'synopsis',
                'tagline',
                'origin_country',
                'original_language',
                'popularity_rank',
                'search_keywords',
                'statistic.rating_count',
                'statistic.average_rating',
                'statistic.metacritic_score',
                'statistic.metacritic_review_count',
            ],
            'releaseDates' => ['release_date'],
            'awardNominations' => ['statistic.awards_nominated_count', 'statistic.awards_won_count'],
            'certificates' => ['age_rating'],
            default => [],
        };
        $relationMap = match ($endpoint) {
            'title' => [
                'genres' => $snapshot['relations']['genres'] ?? [],
                'payload_sections' => array_intersect_key(
                    $snapshot['relations']['payload_sections'] ?? [],
                    array_flip(['title', 'interests'])
                ),
            ],
            'credits' => [
                'credits' => $snapshot['relations']['credits'] ?? [],
            ],
            'releaseDates' => [
                'payload_sections' => array_intersect_key(
                    $snapshot['relations']['payload_sections'] ?? [],
                    array_flip(['releaseDates'])
                ),
            ],
            'akas' => [
                'translations' => $snapshot['relations']['translations'] ?? [],
            ],
            'seasons' => [
                'seasons' => $snapshot['relations']['seasons'] ?? [],
            ],
            'episodes' => [
                'episodes' => $snapshot['relations']['episodes'] ?? [],
            ],
            'images' => [
                'media_assets' => $snapshot['relations']['image_assets'] ?? [],
            ],
            'videos' => [
                'media_assets' => $snapshot['relations']['video_assets'] ?? [],
            ],
            'awardNominations' => [
                'awards' => $snapshot['relations']['awards'] ?? [],
            ],
            'parentsGuide' => [
                'payload_sections' => array_intersect_key(
                    $snapshot['relations']['payload_sections'] ?? [],
                    array_flip(['parentsGuide'])
                ),
            ],
            'certificates' => [
                'payload_sections' => array_intersect_key(
                    $snapshot['relations']['payload_sections'] ?? [],
                    array_flip(['certificates'])
                ),
            ],
            'companyCredits' => [
                'companies' => $snapshot['relations']['companies'] ?? [],
            ],
            'boxOffice' => [
                'payload_sections' => array_intersect_key(
                    $snapshot['relations']['payload_sections'] ?? [],
                    array_flip(['boxOffice'])
                ),
            ],
            default => [],
        };

        return [
            'exists' => $snapshot['exists'],
            'fields' => array_intersect_key($snapshot['fields'], array_flip($fieldKeys)),
            'relations' => collect($relationMap)
                ->filter(fn (array $values): bool => $values !== [])
                ->all(),
        ];
    }

    private function mediaAssetLabel(MediaAsset $mediaAsset): string
    {
        $kind = $mediaAsset->kind?->value ?? $mediaAsset->getRawOriginal('kind');

        return collect([$kind, $mediaAsset->caption, $mediaAsset->url])->filter()->implode(' · ');
    }

    /**
     * @param  array<string, string>  $fieldLabels
     * @return array<string, string>
     */
    private function filledFieldLabels(object $model, array $fieldLabels, string $prefix = ''): array
    {
        return collect($fieldLabels)
            ->filter(function (string $label, string $field) use ($model): bool {
                return $this->hasMeaningfulValue(data_get($model, $field));
            })
            ->mapWithKeys(fn (string $label, string $field): array => [$prefix.$field => $label])
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

    private function resolveArtifactDirectory(string $storagePath, string $imdbId): string
    {
        if (str_ends_with(str_replace('\\', '/', $storagePath), '.json')) {
            $directory = dirname($storagePath);

            if (is_string($directory) && $directory !== '' && ! str_ends_with(str_replace('\\', '/', $directory), '/'.$imdbId)) {
                return $directory.DIRECTORY_SEPARATOR.$imdbId;
            }

            return $directory;
        }

        return rtrim($storagePath, DIRECTORY_SEPARATOR);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertImportRecord(string $imdbId, array $payload, string $storagePath, string $sourceUrl): void
    {
        try {
            $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Failed to encode import payload for [%s].', $imdbId), previous: $exception);
        }

        $imdbTitleImport = ImdbTitleImport::query()->firstOrNew([
            'imdb_id' => $imdbId,
        ]);

        $imdbTitleImport->fill([
            'source_url' => $sourceUrl,
            'storage_path' => $storagePath,
            'payload_hash' => $payloadHash,
            'payload' => $payload,
            'imported_at' => now(),
        ]);

        if ($imdbTitleImport->downloaded_at === null && File::exists($storagePath)) {
            $imdbTitleImport->downloaded_at = now()->setTimestamp(File::lastModified($storagePath));
        }

        $imdbTitleImport->save();
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

    private function preferDecimalValue(mixed $existing, mixed $incoming): ?float
    {
        $existingValue = $this->nullableFloat($existing);
        $incomingValue = $this->nullableFloat($incoming);

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

            return 'json:'.json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        if (is_bool($value)) {
            return 'bool:'.($value ? '1' : '0');
        }

        if (is_scalar($value) || $value === null) {
            return get_debug_type($value).':'.(string) $value;
        }

        return 'serialized:'.serialize($value);
    }

    private function firstOrCreateGenre(string $genreName): Genre
    {
        $slug = Str::slug($genreName);
        $genre = Genre::query()->firstOrCreate(['slug' => $slug], ['name' => $genreName]);

        if ($genre->name !== $genreName) {
            $genre->forceFill(['name' => $genreName])->save();
        }

        return $genre;
    }

    /**
     * @param  list<string>  $genres
     * @param  list<array{id?: string, isSubgenre?: bool, name: string}>  $interests
     * @param  list<array{code: string, name?: string}>  $originCountries
     * @param  list<array{code: string, name?: string}>  $spokenLanguages
     * @param  list<string>  $akaTitles
     */
    private function buildSearchKeywords(
        array $genres,
        array $interests,
        array $originCountries,
        array $spokenLanguages,
        array $akaTitles = [],
    ): ?string {
        $keywords = $this->uniqueStrings([
            ...$genres,
            ...collect($interests)->pluck('name')->all(),
            ...collect($originCountries)->pluck('name')->filter()->all(),
            ...collect($spokenLanguages)->pluck('name')->filter()->all(),
            ...$akaTitles,
        ]);

        return $keywords === [] ? null : implode(', ', array_slice($keywords, 0, 40));
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
     * @return list<array{id?: string, isSubgenre?: bool, name: string}>
     */
    private function normalizeInterests(mixed $interests): array
    {
        return collect(is_iterable($interests) ? $interests : [])
            ->map(function (mixed $interest): ?array {
                if (! is_array($interest)) {
                    return null;
                }

                $name = $this->nullableString(data_get($interest, 'name'));

                if ($name === null) {
                    return null;
                }

                $normalized = ['name' => $name];
                $id = $this->nullableString(data_get($interest, 'id'));

                if ($id !== null) {
                    $normalized['id'] = $id;
                }

                if (data_get($interest, 'isSubgenre') !== null) {
                    $normalized['isSubgenre'] = (bool) data_get($interest, 'isSubgenre');
                }

                return $normalized;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{code: string, name?: string}>
     */
    private function normalizeNamedCodes(mixed $values): array
    {
        return collect(is_iterable($values) ? $values : [])
            ->map(function (mixed $value): ?array {
                if (! is_array($value)) {
                    return null;
                }

                $code = $this->nullableString(data_get($value, 'code'));

                if ($code === null) {
                    return null;
                }

                $normalized = ['code' => $code];
                $name = $this->nullableString(data_get($value, 'name'));

                if ($name !== null) {
                    $normalized['name'] = $name;
                }

                return $normalized;
            })
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
     * @return array<string, array<string, mixed>>
     */
    private function normalizeNameBundles(mixed $names): array
    {
        $normalized = [];

        foreach (is_iterable($names) ? $names : [] as $nameId => $namePayload) {
            if (is_string($nameId) && is_array($namePayload)) {
                $normalized[$nameId] = $namePayload;
            }
        }

        return $normalized;
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
     * @param  list<string>  $primaryProfessions
     */
    private function knownForDepartment(string $sourceGroup, array $primaryProfessions): string
    {
        return match ($sourceGroup) {
            'directors' => 'Directing',
            'writers' => 'Writing',
            'stars' => 'Cast',
            default => $primaryProfessions === [] ? 'Crew' : $this->mapProfession($primaryProfessions[0])[1],
        };
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

    private function mapCompanyKind(?string $category): CompanyKind
    {
        return match (Str::lower((string) $category)) {
            'production' => CompanyKind::Production,
            'distribution', 'distributor' => CompanyKind::Distributor,
            'network', 'broadcast' => CompanyKind::Network,
            'streaming', 'streamer' => CompanyKind::Streamer,
            default => CompanyKind::Studio,
        };
    }

    private function mapTitleType(string $imdbType): TitleType
    {
        return match ($imdbType) {
            'movie', 'tvMovie', 'video' => TitleType::Movie,
            'tvSeries', 'tvPilot', 'tvShortSeries' => TitleType::Series,
            'tvMiniSeries' => TitleType::MiniSeries,
            'documentary', 'tvDocumentary', 'documentaryShort' => TitleType::Documentary,
            'special', 'tvSpecial', 'videoGame' => TitleType::Special,
            'short', 'tvShort' => TitleType::Short,
            'episode', 'tvEpisode' => TitleType::Episode,
            default => Str::contains(strtolower($imdbType), 'series') ? TitleType::Series : TitleType::Movie,
        };
    }

    /**
     * @param  array<string, mixed>  $imagePayload
     */
    private function mapTitleImageKind(array $imagePayload): MediaKind
    {
        $type = Str::lower($this->nullableString(data_get($imagePayload, 'type')) ?? '');
        $width = $this->nullableInt(data_get($imagePayload, 'width')) ?? 0;
        $height = $this->nullableInt(data_get($imagePayload, 'height')) ?? 0;

        return match ($type) {
            'poster' => MediaKind::Poster,
            'still_frame' => $width > $height ? MediaKind::Backdrop : MediaKind::Still,
            default => MediaKind::Gallery,
        };
    }

    /**
     * @param  array<string, mixed>  $imagePayload
     */
    private function mapPersonImageKind(array $imagePayload): MediaKind
    {
        $width = $this->nullableInt(data_get($imagePayload, 'width')) ?? 0;
        $height = $this->nullableInt(data_get($imagePayload, 'height')) ?? 0;

        return $height >= $width ? MediaKind::Headshot : MediaKind::Gallery;
    }

    private function mapVideoKind(?string $type): MediaKind
    {
        return match (Str::lower((string) $type)) {
            'trailer' => MediaKind::Trailer,
            'clip' => MediaKind::Clip,
            default => MediaKind::Featurette,
        };
    }

    private function imdbVideoUrl(string $videoId): string
    {
        return sprintf('https://www.imdb.com/video/%s/', $videoId);
    }

    private function runtimeMinutes(mixed $runtimeSeconds): ?int
    {
        $seconds = $this->nullableInt($runtimeSeconds);

        if ($seconds === null) {
            return null;
        }

        return max(1, (int) ceil($seconds / 60));
    }

    /**
     * @param  array<string, mixed>  $bundle
     */
    private function resolveTitleReleaseDate(array $bundle): ?string
    {
        $releaseDates = collect($bundle['releaseDates'])
            ->map(fn (array $releaseDate): ?string => $this->precisionDate(data_get($releaseDate, 'releaseDate')))
            ->filter()
            ->sort()
            ->values();

        if ($releaseDates->isNotEmpty()) {
            return $releaseDates->first();
        }

        return $this->precisionDate(data_get($bundle['title'], 'releaseDate'));
    }

    /**
     * @param  list<array<string, mixed>>  $certificates
     */
    private function resolveAgeRating(array $certificates): ?string
    {
        $preferredCertificate = collect($certificates)
            ->map(function (array $certificate): ?array {
                $rating = $this->nullableString(data_get($certificate, 'rating'));

                if ($rating === null) {
                    return null;
                }

                return [
                    'rating' => $rating,
                    'country' => $this->nullableString(data_get($certificate, 'country.code')),
                    'is_tv' => str_starts_with($rating, 'TV-'),
                ];
            })
            ->filter()
            ->sortBy([
                fn (array $certificate): int => $certificate['country'] === 'US' ? 0 : 1,
                fn (array $certificate): int => $certificate['is_tv'] ? 1 : 0,
            ])
            ->first();

        return data_get($preferredCertificate, 'rating');
    }

    /**
     * @param  list<array<string, mixed>>  $episodes
     * @param  list<array<string, mixed>>  $seasons
     */
    private function resolveEpisodeCount(array $episodes, array $seasons): int
    {
        if ($episodes !== []) {
            return count($episodes);
        }

        return collect($seasons)
            ->sum(fn (array $season): int => $this->nullableInt(data_get($season, 'episodeCount')) ?? 0);
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
            return CarbonImmutable::createSafe($year, $month, $day, 0, 0, 0, 'UTC')->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $akas
     * @return list<string>
     */
    private function resolvePrimaryAkas(array $akas): array
    {
        return collect($akas)
            ->map(fn (array $aka): ?string => $this->nullableString(data_get($aka, 'text')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $aka
     */
    private function buildAkaLocale(array $aka): ?string
    {
        $languageCode = $this->nullableString(data_get($aka, 'language.code'));
        $countryCode = $this->nullableString(data_get($aka, 'country.code'));

        if ($languageCode !== null && $countryCode !== null) {
            return Str::lower($languageCode).'-'.Str::upper($countryCode);
        }

        if ($languageCode !== null) {
            return Str::lower($languageCode);
        }

        if ($countryCode !== null) {
            return 'und-'.Str::upper($countryCode);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function translationMetaDescription(array $payload): ?string
    {
        $parts = collect([
            $this->nullableString(data_get($payload, 'country.name')),
            $this->nullableString(data_get($payload, 'language.name')),
            $this->normalizeStringList(data_get($payload, 'attributes')) === []
                ? null
                : implode(', ', $this->normalizeStringList(data_get($payload, 'attributes'))),
        ])->filter()->values()->all();

        if ($parts === []) {
            return null;
        }

        return implode(' | ', $parts);
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
     * @param  array<string, mixed>  $payload
     */
    private function hasBundlePayload(array $payload): bool
    {
        return is_array(data_get($payload, 'title'))
            && is_string(data_get($payload, 'title.id'))
            && data_get($payload, 'schemaVersion') !== null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requiredImdbId(array $payload): string
    {
        $imdbId = data_get($payload, 'id');

        if (! is_string($imdbId) || preg_match('/^tt\d+$/', $imdbId) !== 1) {
            throw new RuntimeException('Payload is missing a valid IMDb title identifier.');
        }

        return $imdbId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requiredImdbPersonId(array $payload): string
    {
        $imdbId = data_get($payload, 'id');

        if (! is_string($imdbId) || preg_match('/^nm\d+$/', $imdbId) !== 1) {
            throw new RuntimeException('Nested person payload is missing a valid IMDb person identifier.');
        }

        return $imdbId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requiredNonEmptyString(array $payload, string $key): string
    {
        $value = data_get($payload, $key);

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf('Payload is missing [%s].', $key));
        }

        return trim($value);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
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
