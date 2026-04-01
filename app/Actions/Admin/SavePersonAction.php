<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Models\Person;

class SavePersonAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Person $person, array $attributes): Person
    {
        $attributes = $this->normalizeAttributes($attributes);
        $attributes['is_published'] = (bool) ($attributes['is_published'] ?? false);

        $person->fill($attributes);
        $person->save();

        return $person->refresh();
    }
}
