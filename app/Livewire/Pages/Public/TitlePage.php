<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\BuildTitleCreditsQueryAction;
use App\Actions\Catalog\LoadTitleBoxOfficeAction;
use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Actions\Catalog\LoadTitleMediaGalleryAction;
use App\Actions\Catalog\LoadTitleMetadataExplorationAction;
use App\Actions\Catalog\LoadTitleParentsGuideAction;
use App\Actions\Catalog\LoadTitleTriviaAndGoofsAction;
use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class TitlePage extends Component
{
    use RendersPageView;

    public ?Title $title = null;

    public function mount(Title $title): void
    {
        abort_unless(
            $title->is_published || (auth()->user()?->can('view', $title) ?? false),
            404,
        );

        $this->title = $title;

        $this->redirectCanonicalEpisode($title);
    }

    public function render(
        LoadTitleDetailsAction $loadTitleDetails,
        LoadTitleBoxOfficeAction $loadTitleBoxOffice,
        LoadTitleMediaGalleryAction $loadTitleMediaGallery,
        LoadTitleMetadataExplorationAction $loadTitleMetadataExploration,
        LoadTitleParentsGuideAction $loadTitleParentsGuide,
        LoadTitleTriviaAndGoofsAction $loadTitleTriviaAndGoofs,
        BuildTitleCreditsQueryAction $buildTitleCreditsQuery,
    ): View {
        abort_unless($this->title instanceof Title, 404);

        if (request()->routeIs('public.titles.media')) {
            $mediaPage = $loadTitleMediaGallery->handle($this->title);
            $openGraphImage = $mediaPage['viewerAsset']?->url ?? $mediaPage['poster']?->url;
            $openGraphImageAlt = $mediaPage['viewerAsset']?->alt_text
                ?: $mediaPage['poster']?->alt_text
                ?: $this->title->name;

            return $this->renderPageView('titles.media', [
                ...$mediaPage,
                'seo' => new PageSeoData(
                    title: $this->title->name.' Media Gallery',
                    description: 'Browse posters, stills, backdrops, and trailers for '.$this->title->name.'.',
                    canonical: route('public.titles.media', $this->title),
                    openGraphType: in_array($this->title->title_type, [TitleType::Series, TitleType::MiniSeries], true) ? 'video.tv_show' : 'video.movie',
                    openGraphImage: $openGraphImage,
                    openGraphImageAlt: $openGraphImageAlt,
                    breadcrumbs: [
                        ['label' => 'Home', 'href' => route('public.home')],
                        ['label' => 'Titles', 'href' => route('public.titles.index')],
                        ['label' => $this->title->name, 'href' => route('public.titles.show', $this->title)],
                        ['label' => 'Media Gallery'],
                    ],
                    paginationPageName: null,
                ),
            ]);
        }

        if (request()->routeIs('public.titles.box-office')) {
            return $this->renderPageView('titles.box-office', $loadTitleBoxOffice->handle($this->title));
        }

        if (request()->routeIs('public.titles.metadata')) {
            return $this->renderPageView('titles.metadata', $loadTitleMetadataExploration->handle($this->title));
        }

        if (request()->routeIs('public.titles.parents-guide')) {
            return $this->renderPageView('titles.parents-guide', $loadTitleParentsGuide->handle($this->title));
        }

        if (request()->routeIs('public.titles.trivia')) {
            return $this->renderPageView('titles.trivia', $loadTitleTriviaAndGoofs->handle($this->title));
        }

        if (request()->routeIs('public.titles.cast')) {
            $this->title->load([
                'genres:id,name,slug',
                'statistic:id,title_id,rating_count,average_rating,review_count',
                'mediaAssets:id,mediable_type,mediable_id,kind,url,alt_text,position,is_primary',
            ]);

            $creditsQuery = $buildTitleCreditsQuery->handle($this->title);
            $castCredits = (clone $creditsQuery)
                ->where('department', 'Cast')
                ->simplePaginate(24, ['*'], 'castPage')
                ->withQueryString();
            $crewCredits = (clone $creditsQuery)
                ->where('department', '!=', 'Cast')
                ->simplePaginate(24, ['*'], 'crewPage')
                ->withQueryString();
            $poster = MediaAsset::preferredFrom($this->title->mediaAssets, MediaKind::Poster, MediaKind::Backdrop);
            $backdrop = MediaAsset::preferredFrom($this->title->mediaAssets, MediaKind::Backdrop, MediaKind::Poster);
            $castPageCredits = collect($castCredits->items());
            $crewPageCredits = collect($crewCredits->items());
            $crewGroups = $crewPageCredits
                ->groupBy(fn ($credit) => filled($credit->department) ? $credit->department : 'Crew')
                ->sortKeys();
            $castBillingGroups = collect([
                'Principal cast' => $castPageCredits->filter(fn ($credit) => $credit->is_principal),
                'Supporting & guest' => $castPageCredits->reject(fn ($credit) => $credit->is_principal),
            ])->filter(fn ($credits) => $credits->isNotEmpty());
            $leadDepartmentOrder = ['Directing', 'Writing', 'Production'];
            $leadCrewGroups = collect($leadDepartmentOrder)
                ->mapWithKeys(fn ($department) => [$department => $crewGroups->get($department, collect())])
                ->filter(fn ($credits) => $credits->isNotEmpty());
            $technicalCrewGroups = $crewGroups
                ->reject(fn ($credits, $department) => in_array($department, $leadDepartmentOrder, true));
            $breadcrumbs = [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'Titles', 'href' => route('public.titles.index')],
                ['label' => $this->title->name, 'href' => route('public.titles.show', $this->title)],
                ['label' => 'Full Cast'],
            ];

            return $this->renderPageView('titles.cast', [
                'title' => $this->title,
                'poster' => $poster,
                'backdrop' => $backdrop,
                'castCredits' => $castCredits,
                'crewCredits' => $crewCredits,
                'castPageCredits' => $castPageCredits,
                'crewPageCredits' => $crewPageCredits,
                'crewGroups' => $crewGroups,
                'castBillingGroups' => $castBillingGroups,
                'leadCrewGroups' => $leadCrewGroups,
                'technicalCrewGroups' => $technicalCrewGroups,
                'castCount' => (clone $creditsQuery)->where('department', 'Cast')->count(),
                'crewCount' => (clone $creditsQuery)->where('department', '!=', 'Cast')->count(),
                'leadCrewCount' => $leadCrewGroups->sum(fn ($credits) => $credits->count()),
                'technicalCrewCount' => $technicalCrewGroups->sum(fn ($credits) => $credits->count()),
                'seo' => new PageSeoData(
                    title: $this->title->name.' Full Cast',
                    description: 'Browse the full cast and crew list for '.$this->title->name.'.',
                    canonical: route('public.titles.cast', $this->title),
                    openGraphType: in_array($this->title->title_type, [TitleType::Series, TitleType::MiniSeries], true) ? 'video.tv_show' : 'video.movie',
                    openGraphImage: $poster?->url,
                    openGraphImageAlt: $poster?->alt_text ?: $this->title->name,
                    breadcrumbs: $breadcrumbs,
                    paginationPageName: null,
                ),
            ]);
        }

        return $this->renderPageView('titles.show', $loadTitleDetails->handle($this->title));
    }

    private function redirectCanonicalEpisode(Title $title): void
    {
        if ($title->title_type !== TitleType::Episode) {
            return;
        }

        $title->load('episodeMeta.season:id,series_id,slug', 'episodeMeta.series:id,slug');

        if (
            Route::has('public.episodes.show')
            && $title->episodeMeta?->season
            && $title->episodeMeta?->series
        ) {
            $this->redirectRoute('public.episodes.show', [
                'series' => $title->episodeMeta->series,
                'season' => $title->episodeMeta->season,
                'episode' => $title,
            ]);

            return;
        }

        abort(404);
    }
}
