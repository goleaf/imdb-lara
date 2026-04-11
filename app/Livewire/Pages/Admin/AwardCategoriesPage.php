<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminAwardCategoriesIndexQueryAction;
use App\Actions\Admin\SaveAwardCategoryAction;
use App\Http\Requests\Admin\StoreAwardCategoryRequest;
use App\Http\Requests\Admin\UpdateAwardCategoryRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\AwardCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AwardCategoriesPage extends Component
{
    use RendersPageView;
    use ValidatesFormRequests;
    use WithPagination;

    public ?AwardCategory $awardCategory = null;

    #[Url(as: 'q')]
    public string $search = '';

    public string $name = '';

    public function mount(?AwardCategory $awardCategory = null): void
    {
        $this->awardCategory = $awardCategory;
        $this->fillAwardCategoryForm($awardCategory ?? new AwardCategory);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function renderAwardCategoriesIndexPage(
        BuildAdminAwardCategoriesIndexQueryAction $buildAdminAwardCategoriesIndexQuery,
    ): View {
        $this->authorize('viewAny', AwardCategory::class);

        return $this->renderPageView('admin.award-categories.index', [
            'awardCategories' => $buildAdminAwardCategoriesIndexQuery
                ->handle($this->search)
                ->simplePaginate(20)
                ->withQueryString(),
            'hasActiveFilters' => trim($this->search) !== '',
        ]);
    }

    protected function renderAwardCategoryCreatePage(): View
    {
        $this->authorize('create', AwardCategory::class);

        return $this->renderPageView('admin.award-categories.create', [
            'awardCategory' => new AwardCategory($this->awardCategoryPayload()),
        ]);
    }

    protected function renderAwardCategoryEditPage(): View
    {
        abort_unless($this->awardCategory instanceof AwardCategory, 404);

        $this->authorize('view', $this->awardCategory);

        return $this->renderPageView('admin.award-categories.edit', [
            'awardCategory' => $this->awardCategory
                ->loadCount('movieAwardNominations')
                ->fill($this->awardCategoryPayload()),
        ]);
    }

    public function saveAwardCategory(SaveAwardCategoryAction $saveAwardCategory): mixed
    {
        $validated = $this->awardCategory instanceof AwardCategory
            ? $this->validateWithFormRequest(UpdateAwardCategoryRequest::class, $this->awardCategoryPayload(), [
                'awardCategory' => $this->awardCategory,
            ])
            : $this->validateWithFormRequest(StoreAwardCategoryRequest::class, $this->awardCategoryPayload());

        $savedAwardCategory = $saveAwardCategory->handle($this->awardCategory ?? new AwardCategory, $validated);

        $this->awardCategory = $savedAwardCategory;
        $this->fillAwardCategoryForm($savedAwardCategory);
        $this->resetValidation();
        session()->flash('status', $savedAwardCategory->wasRecentlyCreated ? 'Award category created.' : 'Award category updated.');

        return $this->redirectRoute('admin.award-categories.edit', $savedAwardCategory);
    }

    public function deleteAwardCategory(): mixed
    {
        abort_unless($this->awardCategory instanceof AwardCategory, 404);

        $this->authorize('delete', $this->awardCategory);

        DB::connection($this->awardCategory->getConnectionName())
            ->transaction(function (): void {
                $this->awardCategory?->movieAwardNominations()->update([
                    'award_category_id' => null,
                ]);
                $this->awardCategory?->delete();
            });

        session()->flash('status', 'Award category deleted.');

        return $this->redirectRoute('admin.award-categories.index');
    }

    private function fillAwardCategoryForm(AwardCategory $awardCategory): void
    {
        $this->name = (string) $awardCategory->name;
    }

    /**
     * @return array{name: string}
     */
    private function awardCategoryPayload(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
