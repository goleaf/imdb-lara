<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\DeleteEpisodeAction;
use App\Actions\Admin\SaveEpisodeAction;
use App\Http\Requests\Admin\UpdateEpisodeRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Episode;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EpisodesPage extends Component
{
    use RendersPageView;
    use ValidatesFormRequests;

    public ?Episode $episode = null;

    public string $name = '';

    public ?string $original_name = null;

    public string $slug = '';

    public ?string $plot_outline = null;

    public ?string $synopsis = null;

    public ?int $release_year = null;

    public ?string $release_date = null;

    public ?int $runtime_minutes = null;

    public ?string $age_rating = null;

    public ?string $origin_country = null;

    public ?string $original_language = null;

    public ?string $tagline = null;

    public ?string $meta_title = null;

    public ?string $meta_description = null;

    public ?string $search_keywords = null;

    public bool $is_published = true;

    public ?int $season_number = null;

    public ?int $episode_number = null;

    public ?int $absolute_number = null;

    public ?string $production_code = null;

    public ?string $aired_at = null;

    public function mount(?Episode $episode = null): void
    {
        $this->episode = $episode;
        if ($episode instanceof Episode) {
            $this->fillEpisodeForm($episode);
        }
    }

    public function render(): View
    {
        abort_unless($this->episode instanceof Episode, 404);

        $loadedEpisode = $this->episode->load([
            'title' => fn ($titleQuery) => $titleQuery->select(Title::catalogCardColumns()),
            'season:id,series_id,name,slug,season_number',
            'series:id,name,slug,title_type,is_published',
        ]);
        $loadedEpisode->fill($this->episodePayload());
        $loadedEpisode->setRelation('title', tap(
            $loadedEpisode->title ?? new Title,
            fn (Title $title) => $title->fill([
                'name' => $this->name,
                'original_name' => $this->original_name,
                'slug' => $this->slug,
                'release_year' => $this->release_year,
                'release_date' => $this->release_date,
                'runtime_minutes' => $this->runtime_minutes,
                'age_rating' => $this->age_rating,
                'origin_country' => $this->origin_country,
                'original_language' => $this->original_language,
                'plot_outline' => $this->plot_outline,
                'synopsis' => $this->synopsis,
                'tagline' => $this->tagline,
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'search_keywords' => $this->search_keywords,
                'is_published' => $this->is_published,
            ]),
        ));

        return $this->renderPageView('admin.episodes.edit', [
            'episode' => $loadedEpisode,
        ]);
    }

    public function saveEpisode(SaveEpisodeAction $saveEpisode): mixed
    {
        abort_unless($this->episode instanceof Episode, 404);

        $validated = $this->validateWithFormRequest(UpdateEpisodeRequest::class, $this->episodePayload(), [
            'episode' => $this->episode,
        ]);

        $savedEpisode = $saveEpisode->handle($this->episode, $this->episode->season, $validated);

        $this->episode = $savedEpisode;
        $this->fillEpisodeForm($savedEpisode);
        $this->resetValidation();
        session()->flash('status', 'Episode updated.');

        return $this->redirectRoute('admin.episodes.edit', $savedEpisode);
    }

    public function deleteEpisode(DeleteEpisodeAction $deleteEpisode): mixed
    {
        abort_unless($this->episode instanceof Episode, 404);

        $this->authorize('delete', $this->episode);
        $redirectSeason = $this->episode->season;
        $deleteEpisode->handle($this->episode);
        session()->flash('status', 'Episode deleted.');

        return $this->redirectRoute('admin.seasons.edit', $redirectSeason);
    }

    private function fillEpisodeForm(Episode $episode): void
    {
        $this->name = (string) ($episode->title?->name ?? '');
        $this->original_name = $episode->title?->original_name;
        $this->slug = (string) ($episode->title?->slug ?? '');
        $this->plot_outline = $episode->title?->plot_outline;
        $this->synopsis = $episode->title?->synopsis;
        $this->release_year = $episode->title?->release_year;
        $this->release_date = $episode->title?->release_date?->toDateString();
        $this->runtime_minutes = $episode->title?->runtime_minutes;
        $this->age_rating = $episode->title?->age_rating;
        $this->origin_country = $episode->title?->origin_country;
        $this->original_language = $episode->title?->original_language;
        $this->tagline = $episode->title?->tagline;
        $this->meta_title = $episode->title?->meta_title;
        $this->meta_description = $episode->title?->meta_description;
        $this->search_keywords = $episode->title?->search_keywords;
        $this->is_published = (bool) ($episode->title?->is_published ?? true);
        $this->season_number = $episode->season_number;
        $this->episode_number = $episode->episode_number;
        $this->absolute_number = $episode->absolute_number;
        $this->production_code = $episode->production_code;
        $this->aired_at = $episode->aired_at?->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function episodePayload(): array
    {
        return [
            'name' => $this->name,
            'original_name' => $this->original_name,
            'slug' => $this->slug,
            'plot_outline' => $this->plot_outline,
            'synopsis' => $this->synopsis,
            'release_year' => $this->release_year,
            'release_date' => $this->release_date,
            'runtime_minutes' => $this->runtime_minutes,
            'age_rating' => $this->age_rating,
            'origin_country' => $this->origin_country,
            'original_language' => $this->original_language,
            'tagline' => $this->tagline,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'search_keywords' => $this->search_keywords,
            'is_published' => $this->is_published,
            'season_number' => $this->season_number,
            'episode_number' => $this->episode_number,
            'absolute_number' => $this->absolute_number,
            'production_code' => $this->production_code,
            'aired_at' => $this->aired_at,
        ];
    }
}
