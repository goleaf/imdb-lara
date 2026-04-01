<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Contracts\View\View;

class GenreController extends Controller
{
    public function __invoke(Genre $genre): View
    {
        return view('catalog.browse', [
            'pageTitle' => $genre->name,
            'metaDescription' => $genre->description ?: 'Browse '.$genre->name.' titles, reviews, and discovery pages on Screenbase.',
            'heading' => $genre->name,
            'description' => $genre->description ?: 'Published titles tagged with '.$genre->name.'.',
            'breadcrumbs' => [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'All Titles', 'href' => route('public.titles.index')],
                ['label' => $genre->name],
            ],
            'badges' => ['Genre hub', 'Linked from title pages'],
            'actions' => [
                ['label' => 'Browse All Titles', 'href' => route('public.titles.index'), 'variant' => 'outline', 'icon' => 'film'],
                ['label' => 'Search', 'href' => route('public.search', ['genre' => $genre->slug]), 'variant' => 'ghost', 'icon' => 'funnel'],
            ],
            'browserProps' => [
                'genre' => $genre->slug,
                'sort' => 'popular',
                'pageName' => 'genre-'.$genre->slug,
                'emptyHeading' => 'No published titles are available in this genre yet.',
                'emptyText' => 'Try another genre or return to the broader catalog.',
            ],
        ]);
    }
}
