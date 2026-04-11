<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\CatalogMediaAsset;
use App\Models\Credit;
use App\Models\Title;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class LoadTitleCastAction
{
    /**
     * @var list<string>
     */
    private const CAST_CATEGORIES = [
        'actor',
        'actress',
        'archive_footage',
        'self',
    ];

    /**
     * @var list<string>
     */
    private const LEAD_CREW_CATEGORIES = [
        'director',
        'writer',
        'producer',
        'executive',
    ];

    private const PAGE_SIZE = 24;

    /**
     * @return array{
     *     title: Title,
     *     poster: CatalogMediaAsset|null,
     *     backdrop: CatalogMediaAsset|null,
     *     castCredits: LengthAwarePaginator,
     *     crewCredits: LengthAwarePaginator,
     *     castPageCredits: Collection<int, Credit>,
     *     crewPageCredits: Collection<int, Credit>,
     *     castBillingGroups: Collection<string, Collection<int, Credit>>,
     *     crewGroups: Collection<string, Collection<int, Credit>>,
     *     leadCrewGroups: Collection<string, Collection<int, Credit>>,
     *     technicalCrewGroups: Collection<string, Collection<int, Credit>>,
     *     castCount: int,
     *     crewCount: int,
     *     leadCrewCount: int,
     *     technicalCrewCount: int,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'statistic:movie_id,aggregate_rating,vote_count',
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
            'plotRecord:movie_id,plot',
        ]);

        $poster = $title->preferredPoster();
        $backdrop = $title->preferredBackdrop();
        $castCreditsQuery = $this->creditQuery($title, castOnly: true);
        $crewCreditsQuery = $this->creditQuery($title, castOnly: false);
        $castCount = (clone $castCreditsQuery)->count();
        $crewCount = (clone $crewCreditsQuery)->count();
        $castCredits = $castCreditsQuery
            ->paginate(self::PAGE_SIZE, pageName: 'cast_page')
            ->withQueryString();
        $crewCredits = $crewCreditsQuery
            ->paginate(self::PAGE_SIZE, pageName: 'crew_page')
            ->withQueryString();
        $castPageCredits = collect($castCredits->items());
        $crewPageCredits = collect($crewCredits->items());
        $crewGroups = $crewPageCredits
            ->groupBy(fn (Credit $credit): string => $credit->department)
            ->sortKeys();
        $leadCrewGroups = $crewPageCredits
            ->filter(fn (Credit $credit): bool => in_array($credit->category, self::LEAD_CREW_CATEGORIES, true))
            ->groupBy(fn (Credit $credit): string => $credit->department)
            ->sortKeys();
        $technicalCrewGroups = $crewPageCredits
            ->reject(fn (Credit $credit): bool => in_array($credit->category, self::LEAD_CREW_CATEGORIES, true))
            ->groupBy(fn (Credit $credit): string => $credit->department)
            ->sortKeys();
        $openGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            'castCredits' => $castCredits,
            'crewCredits' => $crewCredits,
            'castPageCredits' => $castPageCredits,
            'crewPageCredits' => $crewPageCredits,
            'castBillingGroups' => $castPageCredits
                ->groupBy(fn (Credit $credit): string => $credit->is_principal ? 'Principal Cast' : 'Supporting & Guest'),
            'crewGroups' => $crewGroups,
            'leadCrewGroups' => $leadCrewGroups,
            'technicalCrewGroups' => $technicalCrewGroups,
            'castCount' => $castCount,
            'crewCount' => $crewCount,
            'leadCrewCount' => $leadCrewGroups->flatten(1)->count(),
            'technicalCrewCount' => $technicalCrewGroups->flatten(1)->count(),
            'seo' => new PageSeoData(
                title: $title->name.' Full Cast',
                description: 'Browse the full cast and crew list for '.$title->name.'.',
                canonical: route('public.titles.cast', $title),
                openGraphType: $openGraphType,
                openGraphImage: ($backdrop ?? $poster)?->url,
                openGraphImageAlt: ($backdrop ?? $poster)?->alt_text ?: $title->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                    ['label' => 'Full Cast'],
                ],
                paginationPageName: null,
            ),
        ];
    }

    private function creditQuery(Title $title, bool $castOnly): HasMany
    {
        $query = $title->credits()
            ->select([
                'name_credits.name_basic_id',
                'name_credits.movie_id',
                'name_credits.category',
                'name_credits.episode_count',
                'name_credits.position',
            ])
            ->with([
                'person' => fn ($personQuery) => $personQuery->select([
                    'name_basics.id',
                    'name_basics.nconst',
                    'name_basics.imdb_id',
                    'name_basics.primaryname',
                    'name_basics.displayName',
                    'name_basics.primaryImage_url',
                    'name_basics.primaryImage_width',
                    'name_basics.primaryImage_height',
                ]),
            ]);

        if ($castOnly) {
            return $query->whereIn('name_credits.category', self::CAST_CATEGORIES);
        }

        return $query->where(function (Builder $crewQuery): void {
            $crewQuery
                ->whereNull('name_credits.category')
                ->orWhereNotIn('name_credits.category', self::CAST_CATEGORIES);
        });
    }
}
