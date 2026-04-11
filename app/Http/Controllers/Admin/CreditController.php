<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteCreditAction;
use App\Actions\Admin\SaveCreditAction;
use App\Http\Controllers\Admin\Concerns\BlocksCatalogOnlyAdminMutations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCreditRequest;
use App\Http\Requests\Admin\UpdateCreditRequest;
use App\Models\Credit;
use Illuminate\Http\RedirectResponse;

class CreditController extends Controller
{
    use BlocksCatalogOnlyAdminMutations;

    public function store(StoreCreditRequest $request, SaveCreditAction $saveCredit): RedirectResponse
    {
        $this->abortIfCatalogOnly();

        $credit = $saveCredit->handle(new Credit, $request->validated());

        return redirect()->route('admin.credits.edit', $credit)->with('status', 'Credit created.');
    }

    public function update(
        UpdateCreditRequest $request,
        Credit $credit,
        SaveCreditAction $saveCredit,
    ): RedirectResponse {
        $this->abortIfCatalogOnly();

        $credit = $saveCredit->handle($credit, $request->validated());

        return redirect()->route('admin.credits.edit', $credit)->with('status', 'Credit updated.');
    }

    public function destroy(Credit $credit, DeleteCreditAction $deleteCredit): RedirectResponse
    {
        $this->abortIfCatalogOnly();
        $this->authorize('delete', $credit);

        $title = $credit->title;
        $deleteCredit->handle($credit);

        return $title
            ? redirect()->route('admin.titles.edit', $title)->with('status', 'Credit deleted.')
            : redirect()->route('admin.dashboard')->with('status', 'Credit deleted.');
    }
}
