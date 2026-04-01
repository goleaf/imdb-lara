<?php

namespace App\Actions\Catalog;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;

class GetFeaturedGenresAction
{
    /**
     * @return Collection<int, Genre>
     */
    public function handle(): Collection
    {
        return Genre::query()
            ->select(['id', 'name', 'slug'])
            ->orderBy('name')
            ->get();
    }
}
