<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminContributionsIndexQueryAction;
use App\Enums\ContributionStatus;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Contribution;
use App\Models\LocalPerson;
use App\Models\LocalTitle;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ContributionsPage extends Component
{
    use RendersPageView;
    use WithPagination;

    #[On('moderation-queue-updated')]
    public function refreshQueue(): void
    {
        // Re-render the queue after a review changes contribution ordering.
    }

    public function render(BuildAdminContributionsIndexQueryAction $buildAdminContributionsIndexQuery): View
    {
        $contributions = $buildAdminContributionsIndexQuery
            ->handle()
            ->simplePaginate(20)
            ->withQueryString();

        $this->hydrateAdminContributables($contributions);

        return $this->renderPageView('admin.contributions.index', [
            'contributions' => $contributions,
            'contributionStatuses' => ContributionStatus::cases(),
        ]);
    }

    private function hydrateAdminContributables(AbstractPaginator $contributions): void
    {
        /** @var Collection<int, Contribution> $items */
        $items = $contributions->getCollection();

        $titleContributions = $items->where('contributable_type', Title::class);
        $personContributions = $items->where('contributable_type', Person::class);

        $titles = LocalTitle::query()
            ->select(['id', 'name', 'slug'])
            ->whereKey($titleContributions->pluck('contributable_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $people = LocalPerson::query()
            ->select(['id', 'name', 'slug'])
            ->whereKey($personContributions->pluck('contributable_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $items->each(function (Contribution $contribution) use ($titles, $people): void {
            $resolvedContributable = match ($contribution->contributable_type) {
                Title::class => $titles->get($contribution->contributable_id),
                Person::class => $people->get($contribution->contributable_id),
                default => $contribution->contributable instanceof Model ? $contribution->contributable : null,
            };

            $contribution->setRelation('adminContributable', $resolvedContributable);
        });
    }
}
