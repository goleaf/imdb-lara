<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\LoadPersonDetailsAction;
use App\Http\Requests\Catalog\ShowPersonRequest;
use App\Models\Person;
use Illuminate\Contracts\View\View;

class PersonController extends Controller
{
    public function index(): View
    {
        return view('people.index');
    }

    public function show(
        ShowPersonRequest $request,
        Person $person,
        LoadPersonDetailsAction $loadPersonDetails,
    ): View {
        $person = $request->person();

        return view('people.show', $loadPersonDetails->handle($person));
    }
}
