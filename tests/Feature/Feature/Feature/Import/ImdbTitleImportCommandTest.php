<?php

namespace Tests\Feature\Feature\Feature\Import;

use App\Models\AwardNomination;
use App\Models\Company;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use App\Models\TitleStatistic;
use App\Models\TitleTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImdbTitleImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_command_normalizes_bundle_payload_into_catalog_tables_and_updates_existing_records(): void
    {
        $directory = storage_path('framework/testing/imdb-import');
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.'tt7654321.json';
        File::put($path, json_encode($this->seriesBundle(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->importImdbTitlePayloadFromPath($path);

        $title = Title::query()->where('imdb_id', 'tt7654321')->firstOrFail();
        $titleStatistic = TitleStatistic::query()->where('title_id', $title->id)->firstOrFail();
        $lead = Person::query()->where('imdb_id', 'nm1000001')->firstOrFail();

        $this->assertSame('series', $title->title_type->value);
        $this->assertSame('TV-14', $title->age_rating);
        $this->assertSame('2021-05-04', $title->release_date?->toDateString());
        $this->assertSame(['Drama', 'Sci-Fi'], $title->imdb_genres);
        $this->assertSame(1, data_get($title->imdb_payload, 'storageVersion'));
        $this->assertSame('Neon futures and speculative noir.', data_get($title->imdb_payload, 'interests.in0000159.description'));
        $this->assertNull(data_get($title->imdb_payload, 'credits'));
        $this->assertTrue(TitleTranslation::query()->where('title_id', $title->id)->where('locale', 'uk-UA')->exists());
        $this->assertSame(1, Season::query()->where('series_id', $title->id)->count());
        $this->assertSame(1, Episode::query()->where('series_id', $title->id)->count());
        $this->assertTrue(Title::query()->where('imdb_id', 'tt7654322')->exists());
        $this->assertSame(1, $titleStatistic->episodes_count);
        $this->assertSame(1, $titleStatistic->awards_won_count);
        $this->assertTrue(Credit::query()->where('title_id', $title->id)->where('imdb_source_group', 'imdb:actor')->where('character_name', 'Mara Vale')->exists());
        $this->assertSame('Ava Stone is an actor from Seattle.', $lead->biography);
        $this->assertNull($lead->alternate_names);
        $this->assertSame(['A. Stone'], $lead->imdb_alternative_names);
        $this->assertSame(1, data_get($lead->imdb_payload, 'storageVersion'));
        $this->assertSame('Won stage awards.', data_get($lead->imdb_payload, 'trivia.triviaEntries.0.text'));
        $this->assertSame('https://example.com/ava-stone-gallery.jpg', data_get($lead->imdb_payload, 'images.images.0.url'));
        $this->assertTrue(MediaAsset::query()->where('mediable_type', Title::class)->where('mediable_id', $title->id)->where('kind', 'trailer')->exists());
        $this->assertTrue(MediaAsset::query()->where('mediable_type', Person::class)->where('mediable_id', $lead->id)->where('kind', 'headshot')->exists());
        $this->assertTrue(Company::query()->where('slug', 'co123-harbor-network')->exists());
        $this->assertTrue(AwardNomination::query()->where('title_id', $title->id)->where('person_id', $lead->id)->exists());
        $this->assertDatabaseHas('imdb_title_imports', [
            'imdb_id' => 'tt7654321',
        ]);

        File::put($path, json_encode($this->seriesBundle([
            'title.plot' => 'Neon Harbor pivots after a citywide blackout.',
            'title.rating.aggregateRating' => 8.8,
            'episodes.episodes.0.rating.aggregateRating' => 8.4,
        ]), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->importImdbTitlePayloadFromPath($path);

        $title->refresh();
        $titleStatistic->refresh();

        $this->assertSame('Neon Harbor pivots after a citywide blackout.', $title->plot_outline);
        $this->assertSame('8.80', $titleStatistic->average_rating);
        $this->assertSame(1, Title::query()->where('imdb_id', 'tt7654321')->count());
        $this->assertSame(1, Episode::query()->where('series_id', $title->id)->count());
    }

    public function test_import_command_remains_backward_compatible_with_legacy_single_title_payloads(): void
    {
        $directory = storage_path('framework/testing/imdb-import-legacy');
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.'tt0133093.json';
        File::put($path, json_encode($this->legacyMatrixPayload(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->importImdbTitlePayloadFromPath($path);

        $title = Title::query()->where('imdb_id', 'tt0133093')->firstOrFail();

        $this->assertSame('The Matrix', $title->name);
        $this->assertSame('movie', $title->title_type->value);
        $this->assertTrue(Credit::query()->where('title_id', $title->id)->where('imdb_source_group', 'directors')->exists());
    }

    #[DataProvider('titleEndpointReportProvider')]
    public function test_import_command_writes_a_separate_endpoint_report_for_each_title_endpoint(
        string $endpoint,
        string $artifactPath,
        ?string $expectedAddedField,
        ?string $expectedRelationKey,
    ): void {
        $directory = storage_path('framework/testing/imdb-import-reports');
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.'tt7654321.json';
        File::put($path, json_encode($this->seriesBundle(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->importImdbTitlePayloadFromPath($path);

        $report = $this->decodeJson(
            $directory.DIRECTORY_SEPARATOR.'tt7654321'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.$endpoint.'.json'
        );

        $this->assertSame($endpoint, data_get($report, 'endpoint'));
        $this->assertSame($artifactPath, data_get($report, 'artifact_path'));
        $this->assertSame('tt7654321', data_get($report, 'imdb_id'));
        $this->assertTrue((bool) data_get($report, 'has_payload'));

        if ($expectedAddedField !== null) {
            $this->assertContains($expectedAddedField, data_get($report, 'added_fields', []));
        }

        if ($expectedRelationKey !== null) {
            $this->assertArrayHasKey($expectedRelationKey, data_get($report, 'added_relations', []));
            $this->assertNotEmpty(data_get($report, 'added_relations.'.$expectedRelationKey, []));
        }
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string|null, 3: string|null}>
     */
    public static function titleEndpointReportProvider(): array
    {
        return [
            'title' => ['title', 'title.json', 'Name', 'genres'],
            'credits' => ['credits', 'credits.json', null, 'credits'],
            'releaseDates' => ['releaseDates', 'release-dates.json', 'Release date', 'payload_sections'],
            'akas' => ['akas', 'aka-titles.json', null, 'translations'],
            'seasons' => ['seasons', 'seasons.json', null, 'seasons'],
            'episodes' => ['episodes', 'episodes.json', null, 'episodes'],
            'images' => ['images', 'images.json', null, 'media_assets'],
            'videos' => ['videos', 'videos.json', null, 'media_assets'],
            'awardNominations' => ['awardNominations', 'award-nominations.json', 'Awards won count', 'awards'],
            'parentsGuide' => ['parentsGuide', 'parents-guide.json', null, 'payload_sections'],
            'certificates' => ['certificates', 'certificates.json', 'Age rating', 'payload_sections'],
            'companyCredits' => ['companyCredits', 'company-credits.json', null, 'companies'],
            'boxOffice' => ['boxOffice', 'box-office.json', null, 'payload_sections'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function seriesBundle(array $overrides = []): array
    {
        $bundle = [
            'schemaVersion' => 3,
            'imdbId' => 'tt7654321',
            'sourceUrl' => 'https://api.imdbapi.dev/titles/tt7654321',
            'title' => [
                'id' => 'tt7654321',
                'type' => 'tvSeries',
                'primaryTitle' => 'Neon Harbor',
                'primaryImage' => ['url' => 'https://example.com/neon-harbor.jpg', 'width' => 1800, 'height' => 2700],
                'startYear' => 2021,
                'runtimeSeconds' => 3600,
                'genres' => ['Drama', 'Sci-Fi'],
                'rating' => ['aggregateRating' => 8.6, 'voteCount' => 43000],
                'metacritic' => ['score' => 74, 'reviewCount' => 18],
                'plot' => 'A freight hub on the edge of the Pacific turns into a battleground for rival futures.',
                'originCountries' => [['code' => 'US', 'name' => 'United States']],
                'spokenLanguages' => [['code' => 'eng', 'name' => 'English']],
                'interests' => [['id' => 'in0000159', 'name' => 'Cyberpunk']],
            ],
            'credits' => [
                'credits' => [
                    ['name' => ['id' => 'nm1000001', 'displayName' => 'Ava Stone', 'primaryProfessions' => ['actor']], 'category' => 'actor', 'characters' => ['Mara Vale']],
                    ['name' => ['id' => 'nm1000002', 'displayName' => 'Noah Flint', 'primaryProfessions' => ['director']], 'category' => 'director'],
                ],
            ],
            'releaseDates' => ['releaseDates' => [['country' => ['code' => 'US', 'name' => 'United States'], 'releaseDate' => ['year' => 2021, 'month' => 5, 'day' => 4]]]],
            'akas' => ['akas' => [['text' => 'Неонова гавань', 'country' => ['code' => 'UA', 'name' => 'Ukraine'], 'language' => ['code' => 'uk', 'name' => 'Ukrainian']]]],
            'seasons' => ['seasons' => [['season' => '1', 'episodeCount' => 1]]],
            'episodes' => ['episodes' => [['id' => 'tt7654322', 'title' => 'Pilot Light', 'primaryImage' => ['url' => 'https://example.com/pilot-light.jpg', 'width' => 1600, 'height' => 900], 'season' => '1', 'episodeNumber' => 1, 'runtimeSeconds' => 3300, 'plot' => 'The first night shift uncovers a sabotaged cargo chain.', 'rating' => ['aggregateRating' => 8.1, 'voteCount' => 1200], 'releaseDate' => ['year' => 2021, 'month' => 5, 'day' => 11]]]],
            'images' => ['images' => [['url' => 'https://example.com/neon-harbor-still.jpg', 'width' => 1920, 'height' => 1080, 'type' => 'still_frame']]],
            'videos' => ['videos' => [['id' => 'vi1000001', 'type' => 'trailer', 'name' => 'Official Trailer', 'description' => 'Official Trailer', 'width' => 1920, 'height' => 1080, 'runtimeSeconds' => 90]]],
            'awardNominations' => ['stats' => ['nominationCount' => 1, 'winCount' => 1], 'awardNominations' => [['event' => ['id' => 'ev123', 'name' => 'Nebula Screen Awards'], 'year' => 2022, 'text' => 'Nebula Prize', 'category' => 'Best Performance', 'isWinner' => true, 'winnerRank' => 1, 'nominees' => [['id' => 'nm1000001', 'displayName' => 'Ava Stone', 'primaryProfessions' => ['actor']]]]]],
            'parentsGuide' => ['advisories' => []],
            'certificates' => ['certificates' => [['rating' => 'TV-14', 'country' => ['code' => 'US', 'name' => 'United States']]]],
            'companyCredits' => ['companyCredits' => [['company' => ['id' => 'co123', 'name' => 'Harbor Network'], 'category' => 'distribution', 'countries' => [['code' => 'US', 'name' => 'United States']], 'attributes' => ['streaming']]]],
            'boxOffice' => ['domesticGross' => ['amount' => '100000', 'currency' => 'USD']],
            'interests' => [
                'in0000159' => [
                    'id' => 'in0000159',
                    'name' => 'Cyberpunk',
                    'description' => 'Neon futures and speculative noir.',
                    'isSubgenre' => true,
                ],
            ],
            'names' => [
                'nm1000001' => [
                    'details' => ['id' => 'nm1000001', 'displayName' => 'Ava Stone', 'alternativeNames' => ['A. Stone'], 'primaryImage' => ['url' => 'https://example.com/ava-stone.jpg', 'width' => 1200, 'height' => 1800], 'primaryProfessions' => ['actor'], 'biography' => 'Ava Stone is an actor from Seattle.', 'birthDate' => ['year' => 1988, 'month' => 3, 'day' => 2], 'birthLocation' => 'Seattle, Washington, USA'],
                    'images' => ['images' => [['url' => 'https://example.com/ava-stone-gallery.jpg', 'width' => 1000, 'height' => 1500, 'type' => 'poster']]],
                    'relationships' => ['relationships' => []],
                    'trivia' => ['triviaEntries' => [['id' => 'nt1', 'text' => 'Won stage awards.', 'interestCount' => 1, 'voteCount' => 1]]],
                ],
                'nm1000002' => [
                    'details' => ['id' => 'nm1000002', 'displayName' => 'Noah Flint', 'primaryProfessions' => ['director'], 'biography' => 'Noah Flint directs speculative dramas.'],
                    'images' => ['images' => []],
                    'relationships' => ['relationships' => []],
                    'trivia' => ['triviaEntries' => []],
                ],
            ],
        ];

        foreach ($overrides as $path => $value) {
            data_set($bundle, $path, $value);
        }

        return $bundle;
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyMatrixPayload(): array
    {
        return [
            'id' => 'tt0133093',
            'type' => 'movie',
            'primaryTitle' => 'The Matrix',
            'primaryImage' => ['url' => 'https://example.com/matrix.jpg', 'width' => 2100, 'height' => 3156],
            'startYear' => 1999,
            'runtimeSeconds' => 8160,
            'genres' => ['Action', 'Sci-Fi'],
            'rating' => ['aggregateRating' => 8.7, 'voteCount' => 2237344],
            'metacritic' => ['score' => 73, 'reviewCount' => 36],
            'plot' => 'A computer hacker learns the world is a simulation.',
            'directors' => [['id' => 'nm0905154', 'displayName' => 'Lana Wachowski', 'primaryProfessions' => ['director']]],
            'writers' => [['id' => 'nm0905152', 'displayName' => 'Lilly Wachowski', 'primaryProfessions' => ['writer']]],
            'stars' => [['id' => 'nm0000206', 'displayName' => 'Keanu Reeves', 'primaryProfessions' => ['actor']]],
            'originCountries' => [['code' => 'US', 'name' => 'United States']],
            'spokenLanguages' => [['code' => 'eng', 'name' => 'English']],
            'interests' => [['id' => 'in0000001', 'name' => 'Action']],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $path): array
    {
        $decoded = json_decode((string) File::get($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);

        return $decoded;
    }
}
