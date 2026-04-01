<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\NotFoundFormRequest;
use App\Models\Person;

class ShowPersonRequest extends NotFoundFormRequest
{
    public function authorize(): bool
    {
        $person = $this->route('person');

        return $person instanceof Person
            && ($person->is_published || ($this->user()?->can('view', $person) ?? false));
    }

    public function person(): Person
    {
        /** @var Person */
        return $this->route('person');
    }
}
