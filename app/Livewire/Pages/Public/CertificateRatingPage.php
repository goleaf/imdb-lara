<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadCertificateRatingDetailsAction;
use App\Enums\TitleType;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\CertificateRating;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CertificateRatingPage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;
    use WithPagination;

    public ?CertificateRating $certificateRating = null;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $country = '';

    public function mount(CertificateRating $certificateRating): void
    {
        $this->certificateRating = $certificateRating;
        $this->normalizeFilters();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->country = '';

        $this->resetPage(pageName: 'rating_records');
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'rating_records');
    }

    public function updatedType(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'rating_records');
    }

    public function updatedCountry(): void
    {
        $this->normalizeFilters();
        $this->resetPage(pageName: 'rating_records');
    }

    public function render(LoadCertificateRatingDetailsAction $loadCertificateRatingDetails): View
    {
        abort_unless($this->certificateRating instanceof CertificateRating, 404);

        $data = $this->withCollectionDefaults(
            $loadCertificateRatingDetails->handle($this->certificateRating, $this->filters()),
            ['summaryItems', 'typeOptions', 'countryOptions'],
        );

        return $this->renderPageView(
            'certificates.ratings.show',
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
