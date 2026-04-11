<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TitlePage extends Component
{
    use RendersPageView;

    public ?Title $title = null;

    public function mount(Title $title): void
    {
        abort_unless($title->is_published, 404);

        $this->title = $title;

        $this->redirectCanonicalEpisode($title);
    }

    public function render(LoadTitleDetailsAction $loadTitleDetails): View
    {
        abort_unless($this->title instanceof Title, 404);

        return $this->renderPageView('titles.show', $loadTitleDetails->handle($this->title));
    }

    private function redirectCanonicalEpisode(Title $title): void
    {
        if ($title->title_type !== TitleType::Episode) {
            return;
        }

        $title->loadMissing([
            'episodeMeta:episode_movie_id,movie_id,season,episode_number,release_year,release_month,release_day',
            'episodeMeta.series' => fn ($query) => $query->select([
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
            ]),
        ]);

        if ($title->episodeMeta?->series instanceof Title) {
            $this->redirectRoute('public.episodes.show', [
                'series' => $title->episodeMeta->series,
                'season' => 'season-'.$title->episodeMeta->season_number,
                'episode' => $title,
            ]);

            return;
        }

        abort(404);
    }
}
