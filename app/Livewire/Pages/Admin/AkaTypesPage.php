<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminAkaTypesIndexQueryAction;
use App\Actions\Admin\SaveAkaTypeAction;
use App\Http\Requests\Admin\StoreAkaTypeRequest;
use App\Http\Requests\Admin\UpdateAkaTypeRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\AkaType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AkaTypesPage extends Component
{
    use RendersPageView;
    use ValidatesFormRequests;
    use WithPagination;

    public ?AkaType $akaType = null;

    #[Url(as: 'q')]
    public string $search = '';

    public string $name = '';

    public function mount(?AkaType $akaType = null): void
    {
        $this->akaType = $akaType;
        $this->fillAkaTypeForm($akaType ?? new AkaType);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function renderAkaTypesIndexPage(BuildAdminAkaTypesIndexQueryAction $buildAdminAkaTypesIndexQuery): View
    {
        $this->authorize('viewAny', AkaType::class);

        return $this->renderPageView('admin.aka-types.index', [
            'akaTypes' => $buildAdminAkaTypesIndexQuery
                ->handle($this->search)
                ->simplePaginate(20)
                ->withQueryString(),
            'hasActiveFilters' => trim($this->search) !== '',
        ]);
    }

    protected function renderAkaTypeCreatePage(): View
    {
        $this->authorize('create', AkaType::class);

        return $this->renderPageView('admin.aka-types.create', [
            'akaType' => new AkaType($this->akaTypePayload()),
        ]);
    }

    protected function renderAkaTypeEditPage(): View
    {
        abort_unless($this->akaType instanceof AkaType, 404);

        $this->authorize('view', $this->akaType);

        return $this->renderPageView('admin.aka-types.edit', [
            'akaType' => $this->akaType
                ->loadCount('movieAkaTypes')
                ->fill($this->akaTypePayload()),
        ]);
    }

    public function saveAkaType(SaveAkaTypeAction $saveAkaType): mixed
    {
        $validated = $this->akaType instanceof AkaType
            ? $this->validateWithFormRequest(UpdateAkaTypeRequest::class, $this->akaTypePayload(), [
                'akaType' => $this->akaType,
            ])
            : $this->validateWithFormRequest(StoreAkaTypeRequest::class, $this->akaTypePayload());

        $savedAkaType = $saveAkaType->handle($this->akaType ?? new AkaType, $validated);

        $this->akaType = $savedAkaType;
        $this->fillAkaTypeForm($savedAkaType);
        $this->resetValidation();
        session()->flash('status', $savedAkaType->wasRecentlyCreated ? 'AKA type created.' : 'AKA type updated.');

        return $this->redirectRoute('admin.aka-types.edit', $savedAkaType);
    }

    public function deleteAkaType(): mixed
    {
        abort_unless($this->akaType instanceof AkaType, 404);

        $this->authorize('delete', $this->akaType);

        DB::connection($this->akaType->getConnectionName())
            ->transaction(function (): void {
                $this->akaType?->movieAkaTypes()->delete();
                $this->akaType?->delete();
            });

        session()->flash('status', 'AKA type deleted.');

        return $this->redirectRoute('admin.aka-types.index');
    }

    private function fillAkaTypeForm(AkaType $akaType): void
    {
        $this->name = (string) $akaType->name;
    }

    /**
     * @return array{name: string}
     */
    private function akaTypePayload(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
