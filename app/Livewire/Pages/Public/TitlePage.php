<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\BuildTitleCreditsQueryAction;
use App\Actions\Catalog\LoadTitleDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\MediaAsset;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class TitlePage extends Component
{
    use RendersLegacyPage;

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
        BuildTitleCreditsQueryAction $buildTitleCreditsQuery,
    ): View {
        abort_unless($this->title instanceof Title, 404);

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
            $breadcrumbs = [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'Titles', 'href' => route('public.titles.index')],
                ['label' => $this->title->name, 'href' => route('public.titles.show', $this->title)],
                ['label' => 'Full Cast'],
            ];

            return $this->renderLegacyPage('titles.cast', [
                'title' => $this->title,
                'castCredits' => $castCredits,
                'crewCredits' => $crewCredits,
                'castCount' => (clone $creditsQuery)->where('department', 'Cast')->count(),
                'crewCount' => (clone $creditsQuery)->where('department', '!=', 'Cast')->count(),
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

        return $this->renderLegacyPage('titles.show', $loadTitleDetails->handle($this->title));
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
