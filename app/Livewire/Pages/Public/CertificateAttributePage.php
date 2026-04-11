<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadCertificateAttributeDetailsAction;
use App\Livewire\Pages\Concerns\NormalizesPageViewData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\CertificateAttribute;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CertificateAttributePage extends Component
{
    use NormalizesPageViewData;
    use RendersPageView;

    public ?CertificateAttribute $certificateAttribute = null;

    public function mount(CertificateAttribute $certificateAttribute): void
    {
        $this->certificateAttribute = $certificateAttribute;
    }

    public function render(LoadCertificateAttributeDetailsAction $loadCertificateAttributeDetails): View
    {
        abort_unless($this->certificateAttribute instanceof CertificateAttribute, 404);

        $data = $this->withCollectionDefaults(
            $loadCertificateAttributeDetails->handle($this->certificateAttribute),
            ['summaryItems', 'typeOptions', 'countryOptions'],
        );

        return $this->renderPageView(
            'certificates.attributes.show',
            $data,
        );
    }
}
