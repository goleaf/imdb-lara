<?php

namespace App\Actions\Catalog;

use App\Actions\Seo\PageSeoData;
use App\Enums\TitleType;
use App\Models\CatalogMediaAsset;
use App\Models\Title;
use Illuminate\Support\Collection;

class LoadTitleTriviaAndGoofsAction
{
    /**
     * @return array{
     *     title: Title,
     *     poster: CatalogMediaAsset|null,
     *     backdrop: CatalogMediaAsset|null,
     *     triviaItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     goofItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     triviaTotalCount: int,
     *     goofTotalCount: int,
     *     spoilerFactCount: int,
     *     seo: PageSeoData
     * }
     */
    public function handle(Title $title): array
    {
        $title->load([
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
        ]);

        $poster = $title->preferredPoster();
        $backdrop = $title->preferredBackdrop();
        $summary = $this->summarize();
        $openGraphType = in_array($title->title_type, [TitleType::Series, TitleType::MiniSeries], true)
            ? 'video.tv_show'
            : 'video.movie';

        return [
            'title' => $title,
            'poster' => $poster,
            'backdrop' => $backdrop,
            ...$summary,
            'seo' => new PageSeoData(
                title: $title->name.' Trivia & Goofs',
                description: 'Browse trivia notes and goof records for '.$title->name.' when they are attached to the imported catalog.',
                canonical: route('public.titles.trivia', $title),
                openGraphType: $openGraphType,
                openGraphImage: $backdrop?->url ?? $poster?->url,
                openGraphImageAlt: $backdrop?->alt_text ?: $poster?->alt_text ?: $title->name,
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Titles', 'href' => route('public.titles.index')],
                    ['label' => $title->name, 'href' => route('public.titles.show', $title)],
                    ['label' => 'Trivia & Goofs'],
                ],
                paginationPageName: null,
            ),
        ];
    }

    /**
     * @return array{
     *     triviaItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     goofItems: Collection<int, array{text: string, isSpoiler: bool, score: int|null, scoreLabel: string|null, scoreTone: string}>,
     *     triviaTotalCount: int,
     *     goofTotalCount: int,
     *     spoilerFactCount: int
     * }
     */
    public function summarize(): array
    {
        return [
            'triviaItems' => collect(),
            'goofItems' => collect(),
            'triviaTotalCount' => 0,
            'goofTotalCount' => 0,
            'spoilerFactCount' => 0,
        ];
    }
}
