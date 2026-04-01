<?php

namespace App\Actions\Admin;

use App\Models\Person;

class DeletePersonAction
{
    public function handle(Person $person): void
    {
        $person->professions()->delete();
        $person->credits()->delete();
        $person->mediaAssets()->delete();
        $person->delete();
    }
}
