<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\BuildPublicPeopleIndexQueryAction;
use App\Actions\Catalog\LoadPersonDetailsAction;
use App\Http\Requests\Catalog\ShowPersonRequest;
use App\Models\Person;
use Illuminate\Contracts\View\View;

class PersonController extends Controller
{
    public function index(BuildPublicPeopleIndexQueryAction $buildPublicPeopleIndexQuery): View
    {
        $people = $buildPublicPeopleIndexQuery
            ->handle()
            ->simplePaginate(18)
            ->withQueryString();

        return view('people.index', [
            'people' => $people,
        ]);
    }

    public function show(
        ShowPersonRequest $request,
        Person $person,
        LoadPersonDetailsAction $loadPersonDetails,
    ): View {
        $person = $request->person();

        return view('people.show', [
            'person' => $loadPersonDetails->handle($person),
        ]);
    }
}
