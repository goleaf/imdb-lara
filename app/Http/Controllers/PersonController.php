<?php

namespace App\Http\Controllers;

use App\MediaKind;
use App\Models\Person;
use Illuminate\Contracts\View\View;

class PersonController extends Controller
{
    public function index(): View
    {
        $people = Person::query()
            ->select(['id', 'name', 'slug', 'known_for_department', 'biography', 'popularity_rank', 'is_published'])
            ->published()
            ->withCount('credits')
            ->with([
                'mediaAssets' => fn ($query) => $query
                    ->select(['id', 'mediable_type', 'mediable_id', 'kind', 'url', 'alt_text', 'position', 'is_primary'])
                    ->where('kind', MediaKind::Headshot)
                    ->orderBy('position')
                    ->limit(1),
            ])
            ->orderBy('name')
            ->simplePaginate(18)
            ->withQueryString();

        return view('people.index', [
            'people' => $people,
        ]);
    }

    public function show(Person $person): View
    {
        $person->load([
            'mediaAssets',
            'credits.title:id,name,slug,title_type,release_year',
        ]);

        return view('people.show', [
            'person' => $person,
        ]);
    }
}
