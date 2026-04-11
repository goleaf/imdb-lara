<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminAkaAttributesIndexQueryAction;
use App\Actions\Admin\SaveAkaAttributeAction;
use App\Http\Requests\Admin\StoreAkaAttributeRequest;
use App\Http\Requests\Admin\UpdateAkaAttributeRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\AkaAttribute;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AkaAttributesPage extends Component
{
    use RendersPageView;
    use ValidatesFormRequests;
    use WithPagination;

    public ?AkaAttribute $akaAttribute = null;

    #[Url(as: 'q')]
    public string $search = '';

    public string $name = '';

    public function mount(?AkaAttribute $akaAttribute = null): void
    {
        $this->akaAttribute = $akaAttribute;
        $this->fillAkaAttributeForm($akaAttribute ?? new AkaAttribute);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function renderAkaAttributesIndexPage(BuildAdminAkaAttributesIndexQueryAction $buildAdminAkaAttributesIndexQuery): View
    {
        $this->authorize('viewAny', AkaAttribute::class);

        return $this->renderPageView('admin.aka-attributes.index', [
            'akaAttributes' => $buildAdminAkaAttributesIndexQuery
                ->handle($this->search)
                ->simplePaginate(20)
                ->withQueryString(),
            'hasActiveFilters' => trim($this->search) !== '',
        ]);
    }

    protected function renderAkaAttributeCreatePage(): View
    {
        $this->authorize('create', AkaAttribute::class);

        return $this->renderPageView('admin.aka-attributes.create', [
            'akaAttribute' => new AkaAttribute($this->akaAttributePayload()),
        ]);
    }

    protected function renderAkaAttributeEditPage(): View
    {
        abort_unless($this->akaAttribute instanceof AkaAttribute, 404);

        $this->authorize('view', $this->akaAttribute);

        return $this->renderPageView('admin.aka-attributes.edit', [
            'akaAttribute' => $this->akaAttribute
                ->loadCount('movieAkaAttributes')
                ->fill($this->akaAttributePayload()),
        ]);
    }

    public function saveAkaAttribute(SaveAkaAttributeAction $saveAkaAttribute): mixed
    {
        $validated = $this->akaAttribute instanceof AkaAttribute
            ? $this->validateWithFormRequest(UpdateAkaAttributeRequest::class, $this->akaAttributePayload(), [
                'akaAttribute' => $this->akaAttribute,
            ])
            : $this->validateWithFormRequest(StoreAkaAttributeRequest::class, $this->akaAttributePayload());

        $savedAkaAttribute = $saveAkaAttribute->handle($this->akaAttribute ?? new AkaAttribute, $validated);

        $this->akaAttribute = $savedAkaAttribute;
        $this->fillAkaAttributeForm($savedAkaAttribute);
        $this->resetValidation();
        session()->flash('status', $savedAkaAttribute->wasRecentlyCreated ? 'AKA attribute created.' : 'AKA attribute updated.');

        return $this->redirectRoute('admin.aka-attributes.edit', $savedAkaAttribute);
    }

    public function deleteAkaAttribute(): mixed
    {
        abort_unless($this->akaAttribute instanceof AkaAttribute, 404);

        $this->authorize('delete', $this->akaAttribute);

        DB::connection($this->akaAttribute->getConnectionName())
            ->transaction(function (): void {
                $this->akaAttribute?->movieAkaAttributes()->delete();
                $this->akaAttribute?->delete();
            });

        session()->flash('status', 'AKA attribute deleted.');

        return $this->redirectRoute('admin.aka-attributes.index');
    }

    private function fillAkaAttributeForm(AkaAttribute $akaAttribute): void
    {
        $this->name = (string) $akaAttribute->name;
    }

    /**
     * @return array{name: string}
     */
    private function akaAttributePayload(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
