<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SavePersonProfessionAction;
use App\Http\Controllers\Admin\Concerns\BlocksCatalogOnlyAdminMutations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePersonProfessionRequest;
use App\Models\PersonProfession;
use Illuminate\Http\RedirectResponse;

class PersonProfessionController extends Controller
{
    use BlocksCatalogOnlyAdminMutations;

    public function update(
        UpdatePersonProfessionRequest $request,
        PersonProfession $profession,
        SavePersonProfessionAction $savePersonProfession,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $person = $profession->person()->firstOrFail();
        $savePersonProfession->handle($profession, $person, $request->validated());

        return redirect()->route('admin.people.edit', $person)->with('status', 'Profession updated.');
    }

    public function destroy(PersonProfession $profession): RedirectResponse
    {
        $this->abortIfCatalogOnly();
        $this->authorize('delete', $profession);

        $person = $profession->person()->firstOrFail();
        $profession->delete();

        return redirect()->route('admin.people.edit', $person)->with('status', 'Profession deleted.');
    }
}
