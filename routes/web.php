<?php

use App\Actions\Admin\DeleteEpisodeAction;
use App\Actions\Admin\DeletePersonAction;
use App\Actions\Admin\DeleteSeasonAction;
use App\Actions\Admin\DeleteTitleAction;
use App\Actions\Admin\SaveCreditAction;
use App\Actions\Admin\SaveEpisodeAction;
use App\Actions\Admin\SaveGenreAction;
use App\Actions\Admin\SaveMediaAssetAction;
use App\Actions\Admin\SavePersonAction;
use App\Actions\Admin\SaveSeasonAction;
use App\Actions\Admin\StoreTitleAction;
use App\Actions\Admin\UpdateContributionStatusAction;
use App\Actions\Admin\UpdateReportStatusAction;
use App\Actions\Admin\UpdateTitleAction;
use App\Actions\Moderation\ModerateReviewAction;
use App\Actions\Pages\RenderPageComponentAction;
use App\Actions\Seo\GetSitemapDataAction;
use App\Enums\ReviewStatus;
use App\Enums\TitleType;
use App\Http\Requests\Admin\StoreCreditRequest;
use App\Http\Requests\Admin\StoreEpisodeRequest;
use App\Http\Requests\Admin\StoreGenreRequest;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Http\Requests\Admin\StorePersonProfessionRequest;
use App\Http\Requests\Admin\StorePersonRequest;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\StoreTitleRequest;
use App\Http\Requests\Admin\UpdateContributionRequest;
use App\Http\Requests\Admin\UpdateCreditRequest;
use App\Http\Requests\Admin\UpdateEpisodeRequest;
use App\Http\Requests\Admin\UpdateGenreRequest;
use App\Http\Requests\Admin\UpdateMediaAssetRequest;
use App\Http\Requests\Admin\UpdatePersonProfessionRequest;
use App\Http\Requests\Admin\UpdatePersonRequest;
use App\Http\Requests\Admin\UpdateReportRequest;
use App\Http\Requests\Admin\UpdateReviewModerationRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Http\Requests\Admin\UpdateTitleRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Livewire\Pages\Account\DashboardPage as AccountDashboardPage;
use App\Livewire\Pages\Account\ListsPage as AccountListsPage;
use App\Livewire\Pages\Account\WatchlistPage as AccountWatchlistPage;
use App\Livewire\Pages\Admin\ContributionsPage as AdminContributionsPage;
use App\Livewire\Pages\Admin\CreditsPage as AdminCreditsPage;
use App\Livewire\Pages\Admin\DashboardPage as AdminDashboardPage;
use App\Livewire\Pages\Admin\EpisodesPage as AdminEpisodesPage;
use App\Livewire\Pages\Admin\GenresPage as AdminGenresPage;
use App\Livewire\Pages\Admin\MediaAssetsPage as AdminMediaAssetsPage;
use App\Livewire\Pages\Admin\PeoplePage as AdminPeoplePage;
use App\Livewire\Pages\Admin\ReportsPage as AdminReportsPage;
use App\Livewire\Pages\Admin\ReviewsPage as AdminReviewsPage;
use App\Livewire\Pages\Admin\SeasonsPage as AdminSeasonsPage;
use App\Livewire\Pages\Admin\TitlesPage as AdminTitlesPage;
use App\Livewire\Pages\Auth\AuthPage;
use App\Livewire\Pages\Public\BrowseTitlesPage;
use App\Livewire\Pages\Public\DiscoverPage;
use App\Livewire\Pages\Public\EpisodeShowPage;
use App\Livewire\Pages\Public\HomePage;
use App\Livewire\Pages\Public\LatestReviewsPage;
use App\Livewire\Pages\Public\LatestTrailersPage;
use App\Livewire\Pages\Public\PeoplePage;
use App\Livewire\Pages\Public\SearchPage;
use App\Livewire\Pages\Public\SeasonShowPage;
use App\Livewire\Pages\Public\TitlePage;
use App\Livewire\Pages\Public\UserPage;
use App\Models\Contribution;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Report;
use App\Models\Review;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', function (GetSitemapDataAction $getSitemapData): Response {
    return response()
        ->view('seo.sitemap', $getSitemapData->handle())
        ->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::get('/robots.txt', fn () => response(
    "User-agent: *\nAllow: /\n\nSitemap: ".route('sitemap')."\n",
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8'],
))->name('robots');
Route::name('public.')->group(function (): void {
    Route::get('/', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(HomePage::class))->name('home');
    Route::get('/discover', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(DiscoverPage::class))->name('discover');
    Route::get('/movies', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(BrowseTitlesPage::class))->name('movies.index');
    Route::get('/tv-shows', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(BrowseTitlesPage::class))->name('series.index');
    Route::get('/titles', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(BrowseTitlesPage::class))->name('titles.index');
    Route::get('/titles/{title:slug}/cast', fn (RenderPageComponentAction $renderPage, Title $title): Response => $renderPage->handle(TitlePage::class, ['title' => $title]))->name('titles.cast');
    Route::get('/titles/{title:slug}', function (RenderPageComponentAction $renderPage, Title $title): Response|RedirectResponse {
        if ($title->title_type === TitleType::Episode) {
            $title->loadMissing('episodeMeta.season:id,series_id,slug', 'episodeMeta.series:id,slug');

            if ($title->episodeMeta?->season && $title->episodeMeta?->series) {
                return redirect()->route('public.episodes.show', [
                    'series' => $title->episodeMeta->series,
                    'season' => $title->episodeMeta->season,
                    'episode' => $title,
                ]);
            }

            abort(404);
        }

        return $renderPage->handle(TitlePage::class, ['title' => $title]);
    })->name('titles.show');
    Route::get('/people', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(PeoplePage::class))->name('people.index');
    Route::get('/people/{person:slug}', fn (RenderPageComponentAction $renderPage, Person $person): Response => $renderPage->handle(PeoplePage::class, ['person' => $person]))->name('people.show');
    Route::get('/genres/{genre:slug}', fn (RenderPageComponentAction $renderPage, Genre $genre): Response => $renderPage->handle(BrowseTitlesPage::class, ['genre' => $genre]))->name('genres.show');
    Route::get('/years/{year}', fn (RenderPageComponentAction $renderPage, int $year): Response => $renderPage->handle(BrowseTitlesPage::class, ['year' => $year]))->whereNumber('year')->name('years.show');
    Route::get('/top-rated/movies', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(BrowseTitlesPage::class))->name('rankings.movies');
    Route::get('/top-rated/series', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(BrowseTitlesPage::class))->name('rankings.series');
    Route::get('/trending', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(BrowseTitlesPage::class))->name('trending');
    Route::get('/trailers/latest', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(LatestTrailersPage::class))->name('trailers.latest');
    Route::get('/reviews/latest', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(LatestReviewsPage::class))->name('reviews.latest');
    Route::get('/search', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(SearchPage::class))->name('search');
    Route::get('/u/{user:username}', fn (RenderPageComponentAction $renderPage, User $user): Response => $renderPage->handle(UserPage::class, ['user' => $user]))->name('users.show');

    Route::withoutScopedBindings()->group(function (): void {
        Route::get('/series/{series:slug}/seasons/{season:slug}', fn (RenderPageComponentAction $renderPage, Title $series, Season $season): Response => $renderPage->handle(SeasonShowPage::class, ['series' => $series, 'season' => $season]))->name('seasons.show');
        Route::get('/series/{series:slug}/seasons/{season:slug}/episodes/{episode:slug}', fn (RenderPageComponentAction $renderPage, Title $series, Season $season, Title $episode): Response => $renderPage->handle(EpisodeShowPage::class, ['series' => $series, 'season' => $season, 'episode' => $episode]))->name('episodes.show');
    });

    Route::scopeBindings()->group(function (): void {
        Route::get('/u/{user:username}/lists/{list:slug}', fn (RenderPageComponentAction $renderPage, User $user, UserList $list): Response => $renderPage->handle(UserPage::class, ['user' => $user, 'list' => $list]))->name('lists.show');
    });
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(AuthPage::class))->name('login');
    Route::get('/register', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(AuthPage::class))->name('register');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', function (LogoutRequest $request): RedirectResponse {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.home');
    })->name('logout');

    Route::prefix('account')->name('account.')->group(function (): void {
        Route::get('/', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(AccountDashboardPage::class))->name('dashboard');
        Route::get('/watchlist', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(AccountWatchlistPage::class))->name('watchlist');
        Route::get('/lists', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(AccountListsPage::class))->name('lists.index');
        Route::get('/lists/{list}', fn (RenderPageComponentAction $renderPage): Response => $renderPage->handle(AccountListsPage::class))->name('lists.show');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'admin'])->group(function (): void {
    Route::livewire('/', AdminDashboardPage::class)->name('dashboard');

    Route::prefix('titles')->name('titles.')->group(function (): void {
        Route::livewire('/', AdminTitlesPage::class)
            ->can('viewAny', Title::class)
            ->name('index');
        Route::livewire('/create', AdminTitlesPage::class)
            ->can('create', Title::class)
            ->name('create');
        Route::post('/', function (
            StoreTitleRequest $request,
            StoreTitleAction $storeTitle,
        ): RedirectResponse {
            $title = $storeTitle->handle($request->validated());

            return redirect()
                ->route('admin.titles.edit', $title)
                ->with('status', 'Title created.');
        })->can('create', Title::class)->name('store');
        Route::livewire('/{title:slug}/edit', AdminTitlesPage::class)
            ->can('update', 'title')
            ->name('edit');
        Route::patch('/{title:slug}', function (
            UpdateTitleRequest $request,
            Title $title,
            UpdateTitleAction $updateTitle,
        ): RedirectResponse {
            $updatedTitle = $updateTitle->handle($title, $request->validated());

            return redirect()
                ->route('admin.titles.edit', $updatedTitle)
                ->with('status', 'Title updated.');
        })->can('update', 'title')->name('update');
        Route::delete('/{title:slug}', function (
            Title $title,
            DeleteTitleAction $deleteTitle,
        ): RedirectResponse {
            $deleteTitle->handle($title);

            return redirect()
                ->route('admin.titles.index')
                ->with('status', 'Title deleted.');
        })->can('delete', 'title')->name('destroy');
        Route::post('/{title:slug}/seasons', function (
            StoreSeasonRequest $request,
            Title $title,
            SaveSeasonAction $saveSeason,
        ): RedirectResponse {
            $season = $saveSeason->handle(new Season, $title, $request->validated());

            return redirect()
                ->route('admin.seasons.edit', $season)
                ->with('status', 'Season created.');
        })->can('update', 'title')->name('seasons.store');
        Route::post('/{title:slug}/media-assets', function (
            StoreMediaAssetRequest $request,
            Title $title,
            SaveMediaAssetAction $saveMediaAsset,
        ): RedirectResponse {
            $saveMediaAsset->handle(new MediaAsset, $title, $request->validated());

            return redirect()
                ->route('admin.titles.edit', $title)
                ->with('status', 'Media asset created.');
        })->can('update', 'title')->name('media-assets.store');
    });

    Route::prefix('people')->name('people.')->group(function (): void {
        Route::livewire('/', AdminPeoplePage::class)
            ->can('viewAny', Person::class)
            ->name('index');
        Route::livewire('/create', AdminPeoplePage::class)
            ->can('create', Person::class)
            ->name('create');
        Route::post('/', function (
            StorePersonRequest $request,
            SavePersonAction $savePerson,
        ): RedirectResponse {
            $person = $savePerson->handle(new Person, $request->validated());

            return redirect()
                ->route('admin.people.edit', $person)
                ->with('status', 'Person created.');
        })->can('create', Person::class)->name('store');
        Route::livewire('/{person:slug}/edit', AdminPeoplePage::class)
            ->can('update', 'person')
            ->name('edit');
        Route::patch('/{person:slug}', function (
            UpdatePersonRequest $request,
            Person $person,
            SavePersonAction $savePerson,
        ): RedirectResponse {
            $updatedPerson = $savePerson->handle($person, $request->validated());

            return redirect()
                ->route('admin.people.edit', $updatedPerson)
                ->with('status', 'Person updated.');
        })->can('update', 'person')->name('update');
        Route::delete('/{person:slug}', function (
            Person $person,
            DeletePersonAction $deletePerson,
        ): RedirectResponse {
            $deletePerson->handle($person);

            return redirect()
                ->route('admin.people.index')
                ->with('status', 'Person deleted.');
        })->can('delete', 'person')->name('destroy');
        Route::post('/{person:slug}/professions', function (
            StorePersonProfessionRequest $request,
            Person $person,
        ): RedirectResponse {
            $person->professions()->create([
                ...$request->validated(),
                'is_primary' => (bool) $request->validated('is_primary'),
                'sort_order' => $request->validated('sort_order') ?? 0,
            ]);

            return redirect()
                ->route('admin.people.edit', $person)
                ->with('status', 'Profession added.');
        })->can('update', 'person')->name('professions.store');
        Route::post('/{person:slug}/media-assets', function (
            StoreMediaAssetRequest $request,
            Person $person,
            SaveMediaAssetAction $saveMediaAsset,
        ): RedirectResponse {
            $saveMediaAsset->handle(new MediaAsset, $person, $request->validated());

            return redirect()
                ->route('admin.people.edit', $person)
                ->with('status', 'Media asset created.');
        })->can('update', 'person')->name('media-assets.store');
    });

    Route::prefix('genres')->name('genres.')->group(function (): void {
        Route::livewire('/', AdminGenresPage::class)
            ->can('viewAny', Genre::class)
            ->name('index');
        Route::livewire('/create', AdminGenresPage::class)
            ->can('create', Genre::class)
            ->name('create');
        Route::post('/', function (
            StoreGenreRequest $request,
            SaveGenreAction $saveGenre,
        ): RedirectResponse {
            $genre = $saveGenre->handle(new Genre, $request->validated());

            return redirect()
                ->route('admin.genres.edit', $genre)
                ->with('status', 'Genre created.');
        })->can('create', Genre::class)->name('store');
        Route::livewire('/{genre}/edit', AdminGenresPage::class)
            ->can('update', 'genre')
            ->name('edit');
        Route::patch('/{genre}', function (
            UpdateGenreRequest $request,
            Genre $genre,
            SaveGenreAction $saveGenre,
        ): RedirectResponse {
            $updatedGenre = $saveGenre->handle($genre, $request->validated());

            return redirect()
                ->route('admin.genres.edit', $updatedGenre)
                ->with('status', 'Genre updated.');
        })->can('update', 'genre')->name('update');
        Route::delete('/{genre}', function (Genre $genre): RedirectResponse {
            $genre->delete();

            return redirect()
                ->route('admin.genres.index')
                ->with('status', 'Genre deleted.');
        })->can('delete', 'genre')->name('destroy');
    });

    Route::prefix('credits')->name('credits.')->group(function (): void {
        Route::livewire('/create', AdminCreditsPage::class)
            ->can('create', Credit::class)
            ->name('create');
        Route::post('/', function (
            StoreCreditRequest $request,
            SaveCreditAction $saveCredit,
        ): RedirectResponse {
            $credit = $saveCredit->handle(new Credit, $request->validated());

            return redirect()
                ->route('admin.titles.edit', $credit->title)
                ->with('status', 'Credit created.');
        })->can('create', Credit::class)->name('store');
        Route::livewire('/{credit}/edit', AdminCreditsPage::class)
            ->can('update', 'credit')
            ->name('edit');
        Route::patch('/{credit}', function (
            UpdateCreditRequest $request,
            Credit $credit,
            SaveCreditAction $saveCredit,
        ): RedirectResponse {
            $updatedCredit = $saveCredit->handle($credit, $request->validated());

            return redirect()
                ->route('admin.credits.edit', $updatedCredit)
                ->with('status', 'Credit updated.');
        })->can('update', 'credit')->name('update');
        Route::delete('/{credit}', function (Credit $credit): RedirectResponse {
            $redirectTitle = $credit->title;
            $credit->delete();

            return redirect()
                ->route('admin.titles.edit', $redirectTitle)
                ->with('status', 'Credit deleted.');
        })->can('delete', 'credit')->name('destroy');
    });

    Route::prefix('seasons')->name('seasons.')->group(function (): void {
        Route::livewire('/{season}/edit', AdminSeasonsPage::class)
            ->can('update', 'season')
            ->name('edit');
        Route::patch('/{season}', function (
            UpdateSeasonRequest $request,
            Season $season,
            SaveSeasonAction $saveSeason,
        ): RedirectResponse {
            $updatedSeason = $saveSeason->handle($season, $season->series, $request->validated());

            return redirect()
                ->route('admin.seasons.edit', $updatedSeason)
                ->with('status', 'Season updated.');
        })->can('update', 'season')->name('update');
        Route::delete('/{season}', function (
            Season $season,
            DeleteSeasonAction $deleteSeason,
        ): RedirectResponse {
            $series = $season->series;
            $deleteSeason->handle($season);

            return redirect()
                ->route('admin.titles.edit', $series)
                ->with('status', 'Season deleted.');
        })->can('delete', 'season')->name('destroy');
        Route::post('/{season}/episodes', function (
            StoreEpisodeRequest $request,
            Season $season,
            SaveEpisodeAction $saveEpisode,
        ): RedirectResponse {
            $episode = $saveEpisode->handle(new Episode, $season, $request->validated());

            return redirect()
                ->route('admin.episodes.edit', $episode)
                ->with('status', 'Episode created.');
        })->can('update', 'season')->name('episodes.store');
    });

    Route::prefix('episodes')->name('episodes.')->group(function (): void {
        Route::livewire('/{episode}/edit', AdminEpisodesPage::class)
            ->can('update', 'episode')
            ->name('edit');
        Route::patch('/{episode}', function (
            UpdateEpisodeRequest $request,
            Episode $episode,
            SaveEpisodeAction $saveEpisode,
        ): RedirectResponse {
            $updatedEpisode = $saveEpisode->handle($episode, $episode->season, $request->validated());

            return redirect()
                ->route('admin.episodes.edit', $updatedEpisode)
                ->with('status', 'Episode updated.');
        })->can('update', 'episode')->name('update');
        Route::delete('/{episode}', function (
            Episode $episode,
            DeleteEpisodeAction $deleteEpisode,
        ): RedirectResponse {
            $season = $episode->season;
            $deleteEpisode->handle($episode);

            return redirect()
                ->route('admin.seasons.edit', $season)
                ->with('status', 'Episode deleted.');
        })->can('delete', 'episode')->name('destroy');
    });

    Route::prefix('media-assets')->name('media-assets.')->group(function (): void {
        Route::livewire('/', AdminMediaAssetsPage::class)
            ->can('viewAny', MediaAsset::class)
            ->name('index');
        Route::livewire('/{mediaAsset}/edit', AdminMediaAssetsPage::class)
            ->can('update', 'mediaAsset')
            ->name('edit');
        Route::patch('/{mediaAsset}', function (
            UpdateMediaAssetRequest $request,
            MediaAsset $mediaAsset,
            SaveMediaAssetAction $saveMediaAsset,
        ): RedirectResponse {
            $updatedMediaAsset = $saveMediaAsset->handle(
                $mediaAsset,
                $mediaAsset->mediable,
                $request->validated(),
            );

            return redirect()
                ->route('admin.media-assets.edit', $updatedMediaAsset)
                ->with('status', 'Media asset updated.');
        })->can('update', 'mediaAsset')->name('update');
        Route::delete('/{mediaAsset}', function (MediaAsset $mediaAsset): RedirectResponse {
            $mediaAsset->delete();

            return redirect()
                ->back()
                ->with('status', 'Media asset deleted.');
        })->can('delete', 'mediaAsset')->name('destroy');
    });

    Route::patch('/professions/{profession}', function (
        UpdatePersonProfessionRequest $request,
        PersonProfession $profession,
    ): RedirectResponse {
        $profession->fill([
            ...$request->validated(),
            'is_primary' => (bool) $request->validated('is_primary'),
            'sort_order' => $request->validated('sort_order') ?? 0,
        ]);
        $profession->save();

        return redirect()
            ->route('admin.people.edit', $profession->person)
            ->with('status', 'Profession updated.');
    })->can('update', 'profession')->name('professions.update');

    Route::delete('/professions/{profession}', function (PersonProfession $profession): RedirectResponse {
        $person = $profession->person;
        $profession->delete();

        return redirect()
            ->route('admin.people.edit', $person)
            ->with('status', 'Profession deleted.');
    })->can('update', 'profession')->name('professions.destroy');

    Route::livewire('/contributions', AdminContributionsPage::class)
        ->can('viewAny', Contribution::class)
        ->name('contributions.index');
    Route::patch('/contributions/{contribution}', function (
        UpdateContributionRequest $request,
        Contribution $contribution,
        UpdateContributionStatusAction $updateContributionStatus,
    ): RedirectResponse {
        $updateContributionStatus->handle($request->user(), $contribution, $request->validated());

        return redirect()
            ->route('admin.contributions.index')
            ->with('status', 'Contribution updated.');
    })->can('update', 'contribution')->name('contributions.update');

    Route::middleware('moderate')->group(function (): void {
        Route::livewire('/reviews', AdminReviewsPage::class)
            ->can('viewAny', Review::class)
            ->name('reviews.index');
        Route::patch('/reviews/{review}', function (
            UpdateReviewModerationRequest $request,
            Review $review,
            ModerateReviewAction $moderateReview,
        ): RedirectResponse {
            $moderateReview->handle(
                $request->user(),
                $review,
                ReviewStatus::from($request->validated('status')),
                $request->validated('moderation_notes'),
            );

            return redirect()
                ->route('admin.reviews.index')
                ->with('status', 'Review updated.');
        })->can('moderate', 'review')->name('reviews.update');
        Route::livewire('/reports', AdminReportsPage::class)
            ->can('viewAny', Report::class)
            ->name('reports.index');
        Route::patch('/reports/{report}', function (
            UpdateReportRequest $request,
            Report $report,
            UpdateReportStatusAction $updateReportStatus,
        ): RedirectResponse {
            $updateReportStatus->handle($request->user(), $report, $request->validated());

            return redirect()
                ->route('admin.reports.index')
                ->with('status', 'Report updated.');
        })->can('update', 'report')->name('reports.update');
    });
});
