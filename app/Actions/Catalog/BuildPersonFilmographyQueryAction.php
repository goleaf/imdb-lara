<?php

namespace App\Actions\Catalog;

use App\Models\Credit;
use App\Models\Person;
use App\Models\Title;
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

        $person->loadMissing('professions:id,person_id,profession,sort_order');

        $credits = Credit::query()
            ->select([
                'id',
                'title_id',
                'person_id',
                'department',
                'job',
                'character_name',
                'billing_order',
                'person_profession_id',
                'episode_id',
                'credited_as',
                'is_principal',
            ])
            ->whereBelongsTo($person)
            ->with([
                'profession:id,person_id,department,profession,is_primary,sort_order',
                'episode:id,title_id,season_number,episode_number',
                'title' => fn ($query) => $query
                    ->select([
                        'id',
                        'name',
                        'slug',
                        'title_type',
                        'release_year',
                        'plot_outline',
                        'popularity_rank',
                        'is_published',
                    ])
                    ->published()
                    ->with([
                        'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
                        'statistic:id,title_id,average_rating,rating_count,review_count',
                    ]),
            ])
            ->orderBy('billing_order')
            ->get()
            ->filter(fn (Credit $credit): bool => $credit->title instanceof Title)
            ->values();

        $normalizedCredits = $credits->map(function (Credit $credit): array {
            return [
                'group' => $this->resolveProfessionLabel($credit),
                'credit' => $credit,
            ];
        });

        $professionOptions = $person->professions
            ->pluck('profession')
            ->merge($normalizedCredits->pluck('group'))
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
        return $credit->profession?->profession
            ?? ($credit->job ?: $credit->department);
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

        $episodeCredits = $creditsForTitle
            ->pluck('episode')
            ->filter()
            ->unique('id')
            ->values();

        $episodeLabel = $episodeCredits->isNotEmpty()
            ? $episodeCredits
                ->take(2)
                ->map(fn ($episode) => sprintf('S%02dE%02d', $episode->season_number, $episode->episode_number))
                ->implode(', ')
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
