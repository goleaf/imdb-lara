<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\DeleteSeasonAction;
use App\Actions\Admin\SaveEpisodeAction;
use App\Actions\Admin\SaveSeasonAction;
use App\Http\Requests\Admin\StoreEpisodeRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Livewire\Pages\Admin\Concerns\ResolvesAdminFormState;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Component;

class SeasonsPage extends Component
{
    use RendersPageView;
    use ResolvesAdminFormState;
    use ValidatesFormRequests;

    public ?Season $season = null;

    public string $name = '';

    public string $slug = '';

    public int $season_number = 1;

    public ?string $summary = null;

    public ?int $release_year = null;

    public ?string $meta_title = null;

    public ?string $meta_description = null;

    /**
     * @var array<string, mixed>
     */
    public array $episode = [];

    public function mount(?Season $season = null): void
    {
        $this->season = $season;
        if ($season instanceof Season) {
            $this->fillSeasonForm($season);
            $this->initializeDraftEpisode();
        }
    }

    public function render(): View
    {
        abort_unless($this->season instanceof Season, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.seasons.edit', [
                'season' => $this->season->load([
                    'series' => fn ($seriesQuery) => $seriesQuery->select(Title::catalogCardColumns()),
                ])->fill($this->seasonPayload()),
            ]);
        }

        $loadedSeason = $this->season->load([
            'series' => fn ($seriesQuery) => $seriesQuery->select(Title::catalogCardColumns()),
            'episodes' => fn ($episodeQuery) => $episodeQuery
                ->select([
                    'id',
                    'title_id',
                    'season_id',
                    'season_number',
                    'episode_number',
                    'absolute_number',
                    'production_code',
                    'aired_at',
                ])
                ->with('title:id,name,slug,is_published'),
        ]);
        $loadedSeason->fill($this->seasonPayload());

        return $this->renderPageView('admin.seasons.edit', [
            'season' => $loadedSeason,
            'seasonFormData' => $this->adminFormBindingData(),
            'draftEpisode' => tap(
                new Episode(Arr::only($this->episode, (new Episode)->getFillable())),
                function (Episode $episode): void {
                    $episode->setRelation('title', new Title([
                        'name' => $this->episode['name'] ?? null,
                        'slug' => $this->episode['slug'] ?? null,
                        'release_year' => $this->episode['release_year'] ?? $this->release_year,
                        'release_date' => $this->episode['release_date'] ?? null,
                        'runtime_minutes' => $this->episode['runtime_minutes'] ?? null,
                        'age_rating' => $this->episode['age_rating'] ?? null,
                        'origin_country' => $this->episode['origin_country'] ?? null,
                        'original_language' => $this->episode['original_language'] ?? null,
                        'plot_outline' => $this->episode['plot_outline'] ?? null,
                        'synopsis' => $this->episode['synopsis'] ?? null,
                        'tagline' => $this->episode['tagline'] ?? null,
                        'meta_title' => $this->episode['meta_title'] ?? null,
                        'meta_description' => $this->episode['meta_description'] ?? null,
                        'search_keywords' => $this->episode['search_keywords'] ?? null,
                        'is_published' => $this->episode['is_published'] ?? true,
                    ]));
                },
            ),
            'draftEpisodeFormData' => $this->adminFormBindingData('episode'),
        ]);
    }

    public function saveSeason(SaveSeasonAction $saveSeason): mixed
    {
        abort_unless($this->season instanceof Season, 404);

        $validated = $this->validateWithFormRequest(UpdateSeasonRequest::class, $this->seasonPayload(), [
            'season' => $this->season,
        ]);

        $savedSeason = $saveSeason->handle($this->season, $this->season->series, $validated);

        $this->season = $savedSeason;
        $this->fillSeasonForm($savedSeason);
        $this->resetValidation();
        session()->flash('status', 'Season updated.');

        return $this->redirectRoute('admin.seasons.edit', $savedSeason);
    }

    public function saveEpisode(SaveEpisodeAction $saveEpisode): void
    {
        abort_unless($this->season instanceof Season, 404);

        $validated = $this->validateWithFormRequest(
            StoreEpisodeRequest::class,
            ['episode' => $this->episode],
            ['season' => $this->season->loadMissing('series')],
        );

        $saveEpisode->handle(new Episode, $this->season, $validated['episode']);

        $this->season->refresh();
        $this->initializeDraftEpisode();
        $this->resetValidation();
        session()->flash('status', 'Episode added.');
    }

    public function deleteSeason(DeleteSeasonAction $deleteSeason): mixed
    {
        abort_unless($this->season instanceof Season, 404);

        $this->authorize('delete', $this->season);
        $redirectTitle = $this->season->series;
        $deleteSeason->handle($this->season);
        session()->flash('status', 'Season deleted.');

        return $this->redirectRoute('admin.titles.edit', $redirectTitle);
    }

    private function fillSeasonForm(Season $season): void
    {
        $this->name = (string) $season->name;
        $this->slug = (string) $season->slug;
        $this->season_number = (int) $season->season_number;
        $this->summary = $season->summary;
        $this->release_year = $season->release_year;
        $this->meta_title = $season->meta_title;
        $this->meta_description = $season->meta_description;
    }

    private function initializeDraftEpisode(): void
    {
        $nextEpisodeNumber = $this->season instanceof Season
            ? ((int) ($this->season->episodes()->max('episode_number') ?? 0)) + 1
            : 1;

        $this->episode = [
            'name' => null,
            'original_name' => null,
            'slug' => null,
            'plot_outline' => null,
            'synopsis' => null,
            'release_year' => $this->release_year,
            'release_date' => null,
            'runtime_minutes' => null,
            'age_rating' => null,
            'origin_country' => null,
            'original_language' => null,
            'tagline' => null,
            'meta_title' => null,
            'meta_description' => null,
            'search_keywords' => null,
            'is_published' => true,
            'season_number' => $this->season_number,
            'episode_number' => $nextEpisodeNumber,
            'absolute_number' => null,
            'production_code' => null,
            'aired_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seasonPayload(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'season_number' => $this->season_number,
            'summary' => $this->summary,
            'release_year' => $this->release_year,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];
    }
}
