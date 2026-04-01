<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BuildAdminTitlesIndexQueryAction;
use App\Actions\Admin\UpdateTitleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EditTitleRequest;
use App\Http\Requests\Admin\IndexTitlesRequest;
use App\Http\Requests\Admin\UpdateTitleRequest;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TitleController extends Controller
{
    public function index(
        IndexTitlesRequest $request,
        BuildAdminTitlesIndexQueryAction $buildAdminTitlesIndexQuery,
    ): View {
        return view('admin.titles.index', [
            'titles' => $buildAdminTitlesIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }

    public function edit(EditTitleRequest $request, Title $title): View
    {
        return view('admin.titles.edit', [
            'title' => $request->title(),
        ]);
    }

    public function update(
        UpdateTitleRequest $request,
        Title $title,
        UpdateTitleAction $updateTitle,
    ): RedirectResponse {
        $updatedTitle = $updateTitle->handle($title, $request->validated());

        return redirect()
            ->route('admin.titles.edit', $updatedTitle)
            ->with('status', 'Title updated.');
    }
}
