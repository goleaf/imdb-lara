<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\ResolvesLocalCatalogWriteModels;
use App\Models\Person;

class DeletePersonAction
{
    use ResolvesLocalCatalogWriteModels;

    public function __construct(private DeleteMediaAssetAction $deleteMediaAsset) {}

    public function handle(Person $person): void
    {
        $person = $this->resolveLocalPerson($person);
        $person->professions()->delete();
        $person->credits()->delete();
        $person->mediaAssets()->get()->each(
            fn ($mediaAsset) => $this->deleteMediaAsset->handle($mediaAsset),
        );
        $person->delete();
    }
}
