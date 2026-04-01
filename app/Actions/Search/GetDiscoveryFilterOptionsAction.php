<?php

namespace App\Actions\Search;

use App\Enums\TitleType;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;

class GetDiscoveryFilterOptionsAction
{
    /**
     * @return array{genres: Collection<int, Genre>, titleTypes: list<TitleType>}
     */
    public function handle(): array
    {
        return [
            'genres' => Genre::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
            'titleTypes' => TitleType::cases(),
        ];
    }
}
