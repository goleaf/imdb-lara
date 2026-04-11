<?php

namespace App\Actions\Catalog;

use App\Models\Credit;
use App\Models\Person;
use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Support\Collection;

class BuildPersonFilmographyQueryAction
{
    /**
     * @param  array{profession?: string|null, sort?: string}  $filters
     * @return array{
     *     groups: Collection<int, array{label: string, rows: Collection<int, array{
     *         title: Title,
     *         roleSummary: string,
     *         roleLabels: Collection<int, string>,
     *         creditCount: int,
     *         episodeLabel: string|null
     *     }>}>,
     *     professionOptions: Collection<int, string>
     * }
     */
    public function handle(Person $person, array $filters = []): array
    {
        $selectedProfession = filled($filters['profession'] ?? null)
            ? (string) $filters['profession']
            : null;
        $sort = (string) ($filters['sort'] ?? 'latest');

        $credits = Credit::query()
            ->select(Credit::projectedColumns())
            ->with(Credit::projectedRelations())
            ->whereBelongsTo($person, 'person')
            ->ordered()
            ->with([
                'title' => fn ($query) => $query
                    ->selectCatalogCardColumns()
                    ->addSelect([
                        'popularity_rank' => TitleStatistic::query()
                            ->select('watchlist_count')
                            ->whereColumn('title_statistics.title_id', 'titles.id')
                            ->limit(1),
                    ])
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
                'profession:id,person_id,department,profession,is_primary,sort_order',
            ])
            ->get()
            ->filter(fn (Credit $credit): bool => $credit->title instanceof Title)
            ->values();

        $normalizedCredits = $credits->map(function (Credit $credit): array {
            return [
                'group' => $this->resolveProfessionLabel($credit),
                'credit' => $credit,
            ];
        });

        $professionOptions = $normalizedCredits
            ->pluck('group')
            ->filter()
            ->unique()
            ->values();

        if ($selectedProfession) {
            $normalizedCredits = $normalizedCredits
                ->where('group', $selectedProfession)
                ->values();
        }

        $professionOrder = $professionOptions->values()->flip();

        $groups = $normalizedCredits
            ->groupBy('group')
            ->map(function (Collection $groupCredits, string $groupLabel) use ($sort): array {
                $rows = $groupCredits
                    ->pluck('credit')
                    ->groupBy('title_id')
                    ->map(fn (Collection $creditsForTitle): array => $this->makeFilmographyRow($creditsForTitle))
                    ->pipe(fn (Collection $rows): Collection => $this->sortRows($rows, $sort))
                    ->values();

                return [
                    'label' => $groupLabel,
                    'rows' => $rows,
                ];
            })
            ->sortBy(fn (array $group): int => (int) ($professionOrder[$group['label']] ?? 999))
            ->values();

        return [
            'groups' => $groups,
            'professionOptions' => $professionOptions,
        ];
    }

    private function resolveProfessionLabel(Credit $credit): string
    {
        return $credit->profession?->profession ?: ($credit->job ?: $credit->department);
    }

    /**
     * @param  Collection<int, Credit>  $creditsForTitle
     * @return array{
     *     title: Title,
     *     roleSummary: string,
     *     roleLabels: Collection<int, string>,
     *     creditCount: int,
     *     episodeLabel: string|null
     * }
     */
    private function makeFilmographyRow(Collection $creditsForTitle): array
    {
        /** @var Credit $leadCredit */
        $leadCredit = $creditsForTitle->first();
        /** @var Title $title */
        $title = $leadCredit->title;
        $roleLabels = $creditsForTitle
            ->map(function (Credit $credit): ?string {
                return $credit->character_name
                    ?: ($credit->credited_as ?: ($credit->job ?: $credit->department));
            })
            ->filter()
            ->unique()
            ->values();

        $episodeCount = $creditsForTitle
            ->pluck('episode_id')
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->unique()
            ->count();

        $episodeLabel = $episodeCount > 0
            ? number_format($episodeCount).' episode'.($episodeCount === 1 ? '' : 's')
            : null;

        return [
            'title' => $title,
            'roleSummary' => $roleLabels->take(3)->implode(' · '),
            'roleLabels' => $roleLabels,
            'creditCount' => $creditsForTitle->count(),
            'episodeLabel' => $episodeLabel,
        ];
    }

    /**
     * @param  Collection<int, array{
     *     title: Title,
     *     roleSummary: string,
     *     roleLabels: Collection<int, string>,
     *     creditCount: int,
     *     episodeLabel: string|null
     * }>  $rows
     * @return Collection<int, array{
     *     title: Title,
     *     roleSummary: string,
     *     roleLabels: Collection<int, string>,
     *     creditCount: int,
     *     episodeLabel: string|null
     * }>
     */
    private function sortRows(Collection $rows, string $sort): Collection
    {
        return match ($sort) {
            'oldest' => $rows->sortBy(fn (array $row): string => sprintf(
                '%05d-%s',
                (int) ($row['title']->release_year ?? 0),
                $row['title']->name,
            )),
            'rating' => $rows->sortByDesc(fn (array $row): string => sprintf(
                '%05.2f-%08d-%08d',
                (float) ($row['title']->statistic?->average_rating ?? 0),
                (int) ($row['title']->statistic?->rating_count ?? 0),
                99999999 - (int) ($row['title']->popularity_rank ?? 99999999),
            )),
            default => $rows->sortByDesc(fn (array $row): string => sprintf(
                '%05d-%08d-%s',
                (int) ($row['title']->release_year ?? 0),
                99999999 - (int) ($row['title']->popularity_rank ?? 99999999),
                $row['title']->name,
            )),
        };
    }
}
