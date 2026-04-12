<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Actions\Admin\Concerns\ResolvesLocalCatalogWriteModels;
use App\Models\LocalPerson;
use App\Models\Person;

class SavePersonAction
{
    use NormalizesAdminAttributes;
    use ResolvesLocalCatalogWriteModels;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Person $person, array $attributes): LocalPerson
    {
        $attributes = $this->normalizeAttributes($attributes);
        $attributes['is_published'] = (bool) ($attributes['is_published'] ?? false);

        $person = $person->exists ? $this->resolveLocalPerson($person) : new LocalPerson;
        $person->fill($attributes);
        $person->save();

        return $person->refresh();
    }
}
