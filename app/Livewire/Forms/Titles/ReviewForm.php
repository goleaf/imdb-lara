<?php

namespace App\Livewire\Forms\Titles;

use App\Models\Review;
use Illuminate\Support\Facades\Validator;
use Livewire\Form;

class ReviewForm extends Form
{
    public string $headline = '';

    public string $body = '';

    public bool $containsSpoilers = false;

    protected function rules(): array
    {
        return $this->rulesForDraft(false);
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    private function rulesForDraft(bool $isDraft): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:160'],
            'body' => ['required', 'string', $isDraft ? 'min:5' : 'min:20'],
            'containsSpoilers' => ['boolean'],
        ];
    }

    /**
     * @return array{headline: string, body: string, contains_spoilers: bool}
     */
    public function payload(bool $isDraft = false): array
    {
        $validated = Validator::make([
            'headline' => $this->headline,
            'body' => $this->body,
            'containsSpoilers' => $this->containsSpoilers,
        ], $this->rulesForDraft($isDraft))->validate();

        return [
            'headline' => $validated['headline'],
            'body' => $validated['body'],
            'contains_spoilers' => (bool) $validated['containsSpoilers'],
        ];
    }

    public function fillFromReview(?Review $review): void
    {
        $this->headline = (string) ($review?->headline ?? '');
        $this->body = (string) ($review?->body ?? '');
        $this->containsSpoilers = (bool) ($review?->contains_spoilers ?? false);
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->containsSpoilers = false;
    }
}
