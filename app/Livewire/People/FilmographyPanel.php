<?php

namespace App\Livewire\People;

use App\Actions\Catalog\BuildPersonFilmographyQueryAction;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class FilmographyPanel extends Component
{
    protected BuildPersonFilmographyQueryAction $buildPersonFilmographyQuery;

    #[Locked]
    public Person $person;

    #[Url(as: 'job')]
    public ?string $profession = null;

    #[Url(as: 'credit_sort')]
    public string $sort = 'latest';

    public function mount(Person $person): void
    {
        $this->person = $person;
    }

    public function boot(BuildPersonFilmographyQueryAction $buildPersonFilmographyQuery): void
    {
        $this->buildPersonFilmographyQuery = $buildPersonFilmographyQuery;
    }

    #[Computed]
    public function viewData(): array
    {
        $filmography = $this->buildPersonFilmographyQuery->handle($this->person, [
            'profession' => $this->profession,
            'sort' => $this->sort,
        ]);
        $groups = $this->formatGroups($filmography['groups']);

        return [
            'groups' => $groups,
            'professionOptions' => $this->formatProfessionOptions($filmography['professionOptions']),
            'sortOptions' => [
                ['value' => 'latest', 'label' => 'Newest first', 'icon' => 'calendar-days'],
                ['value' => 'oldest', 'label' => 'Oldest first', 'icon' => 'clock'],
                ['value' => 'rating', 'label' => 'Highest rated', 'icon' => 'star'],
            ],
            'summaryBadgeLabel' => $this->formatCountLabel(
                $groups->sum(fn (array $group): int => $group['titleCount']),
                'title',
            ),
        ];
    }

    public function render(): View
    {
        return view('livewire.people.filmography-panel', $this->viewData);
    }

    public function placeholder(array $params = []): View
    {
        return view('livewire.placeholders.filmography-panel');
    }

    /**
     * @param  Collection<int, array{label: string, rows: Collection<int, array{
     *     title: \App\Models\Title,
     *     roleSummary: string,
     *     roleLabels: Collection<int, string>,
     *     creditCount: int,
     *     episodeLabel: string|null
     * }>}  $groups
     * @return Collection<int, array{description: string, label: string, rows: Collection<int, array{
     *     title: Title,
     *     roleSummary: string,
     *     roleLabels: Collection<int, string>,
     *     creditCount: int,
     *     creditCountLabel: string,
     *     episodeLabel: string|null
     * }>, titleCount: int, titleCountLabel: string}>
     */
    private function formatGroups(Collection $groups): Collection
    {
        return $groups->map(function (array $group): array {
            $titleCount = $group['rows']->count();

            return [
                ...$group,
                'description' => $this->formatCountLabel($titleCount, 'title').' in this credit grouping.',
                'rows' => $group['rows']->map(fn (array $row): array => [
                    ...$row,
                    'creditCountLabel' => $this->formatCountLabel($row['creditCount'], 'credit'),
                ]),
                'titleCount' => $titleCount,
                'titleCountLabel' => $this->formatCountLabel($titleCount, 'title'),
            ];
        });
    }

    /**
     * @param  Collection<int, string>  $professionOptions
     * @return list<array{key: string, label: string, value: string}>
     */
    private function formatProfessionOptions(Collection $professionOptions): array
    {
        return $professionOptions
            ->map(fn (string $profession): array => [
                'key' => Str::slug($profession),
                'label' => $profession,
                'value' => $profession,
            ])
            ->all();
    }

    private function formatCountLabel(int $count, string $singular): string
    {
        return number_format($count).' '.$singular.($count === 1 ? '' : 's');
    }
}
