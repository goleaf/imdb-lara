<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeletePersonAction;
use App\Actions\Admin\SaveMediaAssetAction;
use App\Actions\Admin\SavePersonAction;
use App\Actions\Admin\SavePersonProfessionAction;
use App\Http\Controllers\Admin\Concerns\BlocksCatalogOnlyAdminMutations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Http\Requests\Admin\StorePersonProfessionRequest;
use App\Http\Requests\Admin\StorePersonRequest;
use App\Http\Requests\Admin\UpdatePersonRequest;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\PersonProfession;
use Illuminate\Http\RedirectResponse;

class PersonController extends Controller
{
    use BlocksCatalogOnlyAdminMutations;

    public function store(StorePersonRequest $request, SavePersonAction $savePerson): RedirectResponse
    {
        $this->abortIfCatalogOnly();

        $person = $savePerson->handle(new Person, $request->validated());

        return redirect()->route('admin.people.edit', $person)->with('status', 'Person created.');
    }

    public function update(
        UpdatePersonRequest $request,
        Person $person,
        SavePersonAction $savePerson,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $person = $savePerson->handle($person, $request->validated());

        return redirect()->route('admin.people.edit', $person)->with('status', 'Person updated.');
    }

    public function destroy(Person $person, DeletePersonAction $deletePerson): RedirectResponse
    {
        $this->abortIfCatalogOnly();
        $this->authorize('delete', $person);

        $deletePerson->handle($person);

        return redirect()->route('admin.people.index')->with('status', 'Person deleted.');
    }

    public function storeProfession(
        StorePersonProfessionRequest $request,
        Person $person,
        SavePersonProfessionAction $savePersonProfession,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $savePersonProfession->handle(new PersonProfession, $person, $request->validated());

        return redirect()->route('admin.people.edit', $person)->with('status', 'Profession added.');
    }

    public function storeMediaAsset(
        StoreMediaAssetRequest $request,
        Person $person,
        SaveMediaAssetAction $saveMediaAsset,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $saveMediaAsset->handle(new MediaAsset, $person, $request->validated());

        return redirect()->route('admin.people.edit', $person)->with('status', 'Media asset added.');
    }
}
