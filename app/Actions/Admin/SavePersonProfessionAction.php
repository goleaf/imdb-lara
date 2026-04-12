<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Actions\Admin\Concerns\ResolvesLocalCatalogWriteModels;
use App\Models\LocalPersonProfession;
use App\Models\Person;
use App\Models\PersonProfession;

class SavePersonProfessionAction
{
    use NormalizesAdminAttributes;
    use ResolvesLocalCatalogWriteModels;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(PersonProfession $profession, Person $person, array $attributes): PersonProfession
    {
        $attributes = $this->normalizeAttributes($attributes);
        $attributes['is_primary'] = (bool) ($attributes['is_primary'] ?? false);
        $person = $this->resolveLocalPerson($person);
        $profession = $profession->exists ? $this->resolveLocalPersonProfession($profession) : new LocalPersonProfession;

        if ($attributes['is_primary']) {
            $person->professions()
                ->when(
                    $profession->exists,
                    fn ($query) => $query->whereKeyNot($profession->getKey()),
                )
                ->update(['is_primary' => false]);
        }

        $profession->fill($attributes);
        $profession->person()->associate($person);
        $profession->save();

        return $profession->refresh();
    }
}
