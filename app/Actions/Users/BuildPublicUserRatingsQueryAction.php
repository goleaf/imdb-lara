<?php

namespace App\Actions\Users;

use App\Models\Rating;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildPublicUserRatingsQueryAction
{
    public function handle(User $profileUser): Builder
    {
        return Rating::query()
            ->select([
                'id',
                'user_id',
                'title_id',
                'score',
                'created_at',
            ])
            ->whereBelongsTo($profileUser)
            ->with([
                'title' => fn ($titleQuery) => $titleQuery
                    ->select(Title::catalogCardColumns())
                    ->publishedCatalog()
                    ->withCatalogCardRelations(),
            ])
            ->latest('created_at');
    }
}
