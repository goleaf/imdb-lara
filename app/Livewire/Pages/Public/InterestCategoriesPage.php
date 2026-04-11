<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Catalog\GetInterestCategoryDirectorySnapshotAction;
use App\Actions\Catalog\LoadInterestCategoryDetailsAction;
use App\Actions\Seo\PageSeoData;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\InterestCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InterestCategoriesPage extends Component
{
    use RendersPageView;

    public ?InterestCategory $interestCategory = null;

    public function mount(?InterestCategory $interestCategory = null): void
    {
        $this->interestCategory = $interestCategory;
    }

    public function render(
        GetInterestCategoryDirectorySnapshotAction $getInterestCategoryDirectorySnapshot,
        LoadInterestCategoryDetailsAction $loadInterestCategoryDetails,
    ): View {
        if ($this->interestCategory instanceof InterestCategory) {
            return $this->renderPageView(
                'interest-categories.show',
                $loadInterestCategoryDetails->handle($this->interestCategory),
            );
        }

        return $this->renderPageView('interest-categories.index', [
            'directorySnapshot' => $getInterestCategoryDirectorySnapshot->handle(),
            'seo' => new PageSeoData(
                title: 'Interest Categories',
                description: 'Browse interest-category lanes, grouped interests, and linked titles from the imported MySQL catalog.',
                canonical: route('public.interest-categories.index'),
                breadcrumbs: [
                    ['label' => 'Home', 'href' => route('public.home')],
                    ['label' => 'Interest Categories'],
                ],
                paginationPageName: 'interest-categories',
                preserveQueryString: true,
                allowedQueryParameters: ['q', 'sort'],
            ),
        ]);
    }
}
