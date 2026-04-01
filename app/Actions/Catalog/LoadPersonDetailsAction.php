<?php

namespace App\Actions\Catalog;

use App\Models\Person;

class LoadPersonDetailsAction
{
    public function handle(Person $person): Person
    {
        $person->load([
            'mediaAssets',
            'professions:id,person_id,department,profession,is_primary,sort_order',
            'credits.title:id,name,slug,title_type,release_year',
        ]);

        return $person;
    }
}
