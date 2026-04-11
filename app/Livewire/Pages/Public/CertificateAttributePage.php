<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadCertificateAttributeDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\CertificateAttribute;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CertificateAttributePage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;
    use WithPagination;

    public ?CertificateAttribute $certificateAttribute = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $country = '';

    public function mount(CertificateAttribute $certificateAttribute): void
    {
        $this->certificateAttribute = $certificateAttribute;
        $this->normalizeFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->country = '';

        $this->resetPage(pageName: 'attribute_records');
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'attribute_records');
    }

    public function updatedType(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'attribute_records');
    }

    public function updatedCountry(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'attribute_records');
    }

    public function render(LoadCertificateAttributeDetailsAction $loadCertificateAttributeDetails): View
    {
        abort_unless($this->certificateAttribute instanceof CertificateAttribute, 404);

        $data = $this->withCollectionDefaults(
            $loadCertificateAttributeDetails->handle($this->certificateAttribute, $this->filters()),
            ['summaryItems', 'typeOptions', 'countryOptions'],
        );

        return $this->renderPageView(
            'certificates.attributes.show',
            $data,
        );
    }

    /**
     * @return array{q: string, type: string, country: string}
     */
    private function filters(): array
    {
        return [
            'q' => $this->search,
            'type' => $this->type,
            'country' => $this->country,
        ];
    }

    private function normalizeFilters(): void
    {
        $this->type = TitleType::tryFrom($this->type)?->value ?? '';
        $this->country = str($this->country)->trim()->upper()->toString();
    }
}
