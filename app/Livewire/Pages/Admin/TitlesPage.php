<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminTitlesIndexQueryAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class TitlesPage extends Component
{
    use RendersPageView;

    public ?Title $title = null;

    public function mount(?Title $title = null): void
    {
        $this->title = $title;
    }

    protected function renderTitlesIndexPage(BuildAdminTitlesIndexQueryAction $buildAdminTitlesIndexQuery): View
    {
        return $this->renderPageView('admin.titles.index', [
            'titles' => $buildAdminTitlesIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }

    protected function renderTitleCreatePage(): View
    {
        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.titles.create', [
                'title' => new Title(['is_published' => true]),
            ]);
        }

        return $this->renderPageView('admin.titles.create', [
            'title' => new Title(['is_published' => true]),
            ...$this->formOptions(),
        ]);
    }

    protected function renderTitleEditPage(): View
    {
        abort_unless($this->title instanceof Title, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.titles.edit', [
                'title' => $this->title,
            ]);
        }

        return $this->renderPageView('admin.titles.edit', [
            'title' => $this->title->load([
                'genres:id,name',
                'credits' => fn ($creditQuery) => $creditQuery
                    ->select([
                        'id',
                        'title_id',
                        'person_id',
                        'department',
                        'job',
                        'character_name',
                        'billing_order',
                        'credited_as',
                        'is_principal',
                        'person_profession_id',
                        'episode_id',
                    ])
                    ->with(['person:id,name,slug', 'profession:id,profession', 'episode.title:id,name'])
                    ->limit(50),
                'seasons' => fn ($seasonQuery) => $seasonQuery
                    ->select([
                        'id',
                        'series_id',
                        'name',
                        'slug',
                        'season_number',
                        'release_year',
                    ])
                    ->withCount('episodes'),
                'mediaAssets' => fn ($mediaQuery) => $mediaQuery->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'caption',
                    'is_primary',
                    'position',
                    'published_at',
                ]),
            ]),
            'draftSeason' => new Season([
                'season_number' => ($this->title->seasons->max('season_number') ?? 0) + 1,
                'release_year' => $this->title->release_year,
            ]),
            'draftMediaAsset' => new MediaAsset([
                'mediable_type' => Title::class,
                'kind' => \App\Enums\MediaKind::Poster,
                'is_primary' => true,
            ]),
            ...$this->formOptions(),
        ]);
    }

    /**
     * @return array{
     *     titleTypes: list<TitleType>,
     *     genres: Collection<int, Genre>
     * }
     */
    private function formOptions(): array
    {
        return [
            'titleTypes' => TitleType::cases(),
            'genres' => Genre::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ];
    }
}
