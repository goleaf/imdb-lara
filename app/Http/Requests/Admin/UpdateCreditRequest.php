<?php

namespace App\Http\Requests\Admin;

use App\Models\Credit;
use App\Models\Episode;
use App\Models\PersonProfession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        $credit = $this->route('credit');

        return $credit instanceof Credit
            && ($this->user()?->can('update', $credit) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title_id' => ['required', 'integer', 'exists:titles,id'],
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'person_profession_id' => ['nullable', 'integer', 'exists:person_professions,id'],
            'department' => ['required', 'string', 'max:255'],
            'job' => ['required', 'string', 'max:255'],
            'character_name' => ['nullable', 'string', 'max:255'],
            'billing_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'credited_as' => ['nullable', 'string', 'max:255'],
            'is_principal' => ['required', 'boolean'],
            'episode_id' => ['nullable', 'integer', 'exists:episodes,id'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateProfessionBelongsToSelectedPerson($validator);
                $this->validateEpisodeBelongsToSelectedTitle($validator);
            },
        ];
    }

    public function credit(): Credit
    {
        /** @var Credit */
        return $this->route('credit');
    }

    private function validateProfessionBelongsToSelectedPerson(Validator $validator): void
    {
        $professionId = $this->integer('person_profession_id');
        $personId = $this->integer('person_id');

        if ($professionId === 0 || $personId === 0) {
            return;
        }

        $profession = PersonProfession::query()->select(['id', 'person_id'])->find($professionId);

        if ($profession !== null && $profession->person_id !== $personId) {
            $validator->errors()->add('person_profession_id', 'The selected profession must belong to the selected person.');
        }
    }

    private function validateEpisodeBelongsToSelectedTitle(Validator $validator): void
    {
        $episodeId = $this->integer('episode_id');
        $titleId = $this->integer('title_id');

        if ($episodeId === 0 || $titleId === 0) {
            return;
        }

        $episode = Episode::query()->select(['id', 'title_id', 'series_id'])->find($episodeId);

        if (
            $episode !== null
            && $episode->series_id !== $titleId
            && $episode->title_id !== $titleId
        ) {
            $validator->errors()->add('episode_id', 'The selected episode must belong to the selected title or series.');
        }
    }
}
