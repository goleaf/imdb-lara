<?php

namespace Tests\Concerns;

use App\Enums\TitleType;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\InterestCategory;
use App\Models\Movie;
use App\Models\Person;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Support\Facades\Schema;
use Throwable;

trait InteractsWithRemoteCatalog
{
    private static bool $remoteCatalogAvailabilityChecked = false;

    private static ?string $remoteCatalogAvailabilitySkipReason = null;

    protected function ensureRemoteCatalogAvailable(): void
    {
        if (self::$remoteCatalogAvailabilitySkipReason !== null) {
            $this->markTestSkipped(self::$remoteCatalogAvailabilitySkipReason);
        }

        if (self::$remoteCatalogAvailabilityChecked) {
            return;
        }

        self::$remoteCatalogAvailabilityChecked = true;

        try {
            Title::query()
                ->select(['movies.id'])
                ->publishedCatalog()
                ->limit(1)
                ->value('movies.id');
        } catch (Throwable $throwable) {
            if (! $this->shouldSkipBecauseRemoteCatalogIsUnavailable($throwable)) {
                throw $throwable;
            }

            self::$remoteCatalogAvailabilitySkipReason = sprintf(
                'Remote IMDb MySQL catalog is temporarily unavailable: %s',
                $this->remoteCatalogUnavailableReason($throwable),
            );

            $this->markTestSkipped(self::$remoteCatalogAvailabilitySkipReason);
        }
    }

    protected function shouldSkipBecauseRemoteCatalogIsUnavailable(Throwable $throwable): bool
    {
        $message = $throwable->getMessage();

        return str_contains($message, 'max_connections_per_hour')
            || str_contains($message, 'SQLSTATE[HY000] [1226]')
            || str_contains($message, 'SQLSTATE[HY000] [2002]')
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'php_network_getaddresses')
            || str_contains($message, 'No route to host')
            || str_contains($message, "Table 'imdb.movies' doesn't exist");
    }

    protected function remoteCatalogUnavailableReason(Throwable $throwable): string
    {
        $message = preg_replace('/\s+/', ' ', trim($throwable->getMessage()));

        if (str_contains($message, 'max_connections_per_hour') || str_contains($message, 'SQLSTATE[HY000] [1226]')) {
            return 'the remote MySQL server hit its hourly connection quota';
        }

        if (str_contains($message, 'SQLSTATE[HY000] [2002]')) {
            return 'the remote MySQL connection could not be established';
        }

        return $message;
    }

    protected function markRemoteCatalogUnavailable(Throwable $throwable): never
    {
        self::$remoteCatalogAvailabilitySkipReason ??= sprintf(
            'Remote IMDb MySQL catalog is temporarily unavailable: %s',
            $this->remoteCatalogUnavailableReason($throwable),
        );

        $this->markTestSkipped(self::$remoteCatalogAvailabilitySkipReason);
    }

    protected function shouldSkipBecauseRemoteCatalogTableIsMissing(Throwable $throwable): bool
    {
        $message = $throwable->getMessage();

        return str_contains($message, 'Base table or view not found: 1146')
            && str_contains($message, "Table 'imdb.");
    }

    protected function remoteCatalogMissingTableReason(Throwable $throwable): string
    {
        $message = preg_replace('/\s+/', ' ', trim($throwable->getMessage()));

        if (preg_match("/Table '([^']+)' doesn't exist/", $message, $matches) === 1) {
            return $matches[1];
        }

        return 'an unknown remote table';
    }

    protected function markRemoteCatalogTableMissing(Throwable $throwable): never
    {
        $this->markTestSkipped(sprintf(
            'Remote IMDb MySQL schema does not expose required table [%s].',
            $this->remoteCatalogMissingTableReason($throwable),
        ));
    }

    /**
     * @return list<string>
     */
    private function remoteTitleColumns(): array
    {
        return [
            'movies.id',
            'movies.tconst',
            'movies.imdb_id',
            'movies.primarytitle',
            'movies.originaltitle',
            'movies.titletype',
            'movies.isadult',
            'movies.startyear',
            'movies.endyear',
            'movies.runtimeminutes',
            'movies.title_type_id',
            'movies.runtimeSeconds',
        ];
    }

    /**
     * @return list<string>
     */
    private function remotePersonColumns(): array
    {
        return [
            'name_basics.id',
            'name_basics.nconst',
            'name_basics.imdb_id',
            'name_basics.primaryname',
            'name_basics.displayName',
            'name_basics.primaryprofession',
            'name_basics.birthyear',
            'name_basics.deathyear',
        ];
    }

    /**
     * @return list<string>
     */
    private function remoteSeasonColumns(): array
    {
        return [
            'movie_seasons.movie_id',
            'movie_seasons.season',
            'movie_seasons.episode_count',
        ];
    }

    /**
     * @return list<string>
     */
    private function remoteEpisodeColumns(): array
    {
        return [
            'movie_episodes.episode_movie_id',
            'movie_episodes.movie_id',
            'movie_episodes.season',
            'movie_episodes.episode_number',
            'movie_episodes.release_year',
            'movie_episodes.release_month',
            'movie_episodes.release_day',
        ];
    }

    private function sampleTitle(): Title
    {
        return Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereNotNull('movies.primarytitle')
            ->whereNotNull('movies.startyear')
            ->whereHas('genres')
            ->orderBy('movies.id')
            ->orderByDesc('movies.startyear')
            ->firstOrFail();
    }

    private function sampleSeries(): Title
    {
        $series = Title::query()
            ->select($this->remoteTitleColumns())
            ->published()
            ->forType(TitleType::Series)
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        if ($series instanceof Title) {
            return $series;
        }

        return Title::query()
            ->select($this->remoteTitleColumns())
            ->published()
            ->forType(TitleType::MiniSeries)
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->firstOrFail();
    }

    private function sampleMovie(): Title
    {
        $movie = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereNotIn('movies.titletype', array_merge(
                Title::remoteTypesForCatalogType(TitleType::Series),
                Title::remoteTypesForCatalogType(TitleType::MiniSeries),
            ))
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        return $movie instanceof Title ? $movie : $this->sampleTitle();
    }

    private function samplePerson(): Person
    {
        if (! Title::catalogTablesAvailable('name_basics')) {
            $this->markTestSkipped('Remote IMDb MySQL schema does not expose required table [imdb.name_basics].');
        }

        if (Credit::catalogCreditsAvailable()) {
            $personId = Credit::query()
                ->orderBy('name_basic_id')
                ->value('name_basic_id');

            if (is_numeric($personId)) {
                $person = Person::query()
                    ->select($this->remotePersonColumns())
                    ->published()
                    ->whereKey((int) $personId)
                    ->whereNotNull('name_basics.primaryname')
                    ->first();

                if ($person instanceof Person) {
                    return $person;
                }
            }

            $creditedPerson = Person::query()
                ->select($this->remotePersonColumns())
                ->published()
                ->whereNotNull('name_basics.primaryname')
                ->whereHas('credits')
                ->orderBy('name_basics.id')
                ->first();

            if ($creditedPerson instanceof Person) {
                return $creditedPerson;
            }
        }

        return Person::query()
            ->select($this->remotePersonColumns())
            ->published()
            ->whereNotNull('name_basics.primaryname')
            ->orderBy('name_basics.id')
            ->firstOrFail();
    }

    private function sampleGenre(): Genre
    {
        return Genre::query()
            ->select(['genres.id', 'genres.name'])
            ->whereHas('titles', fn ($query) => $query->publishedCatalog())
            ->orderBy('genres.name')
            ->firstOrFail();
    }

    private function sampleInterestCategory(): InterestCategory
    {
        return InterestCategory::query()
            ->select(['interest_categories.id', 'interest_categories.name'])
            ->whereHas('interests.movies')
            ->withCount([
                'interests',
                'interests as title_linked_interests_count' => fn ($query) => $query->whereHas('movies'),
            ])
            ->orderByDesc('title_linked_interests_count')
            ->orderByDesc('interests_count')
            ->orderBy('interest_categories.name')
            ->firstOrFail();
    }

    private function sampleReleaseYear(): int
    {
        return (int) $this->sampleTitle()->release_year;
    }

    private function sampleTitleWithMedia(): Title
    {
        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->where(function ($query): void {
                $query->whereHas('titleImages')
                    ->orWhereHas('titleVideos')
                    ->orWhereHas('primaryImageRecord');
            })
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        return $title instanceof Title ? $title : $this->sampleTitle();
    }

    private function sampleTitleWithCredits(): Title
    {
        if (! Credit::catalogCreditsAvailable()) {
            return $this->sampleTitle();
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('credits', fn ($query) => $query->whereIn('category', ['actor', 'actress', 'archive_footage', 'self']))
            ->whereHas('credits', fn ($query) => $query->whereNotIn('category', ['actor', 'actress', 'archive_footage', 'self']))
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        return $title instanceof Title ? $title : $this->sampleTitle();
    }

    private function sampleTitleWithBoxOffice(): Title
    {
        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('boxOfficeRecord')
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        return $title instanceof Title ? $title : $this->sampleTitle();
    }

    private function sampleTitleWithReportedBoxOfficeFigures(): Title
    {
        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('boxOfficeRecord', function ($query): void {
                $query->whereNotNull('worldwide_gross_amount')
                    ->orWhereNotNull('domestic_gross_amount')
                    ->orWhereNotNull('opening_weekend_gross_amount')
                    ->orWhereNotNull('production_budget_amount');
            })
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        if ($title instanceof Title) {
            return $title;
        }

        $this->markTestSkipped('The remote catalog does not currently expose a published title with filled box-office figures.');
    }

    private function sampleTitleWithParentsGuide(): Title
    {
        $movieId = Movie::query()
            ->select(['movies.id'])
            ->where(function ($query): void {
                $query->whereHas('movieParentsGuideSections')
                    ->orWhereHas('movieCertificates');
            })
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->value('movies.id');

        $title = is_numeric($movieId)
            ? Title::query()
                ->select($this->remoteTitleColumns())
                ->publishedCatalog()
                ->whereKey((int) $movieId)
                ->first()
            : null;

        return $title instanceof Title ? $title : $this->sampleTitle();
    }

    private function sampleTitleWithInterests(): Title
    {
        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('interests')
            ->whereNotNull('movies.primarytitle')
            ->orderBy('movies.id')
            ->first();

        return $title instanceof Title ? $title : $this->sampleTitle();
    }

    private function sampleTitleWithLocaleMetadata(): Title
    {
        $titles = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('countries')
            ->whereHas('languages')
            ->whereNotNull('movies.primarytitle')
            ->with([
                'countries:code,name',
                'languages:code,name',
            ])
            ->orderBy('movies.id')
            ->limit(250)
            ->get();

        $renderableTitle = $titles->first(function (Title $title): bool {
            $hasRenderableCountry = $title->countries
                ->contains(fn ($country): bool => $this->flagCanRender('country', (string) $country->code));
            $hasRenderableLanguage = $title->languages
                ->contains(fn ($language): bool => $this->flagCanRender('language', (string) $language->code));

            return $hasRenderableCountry && $hasRenderableLanguage;
        });

        if ($renderableTitle instanceof Title) {
            $renderableTitle->setRelation(
                'countries',
                $renderableTitle->countries
                    ->sortBy(fn ($country): int => $this->flagCanRender('country', (string) $country->code) ? 0 : 1)
                    ->values(),
            );
            $renderableTitle->setRelation(
                'languages',
                $renderableTitle->languages
                    ->sortBy(fn ($language): int => $this->flagCanRender('language', (string) $language->code) ? 0 : 1)
                    ->values(),
            );

            return $renderableTitle;
        }

        $title = $titles->first();

        return $title instanceof Title ? $title : $this->sampleTitle();
    }

    private function flagCanRender(string $type, string $code): bool
    {
        $normalizedType = in_array($type, ['country', 'language'], true) ? $type : null;
        $normalizedCode = str($code)
            ->trim()
            ->replace('_', '-')
            ->lower()
            ->replaceMatches('/[^a-z0-9-]+/', '')
            ->trim('-')
            ->toString();

        if ($normalizedType === null || $normalizedCode === '') {
            return false;
        }

        $iconPaths = collect([
            base_path('vendor/outhebox/blade-flags/resources/svg'),
        ]);
        $iconExists = function (string $svgName) use ($iconPaths): bool {
            return $iconPaths->contains(fn (string $path): bool => file_exists($path.'/'.$svgName.'.svg'));
        };

        if ($normalizedType === 'country') {
            return $iconExists('country-'.$normalizedCode);
        }

        $baseLanguageCode = str($normalizedCode)->before('-')->toString();
        $candidateCodes = collect([$normalizedCode]);

        if ($baseLanguageCode !== '' && $baseLanguageCode !== $normalizedCode) {
            $candidateCodes->push($baseLanguageCode);
        }

        $languageCountriesPath = base_path('vendor/outhebox/blade-flags/config/language-countries.json');
        $languageCountries = file_exists($languageCountriesPath)
            ? json_decode((string) file_get_contents($languageCountriesPath), true)
            : [];
        $languageCountryConfig = is_array($languageCountries) ? ($languageCountries[$baseLanguageCode] ?? null) : null;

        if (is_array($languageCountryConfig)) {
            $candidateCodes->push($baseLanguageCode.'-'.($languageCountryConfig['default'] ?? ''));
        }

        return $candidateCodes
            ->filter()
            ->unique()
            ->contains(fn (string $candidateCode): bool => $iconExists('language-'.$candidateCode));
    }

    private function samplePersonWithHeadshot(): Person
    {
        $person = Person::query()
            ->select($this->remotePersonColumns())
            ->published()
            ->where(function ($query): void {
                $query
                    ->whereNotNull('name_basics.primaryImage_url')
                    ->orWhereHas('personImages');
            })
            ->whereNotNull('name_basics.primaryname')
            ->with([
                'personImages:name_basic_id,position,url,width,height,type',
                'professionTerms:id,name',
            ])
            ->orderBy('name_basics.id')
            ->first();

        return $person instanceof Person ? $person : $this->samplePerson();
    }

    /**
     * @return array{series: Title, season: Season, episode: Title, episodeMeta: Episode}|null
     */
    private function sampleSeriesHierarchy(): ?array
    {
        if (! Schema::hasTable('episodes') || ! Schema::hasTable('seasons')) {
            return null;
        }

        $episodeMeta = Episode::query()
            ->select($this->remoteEpisodeColumns())
            ->whereHas('series', fn ($query) => $query
                ->published()
                ->whereNotNull('movies.primarytitle'))
            ->whereHas('title', fn ($query) => $query
                ->published()
                ->whereNotNull('movies.primarytitle'))
            ->whereHas('title.credits', fn ($query) => $query->whereIn('category', ['actor', 'actress', 'archive_footage', 'self']))
            ->whereHas('title.credits', fn ($query) => $query->whereNotIn('category', ['actor', 'actress', 'archive_footage', 'self']))
            ->with([
                'series' => fn ($query) => $query
                    ->select($this->remoteTitleColumns())
                    ->published()
                    ->whereNotNull('movies.primarytitle'),
                'title' => fn ($query) => $query
                    ->select($this->remoteTitleColumns())
                    ->published()
                    ->whereNotNull('movies.primarytitle'),
            ])
            ->orderBy('movie_episodes.movie_id')
            ->orderBy('movie_episodes.season')
            ->orderBy('movie_episodes.episode_number')
            ->first();

        if (! $episodeMeta instanceof Episode) {
            return null;
        }

        if (! $episodeMeta->series instanceof Title || ! $episodeMeta->title instanceof Title) {
            return null;
        }

        $season = Season::query()
            ->select($this->remoteSeasonColumns())
            ->where('movie_seasons.movie_id', $episodeMeta->series_id)
            ->where('movie_seasons.season', $episodeMeta->season_number)
            ->first();

        if (! $season instanceof Season) {
            return null;
        }

        return [
            'series' => $episodeMeta->series,
            'season' => $season,
            'episode' => $episodeMeta->title,
            'episodeMeta' => $episodeMeta,
        ];
    }

    private function searchTermFor(Title $title): string
    {
        return $title->tconst ?: $title->name;
    }

    private function personSearchTermFor(Person $person): string
    {
        return $person->nconst ?: $person->name;
    }
}
