<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminTitlesIndexQueryAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\Genre;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class TitlesPage extends Component
{
    use RendersLegacyPage;
    use WithPagination;

    public ?Title $title = null;

    public function mount(?Title $title = null): void
    {
        $this->title = $title;
    }

    public function render(BuildAdminTitlesIndexQueryAction $buildAdminTitlesIndexQuery): View
    {
        if (request()->routeIs('admin.titles.index')) {
            return $this->renderLegacyPage('admin.titles.index', [
                'titles' => $buildAdminTitlesIndexQuery
                    ->handle()
                    ->simplePaginate(20)
                    ->withQueryString(),
            ]);
        }

        if (request()->routeIs('admin.titles.create')) {
            return $this->renderLegacyPage('admin.titles.create', [
                'title' => new Title(['is_published' => true]),
                ...$this->formOptions(),
            ]);
        }

        abort_unless($this->title instanceof Title, 404);

        return $this->renderLegacyPage('admin.titles.edit', [
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
