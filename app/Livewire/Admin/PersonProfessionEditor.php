<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\SavePersonProfessionAction;
use App\Http\Requests\Admin\StorePersonProfessionRequest;
use App\Http\Requests\Admin\UpdatePersonProfessionRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Models\Person;
use App\Models\PersonProfession;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PersonProfessionEditor extends Component
{
    use ValidatesFormRequests;

    #[Locked]
    public Person $person;

    #[Locked]
    public ?PersonProfession $professionRecord = null;

    public string $department = '';

    public string $professionName = '';

    public ?int $sort_order = null;

    public bool $is_primary = false;

    public ?int $defaultSortOrder = null;

    public function mount(
        Person $person,
        ?PersonProfession $professionRecord = null,
        ?int $defaultSortOrder = null,
    ): void {
        $this->person = $person;
        $this->professionRecord = $professionRecord;
        $this->defaultSortOrder = $defaultSortOrder;

        if ($professionRecord?->exists) {
            $this->department = (string) $professionRecord->department;
            $this->professionName = (string) $professionRecord->profession;
            $this->sort_order = $professionRecord->sort_order;
            $this->is_primary = (bool) $professionRecord->is_primary;

            return;
        }

        $this->sort_order = $defaultSortOrder;
    }

    public function save(SavePersonProfessionAction $savePersonProfession): void
    {
        $validated = $this->hasExistingProfessionRecord()
            ? $this->validateWithFormRequest(UpdatePersonProfessionRequest::class, $this->payload(), [
                'profession' => $this->professionRecord,
            ])
            : $this->validateWithFormRequest(StorePersonProfessionRequest::class, $this->payload(), [
                'person' => $this->person,
            ]);

        $profession = $savePersonProfession->handle(
            $this->hasExistingProfessionRecord() ? $this->professionRecord : new PersonProfession,
            $this->person,
            $validated,
        );

        $this->professionRecord = $profession;
        $this->department = (string) $profession->department;
        $this->professionName = (string) $profession->profession;
        $this->sort_order = $profession->sort_order;
        $this->is_primary = (bool) $profession->is_primary;

        $this->dispatch('person-professions-updated');
        session()->flash('status', $profession->wasRecentlyCreated ? 'Profession added.' : 'Profession updated.');
    }

    public function delete(): void
    {
        abort_unless($this->hasExistingProfessionRecord(), 404);

        $this->authorize('update', $this->person);
        $this->professionRecord->delete();

        $this->dispatch('person-professions-updated');
        session()->flash('status', 'Profession deleted.');
    }

    public function render(): View
    {
        return view('livewire.admin.person-profession-editor');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'department' => $this->department,
            'profession' => $this->professionName,
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
        ];
    }

    private function hasExistingProfessionRecord(): bool
    {
        return $this->professionRecord?->exists ?? false;
    }
}
