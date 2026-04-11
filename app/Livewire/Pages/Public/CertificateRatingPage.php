<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\LoadCertificateRatingDetailsAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\CertificateRating;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CertificateRatingPage extends Component
{
    use RendersPageView;

    public ?CertificateRating $certificateRating = null;

    public function mount(CertificateRating $certificateRating): void
    {
        $this->certificateRating = $certificateRating;
    }

    public function render(LoadCertificateRatingDetailsAction $loadCertificateRatingDetails): View
    {
        abort_unless($this->certificateRating instanceof CertificateRating, 404);

        return $this->renderPageView(
            'certificates.ratings.show',
            $loadCertificateRatingDetails->handle($this->certificateRating),
        );
    }
}
