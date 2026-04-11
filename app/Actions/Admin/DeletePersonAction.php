<?php

namespace App\Actions\Admin;

use App\Models\Person;

class DeletePersonAction
{
    public function __construct(private DeleteMediaAssetAction $deleteMediaAsset) {}

    public function handle(Person $person): void
    {
        $person->professions()->delete();
        $person->credits()->delete();
        $person->mediaAssets()->get()->each(
            fn ($mediaAsset) => $this->deleteMediaAsset->handle($mediaAsset),
        );
        $person->delete();
    }
}
