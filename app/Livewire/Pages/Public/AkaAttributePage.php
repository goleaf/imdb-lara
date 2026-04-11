<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadAkaAttributeDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\AkaAttribute;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AkaAttributePage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;
    use WithPagination;

    public ?AkaAttribute $akaAttribute = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $country = '';

    #[Url]
    public string $language = '';

    public function mount(AkaAttribute $akaAttribute): void
    {
        $this->akaAttribute = $akaAttribute;
        $this->normalizeFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->country = '';
        $this->language = '';

        $this->resetPage(pageName: 'aka_records');
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'aka_records');
    }

    public function updatedType(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'aka_records');
    }

    public function updatedCountry(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'aka_records');
    }

    public function updatedLanguage(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'aka_records');
    }

    public function render(LoadAkaAttributeDetailsAction $loadAkaAttributeDetails): View
    {
        abort_unless($this->akaAttribute instanceof AkaAttribute, 404);

        $data = $this->withCollectionDefaults(
            $loadAkaAttributeDetails->handle($this->akaAttribute, $this->filters()),
            ['summaryItems', 'typeOptions', 'countryOptions', 'languageOptions'],
        );

        return $this->renderPageView(
            'aka-attributes.show',
            $data,
        );
    }

    /**
     * @return array{q: string, type: string, country: string, language: string}
     */
    private function filters(): array
    {
        return [
            'q' => $this->search,
            'type' => $this->type,
            'country' => $this->country,
            'language' => $this->language,
        ];
    }

    private function normalizeFilters(): void
    {
        $this->type = TitleType::tryFrom($this->type)?->value ?? '';
        $this->country = str($this->country)->trim()->upper()->toString();
        $this->language = str($this->language)->trim()->lower()->toString();
    }
}
