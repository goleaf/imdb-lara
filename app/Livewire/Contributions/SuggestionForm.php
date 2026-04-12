<?php

namespace App\Livewire\Contributions;

use App\Actions\Contributions\SubmitContributionAction;
use App\Models\Contribution;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SuggestionForm extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public string $contributableType;

    #[Locked]
    public int $contributableId;

    #[Locked]
    public string $contributableLabel;

    #[Validate]
    public string $field = '';

    #[Validate('required|string|max:5000')]
    public string $value = '';

    #[Validate('nullable|string|max:2000')]
    public string $notes = '';

    public ?string $statusMessage = null;

    public function mount(string $contributableType, int $contributableId, string $contributableLabel): void
    {
        $this->contributableType = $contributableType;
        $this->contributableId = $contributableId;
        $this->contributableLabel = $contributableLabel;
        $this->field = array_key_first($this->fieldDefinitions()) ?? '';
    }

    public function save(SubmitContributionAction $submitContribution): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $this->authorize('create', Contribution::class);

        $validated = $this->validate();
        $fieldDefinitions = $this->fieldDefinitions();

        $submitContribution->handle(
            auth()->user(),
            $this->resolveContributable(),
            [
                'field' => $validated['field'],
                'field_label' => $fieldDefinitions[$validated['field']]['label'],
                'value' => $validated['value'],
                'notes' => $validated['notes'] ?? null,
            ],
        );

        $this->reset('value', 'notes');
        $this->statusMessage = 'Suggestion submitted for editorial review.';
    }

    #[Computed]
    public function viewData(): array
    {
        return [
            'canSubmitContribution' => auth()->user()?->can('create', Contribution::class) ?? false,
            'entityLabel' => match ($this->contributableType) {
                'person' => 'person profile',
                default => 'title page',
            },
            'fieldOptions' => array_values($this->fieldDefinitions()),
        ];
    }

    public function render(): View
    {
        return view('livewire.contributions.suggestion-form', $this->viewData);
    }

    protected function rules(): array
    {
        return [
            'field' => ['required', Rule::in(array_keys($this->fieldDefinitions()))],
        ];
    }

    /**
     * @return array<string, array{value: string, label: string, icon: string}>
     */
    private function fieldDefinitions(): array
    {
        return match ($this->contributableType) {
            'person' => [
                'name' => ['value' => 'name', 'label' => 'Primary name', 'icon' => 'identification'],
                'biography' => ['value' => 'biography', 'label' => 'Biography', 'icon' => 'document-text'],
                'alternate_names' => ['value' => 'alternate_names', 'label' => 'Alternate names', 'icon' => 'language'],
                'birth_place' => ['value' => 'birth_place', 'label' => 'Birth place', 'icon' => 'map-pin'],
                'nationality' => ['value' => 'nationality', 'label' => 'Nationality', 'icon' => 'globe-alt'],
                'known_for_department' => ['value' => 'known_for_department', 'label' => 'Known-for department', 'icon' => 'briefcase'],
            ],
            default => [
                'name' => ['value' => 'name', 'label' => 'Display title', 'icon' => 'film'],
                'original_name' => ['value' => 'original_name', 'label' => 'Original title', 'icon' => 'language'],
                'plot_outline' => ['value' => 'plot_outline', 'label' => 'Plot outline', 'icon' => 'document-text'],
                'synopsis' => ['value' => 'synopsis', 'label' => 'Synopsis', 'icon' => 'newspaper'],
                'release_year' => ['value' => 'release_year', 'label' => 'Release year', 'icon' => 'calendar-days'],
                'runtime_minutes' => ['value' => 'runtime_minutes', 'label' => 'Runtime', 'icon' => 'clock'],
                'age_rating' => ['value' => 'age_rating', 'label' => 'Certification', 'icon' => 'shield-check'],
                'languages' => ['value' => 'languages', 'label' => 'Languages', 'icon' => 'chat-bubble-left-right'],
                'countries' => ['value' => 'countries', 'label' => 'Countries', 'icon' => 'globe-alt'],
            ],
        };
    }

    private function resolveContributable(): Title|Person
    {
        return match ($this->contributableType) {
            'person' => Person::query()
                ->selectDirectoryColumns()
                ->findOrFail($this->contributableId),
            default => Title::query()
                ->selectCatalogCardColumns()
                ->findOrFail($this->contributableId),
        };
    }
}
