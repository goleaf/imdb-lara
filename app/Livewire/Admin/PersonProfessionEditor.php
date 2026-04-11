<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\SavePersonProfessionAction;
use App\Http\Requests\Admin\StorePersonProfessionRequest;
use App\Http\Requests\Admin\UpdatePersonProfessionRequest;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Models\Person;
use App\Models\PersonProfession;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PersonProfessionEditor extends Component
{
    use ValidatesFormRequests;

    public Person $person;

    public ?PersonProfession $professionRecord = null;

    public string $department = '';

    public string $profession = '';

    public ?int $sort_order = null;

    public bool $is_primary = false;

    public ?int $defaultSortOrder = null;

    public function mount(
        Person $person,
        ?PersonProfession $profession = null,
        ?int $defaultSortOrder = null,
    ): void {
        $this->person = $person;
        $this->professionRecord = $profession;
        $this->defaultSortOrder = $defaultSortOrder;

        if ($profession instanceof PersonProfession) {
            $this->department = (string) $profession->department;
            $this->profession = (string) $profession->profession;
            $this->sort_order = $profession->sort_order;
            $this->is_primary = (bool) $profession->is_primary;

            return;
        }

        $this->sort_order = $defaultSortOrder;
    }

    public function save(SavePersonProfessionAction $savePersonProfession): void
    {
        $validated = $this->professionRecord instanceof PersonProfession
            ? $this->validateWithFormRequest(UpdatePersonProfessionRequest::class, $this->payload(), [
                'profession' => $this->professionRecord,
            ])
            : $this->validateWithFormRequest(StorePersonProfessionRequest::class, $this->payload(), [
                'person' => $this->person,
            ]);

        $profession = $savePersonProfession->handle(
            $this->professionRecord ?? new PersonProfession,
            $this->person,
            $validated,
        );

        $this->professionRecord = $profession;
        $this->department = (string) $profession->department;
        $this->profession = (string) $profession->profession;
        $this->sort_order = $profession->sort_order;
        $this->is_primary = (bool) $profession->is_primary;

        $this->dispatch('person-professions-updated');
        session()->flash('status', $profession->wasRecentlyCreated ? 'Profession added.' : 'Profession updated.');
    }

    public function delete(): void
    {
        abort_unless($this->professionRecord instanceof PersonProfession, 404);

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
            'profession' => $this->profession,
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
        ];
    }
}
