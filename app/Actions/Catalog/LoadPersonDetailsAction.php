<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Models\AwardNomination;
use App\Models\Credit;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadPersonDetailsAction
{
    /**
     * @return array{
     *     person: Person,
     *     headshot: MediaAsset|null,
     *     photoGallery: Collection<int, MediaAsset>,
     *     alternateNames: Collection<int, string>,
     *     professionLabels: Collection<int, string>,
     *     biographyIntro: string|null,
     *     detailItems: Collection<int, array{label: string, value: string}>,
     *     knownForTitles: Collection<int, Title>,
     *     relatedTitles: Collection<int, Title>,
     *     awardHighlights: Collection<int, AwardNomination>,
     *     collaborators: Collection<int, array{
     *         person: Person,
     *         sharedTitles: Collection<int, Title>,
     *         sharedTitlesCount: int
     *     }>
     * }
     */
    public function handle(Person $person): array
    {
        $person->load([
            'mediaAssets' => fn ($query) => $query
                ->select([
                    'id',
                    'mediable_type',
                    'mediable_id',
                    'kind',
                    'url',
                    'alt_text',
                    'caption',
                    'position',
                    'published_at',
                ])
                ->ordered(),
            'professions:id,person_id,department,profession,is_primary,sort_order',
            'credits' => fn ($query) => $query
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
                ->with([
                    'profession:id,person_id,department,profession,is_primary,sort_order',
                    'title' => fn ($titleQuery) => $titleQuery
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
                            'genres:id,name,slug',
                            'statistic:id,title_id,average_rating,rating_count,review_count,watchlist_count',
                        ]),
                ])
                ->orderBy('billing_order'),
            'awardNominations' => fn ($query) => $query
                ->select([
                    'id',
                    'award_event_id',
                    'award_category_id',
                    'title_id',
                    'person_id',
                    'episode_id',
                    'credited_name',
                    'details',
                    'is_winner',
                    'sort_order',
                ])
                ->with([
                    'awardEvent:id,award_id,name,slug,year',
                    'awardEvent.award:id,name,slug',
                    'awardCategory:id,award_id,name,slug',
                    'title:id,name,slug,title_type,release_year',
                    'episode:id,title_id,season_number,episode_number',
                    'episode.title:id,name,slug',
                ])
                ->orderByDesc('is_winner')
                ->orderBy('sort_order'),
        ]);

        $headshot = MediaAsset::preferredFrom($person->mediaAssets, MediaKind::Headshot, MediaKind::Gallery, MediaKind::Still);
        $photoGallery = $person->mediaAssets
            ->filter(fn (MediaAsset $mediaAsset): bool => in_array($mediaAsset->kind, [
                MediaKind::Headshot,
                MediaKind::Gallery,
                MediaKind::Still,
            ], true))
            ->reject(fn (MediaAsset $mediaAsset): bool => $headshot?->is($mediaAsset) ?? false)
            ->take(8)
            ->values();

        $alternateNames = $this->tokenizeList($person->alternate_names);
        $professionLabels = $person->professions
            ->pluck('profession')
            ->filter()
            ->unique()
            ->values();
        $biographyIntro = filled($person->short_biography)
            ? $person->short_biography
            : (filled($person->biography)
                ? str($person->biography)->squish()->limit(220)->toString()
                : null);

        $creditedTitles = $person->credits
            ->pluck('title')
            ->filter(fn ($title): bool => $title instanceof Title)
            ->unique('id')
            ->values();

        $titleRanks = $creditedTitles
            ->map(function (Title $title) use ($person): array {
                $relevantCredits = $person->credits->where('title_id', $title->id);

                return [
                    'title' => $title,
                    'priority' => sprintf(
                        '%d-%05.2f-%08d-%08d-%05d',
                        $relevantCredits->contains('is_principal', true) ? 1 : 0,
                        (float) ($title->statistic?->average_rating ?? 0),
                        (int) ($title->statistic?->rating_count ?? 0),
                        99999999 - (int) ($title->popularity_rank ?? 99999999),
                        (int) ($title->release_year ?? 0),
                    ),
                ];
            })
            ->sortByDesc('priority')
            ->values();

        $knownForTitles = $titleRanks
            ->take(6)
            ->pluck('title')
            ->values();
        $knownForIds = $knownForTitles->pluck('id')->all();

        $relatedTitles = $titleRanks
            ->reject(fn (array $item): bool => in_array($item['title']->id, $knownForIds, true))
            ->take(6)
            ->pluck('title')
            ->values();

        $detailItems = collect([
            ['label' => 'Born', 'value' => $person->birth_date?->format('M j, Y')],
            ['label' => 'Place of birth', 'value' => $person->birth_place],
            ['label' => 'Nationality', 'value' => $person->nationality],
            ['label' => 'Died', 'value' => $person->death_date?->format('M j, Y')],
            ['label' => 'Place of death', 'value' => $person->death_place],
            ['label' => 'Known for', 'value' => $person->known_for_department],
            ['label' => 'Published credits', 'value' => number_format($creditedTitles->count())],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();

        $awardHighlights = $person->awardNominations
            ->take(8)
            ->values();

        $collaborators = collect();
        $titleIds = $creditedTitles->pluck('id')->all();

        if ($titleIds !== []) {
            $collaborators = Credit::query()
                ->select([
                    'id',
                    'title_id',
                    'person_id',
                    'department',
                    'job',
                    'character_name',
                    'billing_order',
                ])
                ->whereIn('title_id', $titleIds)
                ->where('person_id', '!=', $person->id)
                ->with([
                    'person' => fn ($query) => $query
                        ->select([
                            'id',
                            'name',
                            'slug',
                            'short_biography',
                            'known_for_department',
                            'is_published',
                        ])
                        ->published()
                        ->with([
                            'mediaAssets' => fn ($mediaQuery) => $mediaQuery
                                ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position'])
                                ->where('kind', MediaKind::Headshot)
                                ->ordered()
                                ->limit(1),
                            'professions:id,person_id,department,profession,is_primary,sort_order',
                        ]),
                    'title' => fn ($query) => $query
                        ->select(['id', 'name', 'slug', 'title_type', 'release_year', 'is_published'])
                        ->published(),
                ])
                ->get()
                ->filter(fn (Credit $credit): bool => $credit->person instanceof Person && $credit->title instanceof Title)
                ->groupBy('person_id')
                ->map(function (Collection $credits): array {
                    /** @var Credit $leadCredit */
                    $leadCredit = $credits->first();

                    return [
                        'person' => $leadCredit->person,
                        'sharedTitles' => $credits
                            ->pluck('title')
                            ->filter(fn ($title): bool => $title instanceof Title)
                            ->unique('id')
                            ->take(3)
                            ->values(),
                        'sharedTitlesCount' => $credits
                            ->pluck('title_id')
                            ->unique()
                            ->count(),
                    ];
                })
                ->sortByDesc('sharedTitlesCount')
                ->take(6)
                ->values();
        }

        $breadcrumbs = [
            ['label' => 'Home', 'href' => route('public.home')],
            ['label' => 'Browse People', 'href' => route('public.people.index')],
            ['label' => $person->name],
        ];

        return [
            'person' => $person,
            'headshot' => $headshot,
            'photoGallery' => $photoGallery,
            'alternateNames' => $alternateNames,
            'professionLabels' => $professionLabels,
            'biographyIntro' => $biographyIntro,
            'detailItems' => $detailItems,
            'knownForTitles' => $knownForTitles,
            'relatedTitles' => $relatedTitles,
            'awardHighlights' => $awardHighlights,
            'collaborators' => $collaborators,
            'seo' => new PageSeoData(
                title: $person->meta_title ?: $person->name,
                description: $person->meta_description ?: ($biographyIntro ?: 'Browse credits and biography for '.$person->name.'.'),
                canonical: route('public.people.show', $person),
                openGraphType: 'profile',
                openGraphImage: $headshot?->url,
                openGraphImageAlt: $headshot?->alt_text ?: $person->name,
                breadcrumbs: $breadcrumbs,
            ),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function tokenizeList(?string $value): Collection
    {
        return collect(preg_split('/[,|]/', (string) $value) ?: [])
            ->map(fn (string $item): string => str($item)->trim()->toString())
            ->filter()
            ->values();
    }
}
