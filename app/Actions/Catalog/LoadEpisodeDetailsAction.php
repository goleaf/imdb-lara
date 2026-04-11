<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadEpisodeDetailsAction
{
    /**
     * @return array{
     *     series: Title,
     *     season: Season,
     *     episode: Title,
     *     episodeMeta: Episode|null,
     *     still: mixed,
     *     seasonNavigation: Collection<int, Season>,
     *     seasonEpisodes: Collection<int, Episode>,
     *     previousEpisode: Episode|null,
     *     nextEpisode: Episode|null,
     *     guestCast: Collection<int, Credit>,
     *     keyCrew: Collection<int, array{role: string, credits: Collection<int, Credit>}>,
     *     detailItems: Collection<int, array{label: string, value: string}>,
     *     ratingCount: int,
     *     previousEpisodeTitle: Title|null,
     *     nextEpisodeTitle: Title|null,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $series, Season $season, Title $episode): array
    {
        $series->loadMissing([
            'seasons' => fn ($query) => $query
                ->select([
                    'id',
                    'series_id',
                    'name',
                    'slug',
                    'season_number',
                    'summary',
                    'release_year',
                    'meta_title',
                    'meta_description',
                ])
                ->withCount('episodes')
                ->orderBy('season_number')
                ->orderBy('id'),
            ...Title::catalogHeroRelations(),
        ]);

        $season->load([
            'episodes' => fn ($query) => $query
                ->select([
                    'id',
                    'title_id',
                    'series_id',
                    'season_id',
                    'season_number',
                    'episode_number',
                    'absolute_number',
                    'production_code',
                    'aired_at',
                ])
                ->with([
                    'title' => fn ($titleQuery) => $titleQuery
                        ->selectCatalogCardColumns()
                        ->withCatalogHeroRelations(),
                ])
                ->orderBy('episode_number')
                ->orderBy('id'),
        ]);

        $episode->loadMissing([
            'episodeMeta:id,title_id,series_id,season_id,season_number,episode_number,absolute_number,production_code,aired_at',
            'episodeMeta.series:id,slug,name,title_type,is_published',
            'episodeMeta.season:id,series_id,slug,name,season_number',
            'genres:id,name,slug',
            ...Title::catalogHeroRelations(),
        ]);
        $this->loadEpisodeCredits($episode);

        $seasonNavigation = $series->seasons->values();
        $seasonEpisodes = $season->episodes
            ->filter(fn (Episode $episodeMeta): bool => $episodeMeta->title instanceof Title)
            ->values();
        $currentEpisodeIndex = $seasonEpisodes->search(fn (Episode $episodeMeta): bool => $episodeMeta->title_id === $episode->id);
        $previousEpisode = $currentEpisodeIndex !== false && $currentEpisodeIndex > 0
            ? $seasonEpisodes->get($currentEpisodeIndex - 1)
            : null;
        $nextEpisode = $currentEpisodeIndex !== false && $currentEpisodeIndex < ($seasonEpisodes->count() - 1)
            ? $seasonEpisodes->get($currentEpisodeIndex + 1)
            : null;
        $guestCast = $episode->credits
            ->filter(fn (Credit $credit): bool => $credit->department === 'Cast')
            ->values();
        $keyCrew = $episode->credits
            ->reject(fn (Credit $credit): bool => $credit->department === 'Cast')
            ->groupBy(fn (Credit $credit): string => $credit->job ?: $credit->department)
            ->map(fn (Collection $credits, string $role): array => [
                'role' => $role,
                'credits' => $credits->take(4)->values(),
            ])
            ->values();
        $detailItems = collect([
            ['label' => 'Air date', 'value' => $episode->episodeMeta?->aired_at?->format('M j, Y')],
            ['label' => 'Runtime', 'value' => $episode->runtimeMinutesLabel()],
            ['label' => 'Season / episode', 'value' => $episode->episodeMeta ? sprintf('S%02dE%02d', $episode->episodeMeta->season_number, $episode->episodeMeta->episode_number) : null],
            ['label' => 'Series', 'value' => $series->name],
        ])->filter(fn (array $item): bool => filled($item['value']))->values();
        $still = $episode->preferredDisplayImage() ?? $series->preferredBackdrop() ?? $series->preferredPoster();

        return [
            'series' => $series,
            'season' => $season,
            'episode' => $episode,
            'episodeMeta' => $episode->episodeMeta,
            'still' => $still,
            'seasonNavigation' => $seasonNavigation,
            'seasonEpisodes' => $seasonEpisodes,
            'previousEpisode' => $previousEpisode,
            'nextEpisode' => $nextEpisode,
            'previousEpisodeTitle' => $previousEpisode?->title,
            'nextEpisodeTitle' => $nextEpisode?->title,
            'guestCast' => $guestCast,
            'keyCrew' => $keyCrew,
            'detailItems' => $detailItems,
            'ratingCount' => (int) ($episode->statistic?->rating_count ?? 0),
            'seo' => new PageSeoData(
                title: $episode->meta_title ?: $episode->name,
                description: $episode->meta_description ?: ($episode->plot_outline ?: 'Browse cast, metadata, and gallery items for '.$episode->name.'.'),
                canonical: route('public.episodes.show', ['series' => $series, 'season' => $season, 'episode' => $episode]),
                openGraphType: 'video.tv_show',
                openGraphImage: $still?->url,
                openGraphImageAlt: $still?->alt_text ?: $episode->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'TV Shows', 'href' => route('public.series.index')],
                    ['label' => $series->name, 'href' => route('public.titles.show', $series)],
                    ['label' => $season->name, 'href' => route('public.seasons.show', ['series' => $series, 'season' => $season])],
                    ['label' => $episode->name],
                ],
            ),
        ];
    }

    private function loadEpisodeCredits(Title $episode): void
    {
        $episode->setRelation('credits', $episode->credits()
            ->select([
                'id',
                'title_id',
                'person_id',
                'department',
                'job',
                'character_name',
                'billing_order',
                'is_principal',
                'person_profession_id',
                'episode_id',
                'credited_as',
            ])
            ->ordered()
            ->withPersonPreview()
            ->limit(24)
            ->get());
    }
}
