<?php

namespace App\Livewire\Lists;

use App\Actions\Moderation\ReportUserListAction;
use App\Enums\ReportReason;
use App\Livewire\Forms\Reviews\ReportReviewForm as ReportContentForm;
use App\Models\UserList;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ReportListForm extends Component
{
    use AuthorizesRequests;

    public UserList $list;

    public ReportContentForm $form;

    public ?string $statusMessage = null;

    public string $modalId = '';

    /**
     * @var list<array{value: string, label: string, icon: string}>
     */
    public array $reportReasons = [];

    public function mount(UserList $list): void
    {
        $this->list = $list;
        $this->modalId = 'report-list-'.$list->id;
        $this->form->reason = ReportReason::Inaccurate->value;
        $this->reportReasons = array_map(
            static fn (ReportReason $reportReason): array => [
                'value' => $reportReason->value,
                'label' => $reportReason->label(),
                'icon' => $reportReason->icon(),
            ],
            ReportReason::cases(),
        );
    }

    public function save(ReportUserListAction $reportUserList): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('report', $this->list);
        $reportUserList->handle(auth()->user(), $this->list, $this->form->payload());

        $this->statusMessage = 'List reported.';
        $this->form->reset('details');
    }

    public function render(): View
    {
        return view('livewire.lists.report-list-form');
    }
}
