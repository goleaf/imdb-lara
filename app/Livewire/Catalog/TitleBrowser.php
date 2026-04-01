<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\BuildPublicTitleIndexQueryAction;
use Livewire\Component;
use Livewire\WithPagination;

class TitleBrowser extends Component
{
    use WithPagination;

    /**
     * @var list<string>
     */
    public array $types = [];

    public ?string $genre = null;

    public ?int $year = null;

    public string $sort = 'popular';

    public string $pageName = 'titles';

    public int $perPage = 12;

    public bool $excludeEpisodes = true;

    public bool $showSummary = true;

    public string $emptyHeading = 'No titles match this collection yet.';

    public string $emptyText = 'Check back soon or explore another part of the catalog.';

    public function render()
    {
        $titles = app(BuildPublicTitleIndexQueryAction::class)
            ->handle([
                'types' => $this->types,
                'genre' => $this->genre,
                'year' => $this->year,
                'sort' => $this->sort,
                'excludeEpisodes' => $this->excludeEpisodes,
            ])
            ->simplePaginate($this->perPage, pageName: $this->pageName)
            ->withQueryString();

        return view('livewire.catalog.title-browser', [
            'titles' => $titles,
            'showSummary' => $this->showSummary,
        ]);
    }
}
