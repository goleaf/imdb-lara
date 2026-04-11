<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteCreditAction;
use App\Actions\Admin\DeleteEpisodeAction;
use App\Actions\Admin\DeletePersonAction;
use App\Actions\Admin\DeleteSeasonAction;
use App\Actions\Admin\DeleteTitleAction;
use App\Actions\Admin\SaveCreditAction;
use App\Actions\Admin\SaveEpisodeAction;
use App\Actions\Admin\SaveGenreAction;
use App\Actions\Admin\SavePersonAction;
use App\Actions\Admin\SavePersonProfessionAction;
use App\Actions\Admin\SaveSeasonAction;
use App\Actions\Admin\StoreTitleAction;
use App\Actions\Admin\UpdateTitleAction;
use App\Http\Requests\Admin\StoreCreditRequest;
use App\Http\Requests\Admin\StoreEpisodeRequest;
use App\Http\Requests\Admin\StoreGenreRequest;
use App\Http\Requests\Admin\StorePersonProfessionRequest;
use App\Http\Requests\Admin\StorePersonRequest;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\StoreTitleRequest;
use App\Http\Requests\Admin\UpdateCreditRequest;
use App\Http\Requests\Admin\UpdateEpisodeRequest;
use App\Http\Requests\Admin\UpdateGenreRequest;
use App\Http\Requests\Admin\UpdatePersonProfessionRequest;
use App\Http\Requests\Admin\UpdatePersonRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Http\Requests\Admin\UpdateTitleRequest;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class CatalogAdminController
{
    use AuthorizesRequests;

    public function storeTitle(StoreTitleRequest $request, StoreTitleAction $storeTitle): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();

        $title = $storeTitle->handle($request->validated());

        return $this->redirectToTitle($title, 'Title created.');
    }

    public function updateTitle(
        UpdateTitleRequest $request,
        Title $title,
        UpdateTitleAction $updateTitle,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $title = $updateTitle->handle($title, $request->validated());

        return $this->redirectToTitle($title, 'Title updated.');
    }

    public function destroyTitle(Title $title, DeleteTitleAction $deleteTitle): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();
        $this->authorize('delete', $title);

        $deleteTitle->handle($title);

        return redirect()
            ->route('admin.titles.index')
            ->with('status', 'Title deleted.');
    }

    public function storePerson(StorePersonRequest $request, SavePersonAction $savePerson): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();

        $person = $savePerson->handle(new Person, $request->validated());

        return $this->redirectToPerson($person, 'Person created.');
    }

    public function updatePerson(
        UpdatePersonRequest $request,
        Person $person,
        SavePersonAction $savePerson,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $person = $savePerson->handle($person, $request->validated());

        return $this->redirectToPerson($person, 'Person updated.');
    }

    public function destroyPerson(Person $person, DeletePersonAction $deletePerson): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();
        $this->authorize('delete', $person);

        $deletePerson->handle($person);

        return redirect()
            ->route('admin.people.index')
            ->with('status', 'Person deleted.');
    }

    public function storePersonProfession(
        StorePersonProfessionRequest $request,
        Person $person,
        SavePersonProfessionAction $savePersonProfession,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $profession = $savePersonProfession->handle(
            new PersonProfession,
            $person,
            $request->validated(),
        );

        return $this->redirectToPerson(
            $person,
            sprintf('Profession "%s" added.', $profession->profession),
        );
    }

    public function updateProfession(
        UpdatePersonProfessionRequest $request,
        PersonProfession $profession,
        SavePersonProfessionAction $savePersonProfession,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $profession = $savePersonProfession->handle(
            $profession,
            $profession->person,
            $request->validated(),
        );

        return $this->redirectToPerson(
            $profession->person,
            sprintf('Profession "%s" updated.', $profession->profession),
        );
    }

    public function destroyProfession(PersonProfession $profession): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();
        $this->authorize('delete', $profession);

        $person = $profession->person;
        $professionLabel = $profession->profession;
        $profession->delete();

        return $this->redirectToPerson(
            $person,
            sprintf('Profession "%s" deleted.', $professionLabel),
        );
    }

    public function storeCredit(StoreCreditRequest $request, SaveCreditAction $saveCredit): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();

        $credit = $saveCredit->handle(new Credit, $request->validated());

        return redirect()
            ->route('admin.credits.edit', $credit)
            ->with('status', 'Credit created.');
    }

    public function updateCredit(
        UpdateCreditRequest $request,
        Credit $credit,
        SaveCreditAction $saveCredit,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $credit = $saveCredit->handle($credit, $request->validated());

        return redirect()
            ->route('admin.credits.edit', $credit)
            ->with('status', 'Credit updated.');
    }

    public function destroyCredit(Credit $credit, DeleteCreditAction $deleteCredit): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();
        $this->authorize('delete', $credit);

        $title = $credit->title;
        $deleteCredit->handle($credit);

        if ($title instanceof Title) {
            return $this->redirectToTitle($title, 'Credit deleted.');
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Credit deleted.');
    }

    public function storeGenre(StoreGenreRequest $request, SaveGenreAction $saveGenre): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();

        $genre = $saveGenre->handle(new Genre, $request->validated());

        return redirect()
            ->route('admin.genres.edit', $genre)
            ->with('status', 'Genre created.');
    }

    public function updateGenre(
        UpdateGenreRequest $request,
        Genre $genre,
        SaveGenreAction $saveGenre,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $genre = $saveGenre->handle($genre, $request->validated());

        return redirect()
            ->route('admin.genres.edit', $genre)
            ->with('status', 'Genre updated.');
    }

    public function destroyGenre(Genre $genre): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();
        $this->authorize('delete', $genre);

        $genre->delete();

        return redirect()
            ->route('admin.genres.index')
            ->with('status', 'Genre deleted.');
    }

    public function storeSeason(
        StoreSeasonRequest $request,
        Title $title,
        SaveSeasonAction $saveSeason,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        /** @var array<string, mixed> $attributes */
        $attributes = $request->validated()['season'];
        $season = $saveSeason->handle(new Season, $title, $attributes);

        return redirect()
            ->route('admin.seasons.edit', $season)
            ->with('status', 'Season created.');
    }

    public function updateSeason(
        UpdateSeasonRequest $request,
        Season $season,
        SaveSeasonAction $saveSeason,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $season = $saveSeason->handle($season, $season->series, $request->validated());

        return redirect()
            ->route('admin.seasons.edit', $season)
            ->with('status', 'Season updated.');
    }

    public function destroySeason(Season $season, DeleteSeasonAction $deleteSeason): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();
        $this->authorize('delete', $season);

        $series = $season->series;
        $deleteSeason->handle($season);

        if ($series instanceof Title) {
            return $this->redirectToTitle($series, 'Season deleted.');
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Season deleted.');
    }

    public function storeEpisode(
        StoreEpisodeRequest $request,
        Season $season,
        SaveEpisodeAction $saveEpisode,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        /** @var array<string, mixed> $attributes */
        $attributes = $request->validated()['episode'];
        $episode = $saveEpisode->handle(new Episode, $season, $attributes);

        return redirect()
            ->route('admin.episodes.edit', $episode)
            ->with('status', 'Episode created.');
    }

    public function updateEpisode(
        UpdateEpisodeRequest $request,
        Episode $episode,
        SaveEpisodeAction $saveEpisode,
    ): RedirectResponse {
        $this->ensureCatalogWritesEnabled();

        $season = $episode->season;

        if (! $season instanceof Season) {
            abort(404);
        }

        $episode = $saveEpisode->handle($episode, $season, $request->validated());

        return redirect()
            ->route('admin.episodes.edit', $episode)
            ->with('status', 'Episode updated.');
    }

    public function destroyEpisode(Episode $episode, DeleteEpisodeAction $deleteEpisode): RedirectResponse
    {
        $this->ensureCatalogWritesEnabled();
        $this->authorize('delete', $episode);

        $season = $episode->season;
        $deleteEpisode->handle($episode);

        if ($season instanceof Season) {
            return redirect()
                ->route('admin.seasons.edit', $season)
                ->with('status', 'Episode deleted.');
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Episode deleted.');
    }

    private function ensureCatalogWritesEnabled(): void
    {
        abort_if((bool) config('screenbase.catalog_only', false), 501);
    }

    private function redirectToTitle(Title $title, string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.titles.edit', $title)
            ->with('status', $status);
    }

    private function redirectToPerson(Person $person, string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.people.edit', $person)
            ->with('status', $status);
    }
}
