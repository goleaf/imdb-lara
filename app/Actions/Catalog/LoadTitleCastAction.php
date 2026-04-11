<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\CatalogMediaAsset;
use App\Models\Credit;
use App\Models\Title;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
    private const LEAD_CREW_DEPARTMENTS = [
        'Directing',
        'Writing',
        'Production',
    ];

    private const PAGE_SIZE = 24;

    public function __construct(
        private readonly HydrateTitleCastCatalogAction $hydrateTitleCastCatalogAction,
    ) {}

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
        if (config('screenbase.catalog_only', false) && $this->shouldHydrateCatalog($title)) {
            try {
                $title = $this->hydrateTitleCastCatalogAction->handle($title);
            } catch (\Throwable $exception) {
                logger()->warning(sprintf(
                    'Title cast hydration failed for [%s]. %s',
                    $title->imdb_id ?: $title->tconst ?: $title->getKey(),
                    $exception->getMessage(),
                ));
            }
        }

        $title->loadMissing(Title::catalogHeroRelations());

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
            ->filter(fn (Credit $credit): bool => in_array($credit->department, self::LEAD_CREW_DEPARTMENTS, true))
            ->groupBy(fn (Credit $credit): string => $credit->department)
            ->sortKeys();
        $technicalCrewGroups = $crewPageCredits
            ->reject(fn (Credit $credit): bool => in_array($credit->department, self::LEAD_CREW_DEPARTMENTS, true))
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
            ->select(Credit::projectedColumns())
            ->with(Credit::projectedRelations())
            ->whereHas('person')
            ->ordered()
            ->withPersonPreview();

        if ($castOnly) {
            return $query->cast();
        }

        return $query->crew();
    }

    private function shouldHydrateCatalog(Title $title): bool
    {
        if (! config('screenbase.catalog_only', false)) {
            return false;
        }

        $credits = $title->credits();

        if (! $credits->exists()) {
            return true;
        }

        if ($title->credits()->whereDoesntHave('person')->exists()) {
            return true;
        }

        if (! $title->credits()->cast()->exists()) {
            return true;
        }

        return ! $title->credits()->crew()->exists();
    }
}
